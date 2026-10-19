@extends('layouts.account')
@section('title', 'Billing')

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Billing</p>
    <h1>Billing</h1>
  </div>
  @if ($shop->stripe_customer_id)
    <form method="post" action="{{ route('billing.portal') }}">@csrf<button class="btn" type="submit">Change card or billing details</button></form>
  @elseif (! $shop->setup_paid_at)
    <a class="btn" href="{{ route('pay') }}">Pay set-up fee</a>
  @endif
</header>

<div class="cols">
  <section class="col-main">
    <div class="card flush">
      <div class="card-head"><h2>Invoices</h2></div>
      @if ($payments->isEmpty())
        <p class="empty" style="padding:0 22px 16px">No invoices yet.</p>
      @else
        <div class="table-wrap"><table>
          <thead><tr><th>Date</th><th>For</th><th class="num">Amount</th><th>Status</th><th><span class="sr-only">Invoice</span></th></tr></thead>
          <tbody>
            @foreach ($payments as $p)
              <tr>
                <td class="nowrap">{{ $p->created_at->format('j M Y') }}</td>
                <td>{{ $p->description }}</td>
                <td class="num">{{ money($p->amount_pence) }}</td>
                <td>
                  @if ($p->status === 'paid')<span class="pill good">Paid</span>
                  @elseif ($p->isOverdue())<span class="pill bad">Overdue</span>
                  @elseif ($p->status === 'open')<span class="pill warn">Due</span>
                  @else<span class="pill grey">{{ ucfirst($p->status) }}</span>@endif
                </td>
                <td>@if ($p->hosted_url)<a href="{{ $p->hosted_url }}" target="_blank" rel="noopener">{{ $p->status === 'open' ? 'Pay now' : 'View' }}</a>@endif</td>
              </tr>
            @endforeach
          </tbody>
        </table></div>
      @endif
    </div>
    @if ($addons->isNotEmpty())
      <div class="card">
        <h2 style="margin-bottom:8px">Add-ons you've asked for</h2>
        <div class="rows">
          @foreach ($addons as $a)
            <div class="row"><span>{{ $a->name() }}</span><span>{{ money($a->price_pence) }} <span class="pill {{ $a->status === 'invoiced' ? 'good' : 'warn' }}">{{ $a->status === 'invoiced' ? 'On your next invoice' : 'Waiting for us' }}</span></span></div>
          @endforeach
        </div>
      </div>
    @endif
  </section>
  <aside class="col-side">
    <section class="card">
      <h2 style="font-size:17px;margin-bottom:12px">Your plan</h2>
      <dl class="facts">
        <dt>Plan</dt><dd>{{ $shop->planName() }}</dd>
        <dt>Monthly</dt><dd>{{ money($shop->planConfig()['monthly']) }}</dd>
        <dt>Set-up fee</dt><dd>{{ money($shop->planConfig()['setup']) }}</dd>
        @if ($shop->current_period_end)<dt>Next payment</dt><dd>{{ $shop->current_period_end->format('j F Y') }}</dd>@endif
        <dt>Minimum term</dt><dd>{{ config('portal.minimum_term_months') }} months</dd>
      </dl>
      <p class="small muted" style="margin-top:14px">No VAT to add: we're not VAT registered. To change plan or cancel after your minimum term, email hello@3dplatebuilder.com with {{ config('portal.notice_days') }} days' notice.</p>
    </section>
  </aside>
</div>
@endsection
