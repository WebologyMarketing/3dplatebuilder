<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A product a plate maker sells: a plate product (opens the builder on a style) or a simple fixed-price one. */
class ShopProduct extends Model
{
    protected $guarded = ['id', 'shop_id'];

    protected function casts(): array
    {
        return ['images' => 'array', 'active' => 'boolean'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isPlate(): bool
    {
        return $this->type === 'plate';
    }

    /** What it costs on the shop: a plate product starts at its style's pair price. */
    public function priceLabel(?array $builder = null): string
    {
        if (! $this->isPlate()) {
            return '£'.number_format(($this->price_pence ?? 0) / 100, 2);
        }
        $pair = ($builder ?? \App\Support\BuilderSettings::forShop($this->shop))['styles'][$this->style]['pair'] ?? null;

        return $pair !== null ? 'From £'.$pair : '—';
    }
}
