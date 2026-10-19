@extends('layouts.account')
@section('title', $ticket->subject)

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('support') }}">Support</a> / Ticket</p>
    <h1 style="font-size:28px">{{ $ticket->subject }}</h1>
  </div>
  <span class="pill {{ $ticket->status === 'waiting_on_them' ? 'warn' : 'grey' }}">{{ $ticket->statusLabel() }}</span>
</header>

<div class="thread">
  @foreach ($ticket->messages as $m)
    <article class="msg {{ $m->from_admin ? 'us' : '' }}">
      <p class="meta muted">{{ $m->from_admin ? '3dPlateBuilder.com' : ($m->user->name ?? 'You') }} · {{ $m->created_at->format('j M Y, H:i') }}</p>
      <p>{{ $m->body }}</p>
    </article>
  @endforeach
</div>

<form method="post" action="{{ route('support.reply', $ticket) }}" class="card stack" style="max-width:760px">
  @csrf
  <label class="field">{{ $ticket->status === 'closed' ? 'Reopen with a reply' : 'Reply' }}<textarea name="body" rows="5" required>{{ old('body') }}</textarea>@error('body')<span class="err">{{ $message }}</span>@enderror</label>
  <div><button class="btn" type="submit">Send reply</button></div>
</form>
@endsection
