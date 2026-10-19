@extends('layouts.account')
@section('title', 'Products and add-ons')

@php
  $tickSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F7D333" stroke-width="3.5" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>';
  $styles = old('styles', array_keys($products['styles'] ?? []));
  $prices = old('price', $products['styles'] ?? []);
  $sizes = old('sizes', $products['sizes'] ?? []);
  $extras = old('extras', $products['extras'] ?? []);
@endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Products and add-ons</p>
    <h1>What do you sell?</h1>
    <p class="sub">Tick what you offer and set your prices. Everything ticked appears in your builder. You can change this any time.</p>
  </div>
</header>

@if ($errors->any())<div class="notice bad" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="cols">
  <div class="col-main">
    <form method="post" action="{{ route('products') }}" class="stack" id="products-form">
      @csrf
      <section class="card" aria-labelledby="h-st">
        <h2 id="h-st">Plate styles</h2>
        <p class="lead">Your price for a pair, front and rear. Single plates are worked out from this.</p>
        <div class="stack" style="gap:8px">
          @foreach ($catalogue['styles'] as $id => $label)
            <div class="style-row">
              <label class="tick"><input type="checkbox" name="styles[]" value="{{ $id }}" @checked(in_array($id, $styles))><span class="box">{!! $tickSvg !!}</span>{{ $label }}</label>
              <label class="price">£<input name="price[{{ $id }}]" value="{{ $prices[$id] ?? '' }}" inputmode="decimal" placeholder="0.00" aria-label="Pair price for {{ $label }}"></label>
            </div>
          @endforeach
        </div>
      </section>

      <section class="card" aria-labelledby="h-sz">
        <h2 id="h-sz">Sizes</h2>
        <p class="lead">Customers can pick a different size front and rear.</p>
        <div class="ticks">
          @foreach ($catalogue['sizes'] as $id => $label)
            <label class="tick"><input type="checkbox" name="sizes[]" value="{{ $id }}" @checked(in_array($id, $sizes))><span class="box">{!! $tickSvg !!}</span>{{ $label }}</label>
          @endforeach
        </div>
      </section>

      <section class="card" aria-labelledby="h-ex">
        <h2 id="h-ex">Badges, borders and extras</h2>
        <p class="lead">Included on every plan. We'll set the prices with you when we build your shop.</p>
        <div class="ticks">
          @foreach ($catalogue['extras'] as $id => $label)
            <label class="tick"><input type="checkbox" name="extras[]" value="{{ $id }}" @checked(in_array($id, $extras))><span class="box">{!! $tickSvg !!}</span>{{ $label }}</label>
          @endforeach
        </div>
      </section>

      <div class="actions"><button class="btn" type="submit">Save products</button></div>
    </form>

    <section class="card" aria-labelledby="h-ft">
      <h2 id="h-ft">Builder features on your plan</h2>
      <p class="lead">You're on {{ $shop->planName() }}.</p>
      <div class="ticks">
        @foreach ($features as $f)
          @php $has = $shop->hasFeature($f['from']); @endphp
          <div class="feature {{ $has ? '' : 'off' }}">{{ $f['label'] }} <span class="pill {{ $has ? 'good' : 'grey' }}">{{ $has ? 'Included' : config('portal.plans.'.$f['from'].'.name').' plan' }}</span></div>
        @endforeach
      </div>
      <p style="margin-top:14px"><a href="https://3dplatebuilder.com/pricing.html" target="_blank" rel="noopener">Compare plans</a>. To change plan, raise a ticket.</p>
    </section>

    <form method="post" action="{{ route('products.addons') }}" class="card" aria-labelledby="h-ad" style="border:2px solid var(--ink)">
      @csrf
      <h2 id="h-ad">Add-ons from us</h2>
      <p class="lead">One-off work, added to your next invoice once we've confirmed it. No VAT to add.</p>
      <div class="stack" style="gap:8px">
        @foreach ($addons as $key => $a)
          @php $asked = in_array($key, $requested); @endphp
          <label class="tick" @if ($asked) style="opacity:.7;cursor:default" @endif>
            <input type="checkbox" name="addons[]" value="{{ $key }}" data-price="{{ $a['price'] }}" @checked($asked) @disabled($asked)>
            <span class="box">{!! $tickSvg !!}</span>
            <span><span style="display:block">{{ $a['name'] }}</span><span class="sub2">{{ $asked ? 'Requested' : $a['note'] }}</span></span>
            <span class="price-tag">{{ money($a['price']) }}</span>
          </label>
        @endforeach
        <div class="tick" style="cursor:default">
          <span class="box" style="visibility:hidden"></span>
          <span><span style="display:block">Extra design time</span><span class="sub2">For anything beyond your plan. <a href="{{ route('support') }}">Raise a ticket</a> and we'll quote the hours first.</span></span>
          <span class="price-tag">{{ money(config('portal.design_hourly')) }} / hour</span>
        </div>
      </div>
      <div class="actions" style="margin-top:16px;justify-content:space-between">
        <span>Selected: <b id="addon-total">£0</b></span>
        <button class="btn" type="submit">Ask for these</button>
      </div>
    </form>
  </div>

  <aside class="col-side">
    <section class="card" aria-labelledby="h-sum" style="position:sticky;top:20px">
      <h2 id="h-sum" style="margin-bottom:12px">Your selection</h2>
      <dl class="facts">
        <dt>Plate styles</dt><dd><span data-count="styles[]">0</span> of {{ count($catalogue['styles']) }}</dd>
        <dt>Sizes</dt><dd><span data-count="sizes[]">0</span> of {{ count($catalogue['sizes']) }}</dd>
        <dt>Badges and extras</dt><dd><span data-count="extras[]">0</span> of {{ count($catalogue['extras']) }}</dd>
      </dl>
      <button class="btn block" type="submit" form="products-form" style="margin-top:16px">Save products</button>
    </section>
  </aside>
</div>

@push('scripts')
<script>
(function () {
  function count() {
    document.querySelectorAll('[data-count]').forEach(function (el) {
      el.textContent = document.querySelectorAll('input[name="' + el.dataset.count + '"]:checked').length;
    });
    var t = 0;
    document.querySelectorAll('input[name="addons[]"]:checked:not(:disabled)').forEach(function (i) { t += +i.dataset.price; });
    document.getElementById('addon-total').textContent = '£' + (t / 100).toLocaleString('en-GB');
  }
  document.addEventListener('change', count);
  count();
})();
</script>
@endpush
@endsection
