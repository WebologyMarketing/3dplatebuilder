@extends('layouts.account')
@section('title', 'Products')

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Products</p>
    <h1>Products</h1>
    <p class="sub">Plate products open your builder on a style, so customers start from that product. Simple products are anything else with a fixed price.</p>
  </div>
  <div class="actions" style="margin:0">
    <a class="btn" href="{{ route('products.create') }}">+ Plate product</a>
    <a class="btn ghost" href="{{ route('products.create', ['type' => 'simple']) }}">+ Simple product</a>
  </div>
</header>

@include('account.partials.publish')

@if ($items->isEmpty())
  <section class="card">
    <h2>No products yet</h2>
    <p class="lead" style="margin:0">Your builder is already on sale at its own prices. Add products here if you'd like a page for, say, your 4D plates or a keyring.</p>
  </section>
@else
  <div class="plist">
    @foreach ($items as $i => $p)
      <div class="pitem">
        <span class="thumb" @if ($p->images) style="background-image:url('{{ route('products.image', [$p, 0]) }}')" @endif>@unless ($p->images) No image @endunless</span>
        <span class="meta">
          <b>{{ $p->name }}</b>
          <span>
            {{ $p->isPlate() ? 'Plate product · '.($c['styles'][$p->style]['label'] ?? $p->style).($p->size ? ', '.strtolower($c['sizes'][$p->size]['label'] ?? $p->size) : '') : 'Simple product' }}
            · {{ $p->priceLabel($b) }}
            @if ($p->isPlate() && ! ($b['styles'][$p->style]['on'] ?? false)) · <b style="display:inline;color:var(--bad)">style switched off in your builder</b>@endif
            @unless ($p->active) · <span class="pill grey">Hidden</span>@endunless
          </span>
        </span>
        <span class="acts">
          <form method="post" action="{{ route('products.move', $p) }}">@csrf<input type="hidden" name="dir" value="up"><button class="iconbtn" type="submit" @disabled($i === 0) aria-label="Move {{ $p->name }} up">↑</button></form>
          <form method="post" action="{{ route('products.move', $p) }}">@csrf<input type="hidden" name="dir" value="down"><button class="iconbtn" type="submit" @disabled($i === $items->count() - 1) aria-label="Move {{ $p->name }} down">↓</button></form>
          <a class="btn ghost" href="{{ route('products.edit', $p) }}">Edit</a>
        </span>
      </div>
    @endforeach
  </div>
@endif
@endsection
