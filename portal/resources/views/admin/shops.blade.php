@extends('layouts.admin')
@section('title', 'Customers')

@php
  $stagePill = ['not_paid' => 'grey', 'onboarding' => 'warn', 'in_build' => 'info', 'review' => 'purple', 'live' => 'good', 'cancelled' => 'grey'];
@endphp

@section('content')
<header class="head">
  <h1>Customers</h1>
  <form method="get" class="actions" role="search">
    <input type="hidden" name="show" value="{{ $filter }}">
    <label class="sr-only" for="q">Search customers</label>
    <input class="input" id="q" name="q" value="{{ $q }}" placeholder="Name, domain or email" style="width:260px">
    <button class="btn ghost" type="submit">Search</button>
  </form>
</header>

<section class="card flush">
  <div class="card-head">
    <nav class="tabs" aria-label="Filter customers">
      @foreach (['all' => 'All', 'live' => 'Live', 'setup' => 'Setting up', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $k => $label)
        <a href="{{ route('admin.shops', array_filter(['show' => $k, 'q' => $q])) }}" aria-current="{{ $filter === $k ? 'true' : 'false' }}">{{ $label }}</a>
      @endforeach
    </nav>
    <span class="small muted">{{ $shops->count() }} {{ \Illuminate\Support\Str::plural('shop', $shops->count()) }}</span>
  </div>
  @if ($shops->isEmpty())
    <p class="empty" style="padding:0 22px 16px">No customers here yet.</p>
  @else
    <div class="table-wrap"><table>
      <thead><tr><th>Shop</th><th>Plan</th><th>Stage</th><th>Website</th><th>Next payment</th><th class="num">Overdue</th><th>Set-up</th><th><span class="sr-only">Open</span></th></tr></thead>
      <tbody>
        @foreach ($shops as $s)
          @php $owed = $s->overdueBalancePence(); $done = collect($s->onboardingSteps())->where('done', true)->count(); @endphp
          <tr>
            <td><b>{{ $s->name }}</b><div class="small muted">{{ $s->users->first()?->email }}</div></td>
            <td>{{ $s->planName() }}</td>
            <td><span class="pill {{ $stagePill[$s->stage] ?? 'grey' }}">{{ $s->stageLabel() }}</span></td>
            <td>
              @if ($s->live_url)<a href="{{ $s->live_url }}" target="_blank" rel="noopener">{{ parse_url($s->live_url, PHP_URL_HOST) }}</a>
              @elseif ($s->preview_url)<a href="{{ $s->preview_url }}" target="_blank" rel="noopener">Preview</a>
              @else<span class="muted">{{ $s->domain ?: '—' }}</span>@endif
            </td>
            <td class="nowrap">{{ $s->current_period_end?->format('j M') ?? '—' }}</td>
            <td class="num" @if ($owed) style="color:var(--bad);font-weight:700" @endif>{{ $owed ? money($owed) : '—' }}</td>
            <td class="nowrap">{{ $done }}/7</td>
            <td><a class="btn ghost sm" href="{{ route('admin.shops.show', $s) }}">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  @endif
</section>
@endsection
