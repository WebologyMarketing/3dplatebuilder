@extends('layouts.admin')
@section('title', 'Dashboard')

@php
  $hour = now()->hour;
  $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
  $planNames = collect(config('portal.plans'))->map->name->put('outright', 'Outright');
  $byPlanText = $byPlan->map(fn ($n, $p) => $n.' '.($planNames[$p] ?? $p))->implode(' · ') ?: 'None yet';
  $stageDots = ['not_paid' => '#9aa0a6', 'onboarding' => '#F7D333', 'in_build' => '#1d4aa8', 'review' => '#6b21a8', 'live' => '#1d6b38', 'cancelled' => '#cfd0d4'];
@endphp

@section('content')
<header class="head">
  <h1>{{ $greet }}, {{ strtok(auth()->user()->name, ' ') }}</h1>
  <div class="actions">
    <a class="btn ghost" href="https://dashboard.stripe.com" target="_blank" rel="noopener">Stripe dashboard</a>
    <a class="btn" href="{{ route('admin.shops') }}">All customers</a>
  </div>
</header>

<section class="kpis" aria-label="Key numbers">
  <div class="card kpi"><div class="l">Monthly recurring</div><div class="v">{{ money($mrr) }}</div><div class="s">{{ money($mrr * 12) }} a year</div></div>
  <div class="card kpi"><div class="l">Paying shops</div><div class="v">{{ $payingCount }}</div><div class="s">{{ $byPlanText }}</div></div>
  <div class="card kpi"><div class="l">Set-up fees this month</div><div class="v">{{ money($setupPence) }}</div><div class="s">{{ $setupCount }} new {{ \Illuminate\Support\Str::plural('shop', $setupCount) }} · {{ $signupsThisMonth }} sign-ups</div></div>
  <div class="card kpi {{ $overduePence ? 'bad' : '' }}"><div class="l">Overdue</div><div class="v">{{ money($overduePence) }}</div><div class="s">@if ($overdue->isEmpty()) Nothing overdue @else {{ $overdue->count() }} {{ \Illuminate\Support\Str::plural('invoice', $overdue->count()) }}, oldest {{ $overdue->first()->due_at->diffInDays(now(), true) >= 1 ? (int) $overdue->first()->due_at->diffInDays(now(), true).' days' : 'today' }} @endif</div></div>
  <div class="card kpi"><div class="l">Cancelled, last 12 months</div><div class="v">{{ $cancelled }}</div><div class="s">Churn {{ $churn }}%</div></div>
</section>

<div class="cols">
  <section class="card" aria-labelledby="h-mrr" style="flex:2 1 520px;min-width:0">
    <div class="card-head" style="margin:0"><h2 id="h-mrr">Monthly recurring income</h2><span class="small muted">Last 12 months</span></div>
    <div class="chart" role="img" aria-label="Monthly recurring income for the last 12 months, now {{ money($mrr) }}">
      @foreach ($history as $h)
        <div title="{{ $h['month'] }}: {{ money($h['pence']) }}"><i style="height:{{ max(2, round($h['pence'] / $historyMax * 100)) }}%"></i><span>{{ $h['month'] }}</span></div>
      @endforeach
    </div>
  </section>
  <section class="card" aria-labelledby="h-stage" style="flex:1 1 280px">
    <h2 id="h-stage">Shops by stage</h2>
    <div class="rows" style="margin-top:8px">
      @foreach ($stages as $key => $s)
        <a class="row" href="{{ route('admin.shops', ['show' => in_array($key, ['live', 'cancelled']) ? $key : 'setup']) }}" style="text-decoration:none">
          <span style="display:flex;align-items:center;gap:10px"><span style="width:10px;height:10px;border-radius:3px;background:{{ $stageDots[$key] }}"></span>{{ $s['label'] }}</span><b>{{ $s['count'] }}</b>
        </a>
      @endforeach
    </div>
  </section>
</div>

<div class="cols">
  <section class="card" aria-labelledby="h-pay" style="flex:1 1 330px">
    <div class="card-head"><h2 id="h-pay">Payments to chase</h2><a class="small" href="{{ route('admin.payments') }}">All payments</a></div>
    <div class="rows">
      @forelse ($overdue as $p)
        <div class="row"><span><b>{{ $p->shop->name ?? 'Unknown customer' }}</b><br><span class="muted">{{ $p->description }} · due {{ $p->due_at->format('j M') }} · {{ $p->attempt_count }} {{ \Illuminate\Support\Str::plural('attempt', $p->attempt_count) }}</span></span><span style="text-align:right"><b style="color:var(--bad)">{{ money($p->amount_pence) }}</b>@if ($p->hosted_url)<br><a class="small" href="{{ $p->hosted_url }}" target="_blank" rel="noopener">Invoice</a>@endif</span></div>
      @empty
        <p class="empty">Nothing overdue.</p>
      @endforelse
    </div>
    @if ($recentPaid->isNotEmpty())
      <h3 style="margin:16px 0 4px;font-size:15px">Recently paid</h3>
      <div class="rows">
        @foreach ($recentPaid as $p)
          <div class="row"><span>{{ $p->shop->name ?? 'Unknown' }} <span class="muted">· {{ $p->paid_at?->format('j M') }}</span></span><b>{{ money($p->amount_paid_pence) }}</b></div>
        @endforeach
      </div>
    @endif
  </section>

  <section class="card" aria-labelledby="h-tic" style="flex:1 1 330px">
    <div class="card-head"><h2 id="h-tic">Tickets waiting on you</h2><a class="small" href="{{ route('admin.tickets') }}">All tickets</a></div>
    <div class="rows">
      @forelse ($tickets as $t)
        <a class="row" href="{{ route('admin.tickets.show', $t) }}" style="text-decoration:none"><span><b>{{ $t->subject }}</b><br><span class="muted">{{ $t->shop->name }} · {{ ($t->last_reply_at ?? $t->created_at)->diffForHumans() }}</span></span><span class="pill {{ $t->status === 'new' ? 'warn' : 'info' }}">{{ $t->statusLabel(true) }}</span></a>
      @empty
        <p class="empty">You're all caught up.</p>
      @endforelse
    </div>
  </section>

  <section class="card" aria-labelledby="h-doc" style="flex:1 1 300px">
    <div class="card-head"><h2 id="h-doc">Documents to check</h2><a class="small" href="{{ route('admin.documents') }}">All documents</a></div>
    <div class="rows">
      @forelse ($documents as $d)
        <a class="row" href="{{ route('admin.documents') }}#doc-{{ $d->id }}" style="text-decoration:none"><span><b>{{ $d->shop->name }}</b><br><span class="muted">RNPS registration · {{ $d->created_at->diffForHumans() }}</span></span><span class="pill warn">Check</span></a>
      @empty
        <p class="empty">Nothing to check.</p>
      @endforelse
    </div>
  </section>
</div>
@endsection
