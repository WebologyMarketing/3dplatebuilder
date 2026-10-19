@extends('layouts.account')
@section('title', 'Details and brand')

@php
  $accent = old('accent_colour', $shop->accent_colour ?: '#E8452C');
  $dark = old('dark_colour', $shop->dark_colour ?: '#16191C');
  $font = old('heading_font', $shop->heading_font ?: 'Archivo');
  $rnpsPill = ['approved' => ['good', 'Approved'], 'pending' => ['warn', 'Being checked'], 'rejected' => ['bad', 'Not accepted']];
@endphp

@push('fonts')
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@700&family=Barlow+Condensed:wght@700&family=Montserrat:wght@700&family=Poppins:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Details and brand</p>
    <h1>Your details and brand</h1>
    <p class="sub">Fill in what you can now and save. You can come back to the rest.</p>
  </div>
</header>

@if ($errors->any())<div class="notice bad" role="alert">Please check the highlighted fields.</div>@endif

<div class="cols">
  <div class="col-main">
    <form method="post" action="{{ route('details') }}" class="card" aria-labelledby="h-co">
      @csrf
      <h2 id="h-co">Company</h2>
      <p class="lead">For your invoices and your shop's legal pages.</p>
      <div class="grid-2">
        <label class="field">Registered company name<input name="legal_name" value="{{ old('legal_name', $shop->legal_name) }}" autocomplete="organization">@error('legal_name')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Trading name<input name="trading_name" value="{{ old('trading_name', $shop->trading_name) }}">@error('trading_name')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Company number<input name="company_number" value="{{ old('company_number', $shop->company_number) }}">@error('company_number')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">VAT number <small>If you're registered</small><input name="vat_number" value="{{ old('vat_number', $shop->vat_number) }}">@error('vat_number')<span class="err">{{ $message }}</span>@enderror</label>
      </div>
      <label class="field" style="margin-top:16px">Registered address<textarea name="registered_address" autocomplete="street-address">{{ old('registered_address', $shop->registered_address) }}</textarea>@error('registered_address')<span class="err">{{ $message }}</span>@enderror</label>

      <h2 id="contact" style="margin-top:28px">Contact and supplier details</h2>
      <p class="lead">What your customers see, and what goes on each plate.</p>
      <div class="grid-2">
        <label class="field">Email shown on your shop<input type="email" name="contact_email" value="{{ old('contact_email', $shop->contact_email) }}">@error('contact_email')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Your domain<input name="domain" value="{{ old('domain', $shop->domain) }}" placeholder="yourplates.co.uk">@error('domain')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Supplier name on plates <small>Printed on every plate, as BS AU 145e asks</small><input name="supplier_name" value="{{ old('supplier_name', $shop->supplier_name) }}" maxlength="60">@error('supplier_name')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Supplier postcode on plates<input name="supplier_postcode" value="{{ old('supplier_postcode', $shop->supplier_postcode) }}" autocomplete="postal-code">@error('supplier_postcode')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">DVLA RNPS registration number<input name="rnps_number" value="{{ old('rnps_number', $shop->rnps_number) }}">@error('rnps_number')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="field">Collection and fitting address <small>Leave blank if you only post plates</small><textarea name="collection_address" style="min-height:46px">{{ old('collection_address', $shop->collection_address) }}</textarea>@error('collection_address')<span class="err">{{ $message }}</span>@enderror</label>
      </div>
      <div class="actions" style="margin-top:20px"><button class="btn" type="submit">Save details</button></div>
    </form>

    <section class="card" id="logo" aria-labelledby="h-logo">
      <h2 id="h-logo">Logo</h2>
      <p class="lead">For your shop header and emails. SVG is best; a PNG at least 1000px wide also works.</p>
      @if ($shop->logo_path || $shop->logo_white_path)
        <div class="grid-2" style="margin-bottom:16px">
          @if ($shop->logo_path)<div><p class="small muted" style="margin-bottom:6px">Your logo</p><div class="logo-box"><img src="{{ route('details.file', 'logo') }}" alt="Your logo"></div></div>@endif
          @if ($shop->logo_white_path)<div><p class="small muted" style="margin-bottom:6px">White version</p><div class="logo-box" style="background:{{ $dark }}"><img src="{{ route('details.file', 'logo-white') }}" alt="Your white logo"></div></div>@endif
        </div>
      @endif
      <form method="post" action="{{ route('details.logo') }}" enctype="multipart/form-data" class="stack">
        @csrf
        <div class="grid-2">
          <label class="drop">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#5b6065" stroke-width="2" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>
            <b>{{ $shop->logo_path ? 'Replace your logo' : 'Your logo' }}</b>
            <input type="file" name="logo" accept=".svg,.png,image/svg+xml,image/png">
          </label>
          <label class="drop">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#5b6065" stroke-width="2" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>
            <b>White version <span class="muted" style="font-weight:400">(optional)</span></b>
            <input type="file" name="logo_white" accept=".svg,.png,image/svg+xml,image/png">
          </label>
        </div>
        @error('logo')<p class="err">{{ $message }}</p>@enderror
        @error('logo_white')<p class="err">{{ $message }}</p>@enderror
        <div><button class="btn" type="submit">Upload</button></div>
      </form>
    </section>

    <form method="post" action="{{ route('details') }}" class="card" id="brand" aria-labelledby="h-col">
      @csrf
      <h2 id="h-col">Colours and fonts</h2>
      <p class="lead">Pick a main colour and a dark colour. The preview tells you if button text would be hard to read.</p>
      <div class="stack">
        <div>
          <p class="field" style="margin-bottom:8px">Main colour <small>Buttons and highlights</small></p>
          <div class="swatches">
            @foreach (['Red' => '#E8452C', 'Yellow' => '#F7D333', 'Blue' => '#1F5FD6', 'Green' => '#1E8E4E', 'Orange' => '#F28C1B', 'Purple' => '#6B3FD1'] as $n => $c)
              <button type="button" class="swatch" data-set="accent" data-colour="{{ $c }}" aria-label="{{ $n }}" style="border:0;padding:0;background:none;cursor:pointer"><span style="background:{{ $c }}"></span></button>
            @endforeach
            <label class="field" style="flex-direction:row;align-items:center;gap:8px;font-weight:500">Or choose
              <input type="color" name="accent_colour" id="accent" value="{{ strtolower($accent) }}" style="width:56px;min-height:44px;padding:4px">
            </label>
          </div>
          @error('accent_colour')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
          <p class="field" style="margin-bottom:8px">Dark colour <small>Header and footer</small></p>
          <div class="swatches">
            @foreach (['Charcoal' => '#16191C', 'Navy' => '#0F1F3D', 'Forest' => '#10291C', 'Plum' => '#2A1330'] as $n => $c)
              <button type="button" class="swatch" data-set="dark" data-colour="{{ $c }}" aria-label="{{ $n }}" style="border:0;padding:0;background:none;cursor:pointer"><span style="background:{{ $c }}"></span></button>
            @endforeach
            <label class="field" style="flex-direction:row;align-items:center;gap:8px;font-weight:500">Or choose
              <input type="color" name="dark_colour" id="dark" value="{{ strtolower($dark) }}" style="width:56px;min-height:44px;padding:4px">
            </label>
          </div>
          @error('dark_colour')<p class="err">{{ $message }}</p>@enderror
        </div>
        <fieldset style="border:0;margin:0;padding:0">
          <legend class="field" style="margin-bottom:8px">Heading font</legend>
          <div class="fonts">
            @foreach ($fonts as $f)
              <label class="fontopt"><input type="radio" name="heading_font" value="{{ $f }}" @checked($font === $f)><b style="font-family:'{{ $f }}',sans-serif">Aa</b>{{ $f }}</label>
            @endforeach
          </div>
        </fieldset>
        <div><button class="btn" type="submit">Save colours and font</button></div>
      </div>
    </form>

    <section class="card" aria-labelledby="h-fonts">
      <h2 id="h-fonts" style="font-size:17px">Your own font <span class="muted" style="font-weight:400">(optional)</span></h2>
      <p class="lead">If your brand uses a font that isn't above, upload the web font files. You'll need a licence that allows use on a website.</p>
      @if ($shop->font_files)
        <ul class="small" style="margin:0 0 14px;padding-left:18px">@foreach ($shop->font_files as $ff)<li>{{ $ff['name'] }}</li>@endforeach</ul>
      @endif
      <form method="post" action="{{ route('details.fonts') }}" enctype="multipart/form-data" class="actions">
        @csrf
        <input type="file" name="fonts[]" multiple accept=".woff2,.woff,.ttf,.otf" aria-label="Font files">
        <button class="btn ghost sm" type="submit">Upload fonts</button>
      </form>
      @error('fonts')<p class="err">{{ $message }}</p>@enderror
      @error('fonts.*')<p class="err">{{ $message }}</p>@enderror
    </section>

    <section class="card" id="docs" aria-labelledby="h-doc">
      <h2 id="h-doc">RNPS documents</h2>
      <p class="lead">Proof of your DVLA RNPS registration. We check it before your shop goes live.</p>
      <div class="row" style="flex-wrap:wrap;border:1px solid var(--line);border-radius:14px;padding:14px 16px">
        <span><b>RNPS registration</b><br><span class="muted small">{{ $rnps ? $rnps->original_name.', uploaded '.$rnps->created_at->format('j M') : 'PDF or photo' }}</span></span>
        @php [$cls, $label] = $rnpsPill[$rnps?->status] ?? ['grey', 'Not uploaded']; @endphp
        <span class="pill {{ $cls }}">{{ $label }}</span>
      </div>
      @if ($rnps?->status === 'rejected' && $rnps->note)<p class="notice bad" style="margin-top:12px">{{ $rnps->note }}</p>@endif
      @if ($rnps?->status !== 'approved')
        <form method="post" action="{{ route('details.document') }}" enctype="multipart/form-data" class="actions" style="margin-top:14px">
          @csrf
          <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" aria-label="RNPS document" required>
          <button class="btn ghost sm" type="submit">Upload</button>
        </form>
        @error('document')<p class="err">{{ $message }}</p>@enderror
      @endif
    </section>
  </div>

  <aside class="col-side">
    <section class="card preview" aria-labelledby="h-prev" style="position:sticky;top:20px">
      <h2 id="h-prev" style="font-size:16px;padding:18px 20px 12px">Preview</h2>
      <div class="top" id="pv-top" style="background:{{ $dark }}"><span id="pv-name" style="font-weight:700;font-size:18px;font-family:'{{ $font }}',sans-serif">{{ strtoupper($shop->trading_name ?: $shop->name) }}</span><span class="small">Basket</span></div>
      <div class="body">
        <div id="pv-head" style="font-weight:700;font-size:24px;line-height:1.1;font-family:'{{ $font }}',sans-serif">Design your plates</div>
        <div class="plate">YR25 ABC</div>
        <div class="cta" id="pv-cta" style="background:{{ $accent }}">ADD TO BASKET</div>
        <div class="small" id="pv-note" style="font-weight:600" aria-live="polite"></div>
      </div>
    </section>
  </aside>
</div>

@push('scripts')
<script>
(function () {
  var accent = document.getElementById('accent'), dark = document.getElementById('dark');
  function lum(hex) {
    var c = [1, 3, 5].map(function (i) { var v = parseInt(hex.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function paint() {
    var L = lum(accent.value), w = 1.05 / (L + 0.05), b = (L + 0.05) / 0.05, r = Math.max(w, b);
    var cta = document.getElementById('pv-cta');
    cta.style.background = accent.value; cta.style.color = w >= b ? '#fff' : '#111';
    document.getElementById('pv-top').style.background = dark.value;
    var note = document.getElementById('pv-note');
    note.textContent = r >= 4.5 ? 'Button text is easy to read (' + r.toFixed(1) + ':1).' : 'Button text may be hard to read (' + r.toFixed(1) + ':1). Try a darker or lighter shade.';
    note.style.color = r >= 4.5 ? '#1d6b38' : '#a33a12';
  }
  document.querySelectorAll('[data-set]').forEach(function (btn) {
    btn.addEventListener('click', function () { (btn.dataset.set === 'accent' ? accent : dark).value = btn.dataset.colour.toLowerCase(); paint(); });
  });
  accent.addEventListener('input', paint); dark.addEventListener('input', paint);
  document.querySelectorAll('input[name=heading_font]').forEach(function (r) {
    r.addEventListener('change', function () { ['pv-name', 'pv-head'].forEach(function (id) { document.getElementById(id).style.fontFamily = "'" + r.value + "', sans-serif"; }); });
  });
  paint();
})();
</script>
@endpush
@endsection
