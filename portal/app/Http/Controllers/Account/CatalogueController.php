<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ShopProduct;
use App\Support\BuilderSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Products (Pro and Complete): plate products that open the builder on a style, and simple fixed-price ones. */
class CatalogueController extends Controller implements HasMiddleware
{
    /** Starter's one product is the builder itself, so the products list is for Pro and up. */
    public static function middleware(): array
    {
        return [new Middleware(function (Request $request, Closure $next) {
            if (! $request->user()->shop->canAddProducts()) {
                return redirect()->route('builder')->with('status', 'Your Starter shop sells through the builder, so there are no extra products to add. Upgrade to Pro to add products.');
            }

            return $next($request);
        })];
    }

    public function index(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.products', [
            'shop' => $shop,
            'items' => $shop->shopProducts()->get(),
            'b' => BuilderSettings::forShop($shop),
            'c' => config('portal.catalogue'),
        ]);
    }

    public function create(Request $request)
    {
        $shop = $request->user()->shop;
        abort_if($shop->shopProducts()->count() >= config('portal.max_products'), 403);

        return $this->form($shop, new ShopProduct(['type' => $request->query('type') === 'simple' ? 'simple' : 'plate', 'active' => true, 'style' => 'printed']));
    }

    public function store(Request $request)
    {
        $shop = $request->user()->shop;
        abort_if($shop->shopProducts()->count() >= config('portal.max_products'), 403);

        $item = new ShopProduct;
        $item->shop_id = $shop->id;
        $item->sort = (int) $shop->shopProducts()->max('sort') + 1;
        $this->save($request, $item);

        return redirect()->route('products')->with('status', "Added {$item->name}.");
    }

    public function edit(Request $request, ShopProduct $product)
    {
        $this->own($request, $product);

        return $this->form($request->user()->shop, $product);
    }

    public function update(Request $request, ShopProduct $product)
    {
        $this->own($request, $product);
        $this->save($request, $product);

        return redirect()->route('products')->with('status', "Saved {$product->name}.");
    }

    public function destroy(Request $request, ShopProduct $product)
    {
        $this->own($request, $product);
        // Image files stay until the next publish has gone out (the website may still be fetching them).
        $product->delete();

        return redirect()->route('products')->with('status', "Removed {$product->name}.");
    }

    /** Move a product up or down the list (the order it shows on the shop). */
    public function move(Request $request, ShopProduct $product)
    {
        $this->own($request, $product);
        $items = $request->user()->shop->shopProducts()->get()->values();
        $i = $items->search(fn ($p) => $p->id === $product->id);
        $j = $request->input('dir') === 'up' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < $items->count()) {
            $order = $items->pluck('id')->all();
            [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            foreach ($order as $n => $id) {
                ShopProduct::whereKey($id)->update(['sort' => $n + 1]);
            }
        }

        return redirect()->route('products');
    }

    /** A product image, for previews in the portal. */
    public function image(Request $request, ShopProduct $product, int $n)
    {
        $this->own($request, $product);
        $path = $product->images[$n] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return DetailsController::safeFile($path);
    }

    private function form($shop, ShopProduct $item)
    {
        return view('account.product', [
            'shop' => $shop,
            'item' => $item,
            'c' => config('portal.catalogue'),
            'b' => BuilderSettings::forShop($shop),
        ]);
    }

    private function save(Request $request, ShopProduct $item): void
    {
        $c = config('portal.catalogue');
        $max = config('portal.max_product_images');
        $data = $request->validate([
            'type' => ['required', Rule::in(['plate', 'simple'])],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'style' => ['required_if:type,plate', 'nullable', Rule::in(array_keys($c['styles']))],
            'size' => ['nullable', Rule::in(array_keys($c['sizes']))],
            'badge' => ['nullable', Rule::in(array_keys($c['badges']))],
            'price' => ['required_if:type,simple', 'nullable', 'numeric', 'min:0', 'max:99999'],
            'active' => ['nullable', 'boolean'],
            'remove' => ['array'],
            'remove.*' => ['integer'],
            'images' => ['array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Give the product a name.',
            'price.required_if' => 'Set a price for this product.',
            'price.numeric' => 'The price needs to be a number, like 12.99.',
            'images.*.mimes' => 'Images need to be JPG, PNG or WebP.',
            'images.*.max' => 'Each image can be up to 5 MB.',
        ]);

        $images = array_values(array_diff_key($item->images ?? [], array_flip($data['remove'] ?? [])));
        $new = $request->file('images', []);
        if (count($images) + count($new) > $max) {
            back()->withInput()->withErrors(['images' => "Up to {$max} images per product."])->throwResponse();
        }
        $shopId = $item->shop_id ?: $request->user()->shop_id;
        foreach ($new as $file) {
            $images[] = $file->store("shops/{$shopId}/products", 'local');
        }

        $plate = $data['type'] === 'plate';
        $item->fill([
            'type' => $data['type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'style' => $plate ? $data['style'] : null,
            'size' => $plate ? ($data['size'] ?? null) : null,
            'badge' => $plate ? ($data['badge'] ?? null) : null,
            'price_pence' => $plate ? null : (int) round(((float) $data['price']) * 100),
            'images' => $images,
            'active' => (bool) ($data['active'] ?? false),
        ])->save();
    }

    private function own(Request $request, ShopProduct $product): void
    {
        abort_unless($product->shop_id === $request->user()->shop_id, 404);
    }
}
