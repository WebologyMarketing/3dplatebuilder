@extends('layouts.account')
@section('title', 'Your builder')

@php
  $tickSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F7D333" stroke-width="3.5" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>';
  $val = fn ($key, $default) => old($key, $default);
  $custom = old('custom', $b['custom']);
  $groups = [
    'sizes' => ['Sizes', 'Standard is always on. Other sizes add this much per plate; motorbike and trailer plates are one printed plate at this price.'],
    'badges' => ['Badges', 'Charged once per order line. Motorbike and trailer plates get half.'],
    'borders' => ['Borders', 'Charged once per order line.'],
    'extras' => ['Fixings and extras', 'Offered in the basket step. Charged once per order line.'],
  ];
@endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Your builder</p>
    <h1>Your builder and prices</h1>
    <p class="sub">Switch on what you sell and set your prices. {{ $shop->canAddProducts() ? 'Then add products, if you like, and publish.' : 'On Starter the builder is your product: customers choose everything inside it.' }}</p>
  </div>
</header>

@include('account.partials.publish')

@if ($errors->any())<div class="notice bad" role="alert">@foreach ($errors->unique() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="post" action="{{ route('builder') }}" class="stack" id="builder-form">
  @csrf

  <section class="card" aria-labelledby="h-st">
    <h2 id="h-st">Plate styles</h2>
    <p class="lead">Your price for a pair (front and rear) and for one plate, standard size. Printed is always on: motorbike, trailer and import plates use it.</p>
    <div class="opt-head" aria-hidden="true"><span>Style</span><span class="prices"><span>Pair</span><span>One plate</span></span></div>
    <div class="opts">
      @foreach ($c['styles'] as $id => $d)
        @php $always = ! empty($d['always']); $on = $always || (bool) $val("styles.$id.on", $b['styles'][$id]['on']); @endphp
        <div class="opt two">
          <label class="tick {{ $always ? 'always' : '' }}">
            @if ($always)<input type="hidden" name="styles[{{ $id }}][on]" value="1"><input type="checkbox" checked disabled>
            @else<input type="hidden" name="styles[{{ $id }}][on]" value="0"><input type="checkbox" name="styles[{{ $id }}][on]" value="1" @checked($on)>@endif
            <span class="box">{!! $tickSvg !!}</span>
            <span>{{ $d['label'] }}@if ($always)<span class="sub2">Always on</span>@endif</span>
          </label>
          <span class="prices">
            <label class="money">Pair £<input name="styles[{{ $id }}][pair]" value="{{ $val("styles.$id.pair", $b['styles'][$id]['pair']) }}" inputmode="decimal" aria-label="{{ $d['label'] }}, price for a pair"></label>
            <label class="money">One £<input name="styles[{{ $id }}][single]" value="{{ $val("styles.$id.single", $b['styles'][$id]['single']) }}" inputmode="decimal" aria-label="{{ $d['label'] }}, price for one plate"></label>
          </span>
        </div>
      @endforeach
    </div>
  </section>

  @foreach ($groups as $group => [$title, $lead])
    <section class="card" aria-labelledby="h-{{ $group }}">
      <h2 id="h-{{ $group }}">{{ $title }}</h2>
      <p class="lead">{{ $lead }}</p>
      <div class="opts">
        @foreach ($c[$group] as $id => $d)
          @php $always = ! empty($d['always']); $on = $always || (bool) $val("$group.$id.on", $b[$group][$id]['on']); @endphp
          <div class="opt">
            <label class="tick {{ $always ? 'always' : '' }}">
              @if ($always)<input type="hidden" name="{{ $group }}[{{ $id }}][on]" value="1"><input type="checkbox" checked disabled>
              @else<input type="hidden" name="{{ $group }}[{{ $id }}][on]" value="0"><input type="checkbox" name="{{ $group }}[{{ $id }}][on]" value="1" @checked($on)>@endif
              <span class="box">{!! $tickSvg !!}</span>
              <span>{{ $d['label'] }}@if (! empty($d['mm']))<span class="sub2">{{ $d['mm'] }}{{ $always ? ' · always on' : '' }}{{ ($d['type'] ?? '') === 'flat' ? ' · fixed price' : '' }}</span>@endif</span>
            </label>
            <span class="prices">
              @if ($always && (float) $d['price'] === 0.0)
                <span class="money" style="width:112px">No extra</span><input type="hidden" name="{{ $group }}[{{ $id }}][price]" value="0">
              @else
                <label class="money">{{ ($d['type'] ?? '') === 'surcharge' ? '+£' : '£' }}<input name="{{ $group }}[{{ $id }}][price]" value="{{ $val("$group.$id.price", $b[$group][$id]['price']) }}" inputmode="decimal" aria-label="{{ $d['label'] }}, price"></label>
              @endif
            </span>
          </div>
        @endforeach
      </div>
    </section>
  @endforeach

  <section class="card" aria-labelledby="h-own">
    <h2 id="h-own">Your own add-ons</h2>
    <p class="lead">Anything else you'd like to offer in the basket, like a gift box or fitting kit. Charged once per order line. Clear the name to remove one.</p>
    <div class="opts" id="custom-rows">
      @foreach ($custom as $i => $a)
        <div class="custom-row">
          <input type="hidden" name="custom[{{ $i }}][id]" value="{{ $a['id'] ?? '' }}">
          <label class="onoff"><input type="hidden" name="custom[{{ $i }}][on]" value="0"><input type="checkbox" name="custom[{{ $i }}][on]" value="1" @checked(! empty($a['on']))> On</label>
          <input class="input" name="custom[{{ $i }}][name]" value="{{ $a['name'] ?? '' }}" maxlength="80" placeholder="Add-on name" aria-label="Add-on name">
          <label class="money">£<input name="custom[{{ $i }}][price]" value="{{ $a['price'] ?? '' }}" inputmode="decimal" aria-label="Add-on price"></label>
        </div>
      @endforeach
    </div>
    <template id="custom-template">
      <div class="custom-row">
        <input type="hidden" name="custom[__][id]" value="">
        <label class="onoff"><input type="hidden" name="custom[__][on]" value="0"><input type="checkbox" name="custom[__][on]" value="1" checked> On</label>
        <input class="input" name="custom[__][name]" maxlength="80" placeholder="Add-on name, like Gift box" aria-label="Add-on name">
        <label class="money">£<input name="custom[__][price]" inputmode="decimal" placeholder="0.00" aria-label="Add-on price"></label>
      </div>
    </template>
    <p style="margin-top:12px"><button class="btn ghost" type="button" id="add-custom" data-max="{{ config('portal.max_custom_addons') }}">+ Add an add-on</button></p>
  </section>

  <section class="card" aria-labelledby="h-fit">
    <h2 id="h-fit">Fitting</h2>
    <p class="lead">Offered when a customer collects from you. Added at checkout.</p>
    <label class="money">Fitting £<input name="fitting" value="{{ $val('fitting', $b['fitting']) }}" inputmode="decimal" aria-label="Fitting price"></label>
  </section>

  <div class="actions savebar"><button class="btn" type="submit">Save</button><span class="muted">Saving doesn't change your website. Publish when you're ready.</span></div>
</form>

<form method="post" action="{{ route('products.addons') }}" class="card" aria-labelledby="h-ad" id="extra-work" style="margin-top:28px">
  @csrf
  <h2 id="h-ad">Extra work from us</h2>
  <p class="lead">One-off work. Once we've confirmed it, we bill it to the card you signed up with. No VAT to add.</p>
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

@push('scripts')
<script>
(function () {
  var rows = document.getElementById('custom-rows');
  var add = document.getElementById('add-custom');
  var tpl = document.getElementById('custom-template');
  var n = rows.children.length;
  function sync() { add.hidden = rows.children.length >= +add.dataset.max; }
  function addRow(focus) {
    rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__/g, String(n++)));
    if (focus) rows.lastElementChild.querySelector('input.input').focus();
    sync();
  }
  add.addEventListener('click', function () { addRow(true); });
  if (!rows.children.length) addRow(false);
  sync();

  function total() {
    var t = 0;
    document.querySelectorAll('input[name="addons[]"]:checked:not(:disabled)').forEach(function (i) { t += +i.dataset.price; });
    document.getElementById('addon-total').textContent = '£' + (t / 100).toLocaleString('en-GB');
  }
  document.addEventListener('change', total);
  total();
})();
</script>
@endpush
@endsection
