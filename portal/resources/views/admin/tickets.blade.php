@extends('layouts.admin')
@section('title', 'Tickets')

@section('content')
<header class="head"><h1>Tickets</h1></header>

<section class="card flush">
  <div class="card-head">
    <nav class="tabs" aria-label="Filter tickets">
      @foreach (['open' => 'Open', 'closed' => 'Closed', 'all' => 'All'] as $k => $label)
        <a href="{{ route('admin.tickets', ['show' => $k]) }}" aria-current="{{ $show === $k ? 'true' : 'false' }}">{{ $label }}</a>
      @endforeach
    </nav>
  </div>
  @if ($tickets->isEmpty())
    <p class="empty" style="padding:0 22px 16px">No tickets here.</p>
  @else
    <div class="table-wrap"><table>
      <thead><tr><th>Subject</th><th>Shop</th><th>Last update</th><th>Status</th></tr></thead>
      <tbody>@foreach ($tickets as $t)
        <tr>
          <td><a href="{{ route('admin.tickets.show', $t) }}"><b>{{ $t->subject }}</b></a></td>
          <td>{{ $t->shop->name }}</td>
          <td class="nowrap">{{ ($t->last_reply_at ?? $t->created_at)->diffForHumans() }}</td>
          <td><span class="pill {{ ['new' => 'warn', 'waiting_on_us' => 'info', 'waiting_on_them' => 'grey', 'closed' => 'grey'][$t->status] }}">{{ $t->statusLabel(true) }}</span></td>
        </tr>
      @endforeach</tbody>
    </table></div>
  @endif
</section>
@endsection
