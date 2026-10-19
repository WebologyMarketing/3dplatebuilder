@extends('layouts.base')

@php
  $here = fn ($name) => request()->routeIs($name) ? 'page' : 'false';
  $counts = [
    'shops' => \App\Models\Shop::count(),
    'overdue' => \App\Models\Payment::overdue()->count(),
    'tickets' => \App\Models\Ticket::whereIn('status', ['new', 'waiting_on_us'])->count(),
    'docs' => \App\Models\Document::where('status', 'pending')->count(),
    'addons' => \App\Models\AddonRequest::where('status', 'requested')->count(),
  ];
@endphp

@section('body')
<div class="shell">
  <aside class="side">
    <a class="brand" href="{{ route('admin.dashboard') }}"><span class="mark" aria-hidden="true">3D</span>Admin</a>
    <nav aria-label="Admin">
      <a href="{{ route('admin.dashboard') }}" aria-current="{{ $here('admin.dashboard') }}">Dashboard</a>
      <a href="{{ route('admin.shops') }}" aria-current="{{ $here('admin.shops*') }}">Customers <span class="n">{{ $counts['shops'] }}</span></a>
      <a href="{{ route('admin.payments') }}" aria-current="{{ $here('admin.payments') }}">Payments @if ($counts['overdue'] + $counts['addons'])<span class="n {{ $counts['overdue'] ? 'bad' : '' }}">{{ $counts['overdue'] + $counts['addons'] }}</span>@endif</a>
      <a href="{{ route('admin.tickets') }}" aria-current="{{ $here('admin.tickets*') }}">Tickets @if ($counts['tickets'])<span class="n">{{ $counts['tickets'] }}</span>@endif</a>
      <a href="{{ route('admin.documents') }}" aria-current="{{ $here('admin.documents') }}">Documents @if ($counts['docs'])<span class="n">{{ $counts['docs'] }}</span>@endif</a>
      <a href="{{ route('admin.releases') }}" aria-current="{{ $here('admin.releases') }}">Plugin updates</a>
    </nav>
    <div class="who">
      <div><b>{{ auth()->user()->name }}</b>Admin</div>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="linkbtn" type="submit">Sign out</button></form>
    </div>
  </aside>
  <main class="main" id="main">
    <div class="wrap">
      @if (session('status'))<p class="notice good" role="status">{{ session('status') }}</p>@endif
      @if (session('error'))<p class="notice bad" role="alert">{{ session('error') }}</p>@endif
      @if ($errors->any())<div class="notice bad" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
      @yield('content')
    </div>
  </main>
</div>
@endsection
