@extends('layouts.account')
@section('title', 'Overview')

@php
  $total = count($steps);
  $stepUrl = fn ($s) => route($s['route']).(isset($s['anchor']) ? '#'.$s['anchor'] : '');
  $stagePill = ['not_paid' => 'grey', 'onboarding' => 'warn', 'in_build' => 'info', 'review' => 'purple', 'live' => 'good', 'cancelled' => 'grey'][$shop->stage] ?? 'grey';
@endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb">Welcome, {{ auth()->user()->name }}</p>
    <h1>{{ match ($shop->stage) {
      'not_paid' => 'One step to get started',
      'onboarding' => 'Let\'s get your shop ready',
      'in_build' => 'We\'re building your shop',
      'review' => 'Your shop is ready to check',
      'live' => 'Your shop is live',
      default => 'Your account',
    } }}</h1>
  </div>
  <a class="btn ghost" href="{{ route('support') }}">Ask us something</a>
</header>

@if (! $shop->setup_paid_at)
  <section class="card dark" aria-labelledby="pay">
    <h2 id="pay" style="font-size:22px">Pay your set-up fee to begin</h2>
    <p class="muted" style="margin:6px 0 18px">{{ $shop->planName() }} plan: {{ money($shop->planConfig()['setup']) }} set-up, then {{ money($shop->planConfig()['monthly']) }} a month. No VAT to add. Once it's paid, you can add your details and products.</p>
    <a class="btn yellow" href="{{ route('pay') }}">Go to secure payment →</a>
  </section>
@elseif ($shop->stage === 'onboarding')
  <section class="card dark" aria-labelledby="prog" style="display:flex;flex-wrap:wrap;gap:22px;align-items:center;justify-content:space-between">
    <div style="flex:1 1 380px">
      <h2 id="prog" style="font-size:22px">{{ $done }} of {{ $total }} steps done</h2>
      <p class="muted" style="margin-top:6px">Finish these and we'll start building your shop. Everything saves as you go.</p>
      <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}" aria-label="Set-up progress"><span style="width:{{ round($done / $total * 100) }}%"></span></div>
    </div>
    @if ($next)<a class="btn yellow" href="{{ $stepUrl($next) }}">Next: {{ strtolower($next['title']) }} →</a>@endif
  </section>
@endif

<div class="cols">
  <section class="col-main" aria-labelledby="todo">
    <h2 id="todo">{{ $shop->stage === 'onboarding' || $shop->stage === 'not_paid' ? 'What to do next' : 'Your set-up' }}</h2>
    <ol class="steps">
      @foreach ($steps as $i => $s)
        @php
          $status = $s['done'] ? 'Done' : (! empty($s['started']) ? 'Started' : 'To do');
          $pill = $s['done'] ? 'good' : (! empty($s['started']) ? 'warn' : 'grey');
          $locked = ! $shop->setup_paid_at && $s['key'] !== 'pay';
        @endphp
        <li>
          @if ($locked)
            <div class="step" style="opacity:.6">
          @else
            <a class="step" href="{{ $s['key'] === 'pay' && ! $s['done'] ? route('pay') : $stepUrl($s) }}">
          @endif
            <span class="dot {{ $s['done'] ? 'done' : '' }}" aria-hidden="true">@if ($s['done'])<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5 9-10"/></svg>@else{{ $i + 1 }}@endif</span>
            <span class="t"><b>{{ $s['title'] }}</b><span>{{ $s['note'] }}</span></span>
            <span class="pill {{ $pill }}">{{ $status }}</span>
          @if ($locked)</div>@else</a>@endif
        </li>
      @endforeach
    </ol>

    <div class="card">
      <h3 style="margin-bottom:14px">Then we take it from here</h3>
      <ol class="then">
        <li><b>1. We build</b>Your shop, builder and prices, set up for you.</li>
        <li><b>2. You check</b>A preview link to try, and send us any changes.</li>
        <li><b>3. You go live</b>On your own domain, taking orders.</li>
      </ol>
    </div>
  </section>

  <aside class="col-side">
    <section class="card" aria-labelledby="h-site">
      <h2 id="h-site" style="font-size:17px;margin-bottom:10px">Your website</h2>
      <span class="pill {{ $stagePill }}">{{ $shop->stageLabel() }}</span>
      <dl class="facts" style="margin-top:14px">
        <dt>Domain</dt><dd>{{ $shop->domain ?: 'Not set yet' }}</dd>
        <dt>Preview</dt><dd>@if ($shop->preview_url)<a href="{{ $shop->preview_url }}" target="_blank" rel="noopener">Open preview</a>@else Not ready yet @endif</dd>
        @if ($shop->live_url)<dt>Live</dt><dd><a href="{{ $shop->live_url }}" target="_blank" rel="noopener">Visit your shop</a></dd>@endif
      </dl>
    </section>
    <section class="card" aria-labelledby="h-bill">
      <h2 id="h-bill" style="font-size:17px;margin-bottom:12px">Plan and billing</h2>
      <dl class="facts" style="margin-bottom:16px">
        <dt>Plan</dt><dd>{{ $shop->planName() }}, {{ money($shop->planConfig()['monthly']) }} a month</dd>
        <dt>Set-up fee</dt><dd>{{ money($shop->planConfig()['setup']) }}, {{ $shop->setup_paid_at ? 'paid' : 'not paid yet' }}</dd>
        @if ($nextPayment)<dt>Next payment</dt><dd>{{ $nextPayment->format('j F Y') }}</dd>@endif
      </dl>
      <a class="btn ghost block" href="{{ route('billing') }}">Invoices and card</a>
    </section>
    <section class="card" aria-labelledby="h-sup">
      <h2 id="h-sup" style="font-size:17px;margin-bottom:6px">Support</h2>
      <p class="muted small" style="margin-bottom:14px">{{ $openTickets ? $openTickets.' open '.\Illuminate\Support\Str::plural('ticket', $openTickets).'.' : 'No open tickets.' }} We reply by email, usually the same working day.</p>
      <a class="btn block" href="{{ route('support') }}">Raise a ticket</a>
    </section>
  </aside>
</div>
@endsection
