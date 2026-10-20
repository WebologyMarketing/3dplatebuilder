@extends('layouts.account')
@section('title', $item->exists ? 'Edit '.$item->name : 'New product')

@php
  $type = old('type', $item->type);
  $max = config('portal.max_product_images');
@endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / <a href="{{ route('products') }}">Products</a> / {{ $item->exists ? $item->name : 'New' }}</p>
    <h1>{{ $item->exists ? $item->name : ($type === 'simple' ? 'New simple product' : 'New plate product') }}</h1>
  </div>
</header>

@if ($errors->any())<div class="notice bad" role="alert">@foreach ($errors->unique() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('products.update', $item) : route('products.store') }}" class="stack">
  @csrf
  <section class="card stack">
    <fieldset class="seg" style="border:0;padding:0;margin:0">
      <legend class="sr-only">Type of product</legend>
      <label class="tick"><input type="radio" name="type" value="plate" @checked($type === 'plate')><span class="box"></span><span>Plate product<span class="sub2">Opens your builder on a style. Priced by your builder.</span></span></label>
      <label class="tick"><input type="radio" name="type" value="simple" @checked($type === 'simple')><span class="box"></span><span>Simple product<span class="sub2">A fixed price, like a keyring or show-plate frame.</span></span></label>
    </fieldset>

    <label class="field">Name
      <input name="name" value="{{ old('name', $item->name) }}" maxlength="120" required placeholder="{{ $type === 'simple' ? 'Number plate keyring' : '4D 3mm number plates' }}">
    </label>

    <div class="grid-2" data-for="plate">
      <label class="field">Style the builder opens on
        <select name="style">
          @foreach ($c['styles'] as $id => $d)
            <option value="{{ $id }}" @selected(old('style', $item->style) === $id)>{{ $d['label'] }} (from £{{ $b['styles'][$id]['pair'] }}){{ $b['styles'][$id]['on'] ? '' : ' · switched off' }}</option>
          @endforeach
        </select>
        <small>The price comes from your builder, so it always matches what customers pay.</small>
      </label>
      <label class="field">Size <small>Optional</small>
        <select name="size">
          <option value="">Customer's choice</option>
          @foreach ($c['sizes'] as $id => $d)<option value="{{ $id }}" @selected(old('size', $item->size) === $id)>{{ $d['label'] }}</option>@endforeach
        </select>
      </label>
      <label class="field">Badge <small>Optional</small>
        <select name="badge">
          <option value="">Customer's choice</option>
          @foreach ($c['badges'] as $id => $d)<option value="{{ $id }}" @selected(old('badge', $item->badge) === $id)>{{ $d['label'] }}</option>@endforeach
        </select>
      </label>
    </div>

    <label class="field" data-for="simple">Price
      <span class="money" style="font-size:15px">£<input name="price" value="{{ old('price', $item->price_pence !== null ? number_format($item->price_pence / 100, 2, '.', '') : '') }}" inputmode="decimal" placeholder="0.00"></span>
    </label>

    <label class="field">Description <small>Shown on the product page. Plain text; a blank line starts a new paragraph.</small>
      <textarea name="description" rows="6" maxlength="5000">{{ old('description', $item->description) }}</textarea>
    </label>
  </section>

  <section class="card" aria-labelledby="h-img">
    <h2 id="h-img">Images</h2>
    <p class="lead">Up to {{ $max }}, JPG, PNG or WebP, 5 MB each. The first is the main image. Square photos look best.</p>
    @if ($item->images)
      <div class="imgs" style="margin-bottom:14px">
        @foreach ($item->images as $n => $path)
          <label><img src="{{ route('products.image', [$item, $n]) }}" alt="Image {{ $n + 1 }}"><span><input type="checkbox" name="remove[]" value="{{ $n }}"> Remove</span></label>
        @endforeach
      </div>
    @endif
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
  </section>

  <section class="card">
    <label class="onoff"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $item->active))> Show on my website</label>
  </section>

  <div class="actions"><button class="btn" type="submit">{{ $item->exists ? 'Save' : 'Add product' }}</button><a class="btn ghost" href="{{ route('products') }}">Cancel</a></div>
</form>

@if ($item->exists)
  <form method="post" action="{{ route('products.destroy', $item) }}" style="margin-top:20px" onsubmit="return confirm('Remove {{ addslashes($item->name) }}? It comes off your website when you next publish.')">
    @csrf
    <button class="linkbtn" type="submit" style="color:var(--bad)">Remove this product</button>
  </form>
@endif

@push('scripts')
<script>
(function () {
  function show() {
    var t = document.querySelector('input[name="type"]:checked').value;
    document.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.dataset.for !== t; });
  }
  document.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', show); });
  show();
})();
</script>
@endpush
@endsection
