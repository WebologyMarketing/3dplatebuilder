@extends('layouts.admin')
@section('title', 'Payments')

@section('content')
<header class="head">
  <div><h1>Payments</h1><p class="sub">Paid this month: <b>{{ money($paidThisMonth) }}</b>. Stripe retries failed card payments on its own; these are copies of Stripe's invoices.</p></div>
  <a class="btn ghost" href="https://dashboard.stripe.com/invoices" target="_blank" rel="noopener">Invoices in Stripe</a>
</header>

@if ($addons->isNotEmpty())
<section class="card">
  <h2 style="margin-bottom:6px">Add-ons to bill</h2>
  <p class="lead">Bills the add-on to the card they signed up with, on its own invoice.</p>
  <div class="rows">
    @foreach ($addons as $a)
      <div class="row" style="flex-wrap:wrap"><span><b>{{ $a->shop->name }}</b> · {{ $a->name() }} <span class="muted">· asked {{ $a->created_at->diffForHumans() }}</span></span>
        <span class="actions"><b>{{ money($a->price_pence) }}</b><form method="post" action="{{ route('admin.addons.invoice', $a) }}">@csrf<button class="btn sm" type="submit">Bill now</button></form></span></div>
    @endforeach
  </div>
</section>
@endif

@foreach (['overdue' => 'Overdue', 'open' => 'Due', 'paid' => 'Paid'] as $key => $title)
  <section class="card flush">
    <div class="card-head"><h2>{{ $title }}</h2></div>
    @if ($$key->isEmpty())
      <p class="empty" style="padding:0 22px 16px">{{ $key === 'overdue' ? 'Nothing overdue.' : 'None.' }}</p>
    @else
      <div class="table-wrap"><table>
        <thead><tr><th>Shop</th><th>For</th><th>{{ $key === 'paid' ? 'Paid' : 'Due' }}</th><th class="num">Amount</th>@if ($key !== 'paid')<th>Attempts</th>@endif<th><span class="sr-only">Invoice</span></th></tr></thead>
        <tbody>@foreach ($$key as $p)
          <tr>
            <td>@if ($p->shop)<a href="{{ route('admin.shops.show', $p->shop) }}">{{ $p->shop->name }}</a>@else <span class="muted">Unknown</span>@endif</td>
            <td>{{ $p->description }}</td>
            <td class="nowrap">{{ $key === 'paid' ? $p->paid_at?->format('j M Y') : $p->due_at?->format('j M Y') }}</td>
            <td class="num" @if ($key === 'overdue') style="color:var(--bad);font-weight:700" @endif>{{ money($key === 'paid' ? $p->amount_paid_pence : $p->amount_pence) }}</td>
            @if ($key !== 'paid')<td>{{ $p->attempt_count }}</td>@endif
            <td>@if ($p->hosted_url)<a href="{{ $p->hosted_url }}" target="_blank" rel="noopener">Invoice</a>@endif</td>
          </tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </section>
@endforeach
@endsection
