@extends('layouts.admin')
@section('title', $ticket->subject)

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('admin.tickets') }}">Tickets</a> / <a href="{{ route('admin.shops.show', $ticket->shop) }}">{{ $ticket->shop->name }}</a></p>
    <h1 style="font-size:28px">{{ $ticket->subject }}</h1>
  </div>
  <span class="pill grey">{{ $ticket->statusLabel(true) }}</span>
</header>

<div class="thread">
  @foreach ($ticket->messages as $m)
    <article class="msg {{ $m->from_admin ? 'us' : '' }}">
      <p class="meta muted">{{ $m->from_admin ? 'You' : ($m->user->name ?? $ticket->shop->name) }} · {{ $m->created_at->format('j M Y, H:i') }}</p>
      <p>{{ $m->body }}</p>
    </article>
  @endforeach
</div>

<form method="post" action="{{ route('admin.tickets.reply', $ticket) }}" class="card stack" style="max-width:760px">
  @csrf
  <label class="field">Reply <small>Emailed to them, and shown in their account</small><textarea name="body" rows="6">{{ old('body') }}</textarea></label>
  <div class="actions">
    <button class="btn" type="submit">Send reply</button>
    <button class="btn ghost" type="submit" name="close" value="1">{{ 'Send and close' }}</button>
  </div>
</form>
@endsection
