@extends('layouts.base')

@php
  $me = auth()->user();
  $shop = $me->shop;
  $paid = (bool) $shop->setup_paid_at;
  $openTickets = $shop->tickets()->where('status', 'waiting_on_them')->count();
  $here = fn ($name) => request()->routeIs($name) ? 'page' : 'false';
@endphp

@section('body')
<div class="shell">
  <aside class="side">
    <a class="brand" href="{{ route('dashboard') }}"><span class="mark" aria-hidden="true">3D</span>3dPlateBuilder</a>
    <nav aria-label="Your account">
      <a href="{{ route('dashboard') }}" aria-current="{{ $here('dashboard') }}">Overview</a>
      @if ($paid)
        <a href="{{ route('details') }}" aria-current="{{ $here('details') }}">Details and brand</a>
        <a href="{{ route('builder') }}" aria-current="{{ $here('builder') }}">Your builder</a>
        @if ($shop->canAddProducts())<a href="{{ route('products') }}" aria-current="{{ $here('products*') }}">Products</a>@endif
      @endif
      <a href="{{ route('billing') }}" aria-current="{{ $here('billing') }}">Billing</a>
      <a href="{{ route('support') }}" aria-current="{{ $here('support*') }}">Support @if ($openTickets)<span class="n">{{ $openTickets }}</span>@endif</a>
    </nav>
    <div class="who">
      <div><b>{{ $shop->name }}</b>{{ $shop->planName() }} plan</div>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="linkbtn" type="submit">Sign out</button></form>
    </div>
  </aside>
  <main class="main" id="main">
    <div class="wrap">
      @if (session('status'))<p class="notice good" role="status">{{ session('status') }}</p>@endif
      @if (session('error'))<p class="notice bad" role="alert">{{ session('error') }}</p>@endif
      @yield('content')
    </div>
  </main>
</div>
@endsection
