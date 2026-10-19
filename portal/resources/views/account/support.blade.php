@extends('layouts.account')
@section('title', 'Support')

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Support</p>
    <h1>Support</h1>
    <p class="sub">Ask for a change, report a problem or ask a question. We reply by email and here, usually the same working day.</p>
  </div>
</header>

<div class="cols">
  <section class="col-main">
    <div class="card flush">
      <div class="card-head"><h2>Your tickets</h2></div>
      @if ($tickets->isEmpty())
        <p class="empty" style="padding:0 22px 16px">No tickets yet.</p>
      @else
        <div class="table-wrap"><table>
          <thead><tr><th>Subject</th><th>Last update</th><th>Status</th></tr></thead>
          <tbody>
            @foreach ($tickets as $t)
              <tr>
                <td><a href="{{ route('support.show', $t) }}"><b>{{ $t->subject }}</b></a></td>
                <td class="nowrap">{{ ($t->last_reply_at ?? $t->updated_at)->diffForHumans() }}</td>
                <td><span class="pill {{ ['new' => 'grey', 'waiting_on_us' => 'info', 'waiting_on_them' => 'warn', 'closed' => 'grey'][$t->status] }}">{{ $t->statusLabel() }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table></div>
      @endif
    </div>
  </section>
  <aside class="col-side">
    <form method="post" action="{{ route('support') }}" class="card stack" aria-labelledby="h-new">
      @csrf
      <h2 id="h-new">Raise a ticket</h2>
      <label class="field">Subject<input name="subject" value="{{ old('subject') }}" maxlength="150" required>@error('subject')<span class="err">{{ $message }}</span>@enderror</label>
      <label class="field">What do you need?<textarea name="body" rows="6" required>{{ old('body') }}</textarea>@error('body')<span class="err">{{ $message }}</span>@enderror</label>
      <button class="btn block" type="submit">Send</button>
    </form>
  </aside>
</div>
@endsection
