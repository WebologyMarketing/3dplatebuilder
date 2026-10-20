@extends('layouts.account')
@section('title', 'Customer emails')

@php $v = fn ($k) => old($k, $e[$k]); @endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('dashboard') }}">Overview</a> / Customer emails</p>
    <h1>Customer emails</h1>
    <p class="sub">{{ $shop->hasCustomerEmails()
      ? 'Write the order email and the review request in your own words. They go out in your logo and colours.'
      : 'Your customers get an order confirmation and receipt in your logo and colours. There\'s nothing to write.' }}</p>
  </div>
</header>

@if (! $shop->hasCustomerEmails())
  <section class="card stack">
    <h2>What your customers get</h2>
    <ul class="lead" style="margin:0;padding-left:20px;line-height:1.8">
      <li>An order confirmation with a full receipt: their plates, extras, total and how they'll get them.</li>
      <li>What documents they need for road-legal plates, and how to send them.</li>
      <li>An email when you mark the order complete.</li>
    </ul>
  </section>
  <section class="card dark">
    <h2 style="font-size:20px">Want your own wording and review requests?</h2>
    <p class="muted" style="margin:6px 0 0">On Pro and Complete you write the order email yourself, and customers get a review request 3 days after their order is complete. <a href="{{ route('support') }}" style="color:#fff">Raise a ticket</a> to change plan.</p>
  </section>
@else
  @include('account.partials.publish')

  @if ($errors->any())<div class="notice bad" role="alert">@foreach ($errors->unique() as $err)<div>{{ $err }}</div>@endforeach</div>@endif

  <form method="post" action="{{ route('emails') }}" class="stack">
    @csrf
    <p class="muted" style="margin:0">You can use <code>{first_name}</code>, <code>{order_number}</code> and <code>{shop_name}</code>: we fill them in for each customer.</p>

    <section class="card stack" aria-labelledby="h-order">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
        <h2 id="h-order" style="margin:0">Order email</h2>
        <label class="onoff"><input type="hidden" name="order_on" value="0"><input type="checkbox" name="order_on" value="1" @checked($v('order_on'))> Send my own order email</label>
      </div>
      <p class="lead" style="margin:0">Sent as soon as an order is placed, with the order details and receipt underneath. Switched off, customers get the standard confirmation.</p>
      <label class="field">Heading<input name="order_heading" value="{{ $v('order_heading') }}" maxlength="160"></label>
      <label class="field">Message<textarea name="order_message" rows="3" maxlength="1500">{{ $v('order_message') }}</textarea></label>
      <label class="field">What we need from you <small>Shown in a box. Leave empty to leave the box out. If documents are still to come, our standard documents box with how to send them follows the order details.</small><textarea name="order_needs" rows="4" maxlength="1500">{{ $v('order_needs') }}</textarea></label>
      <label class="field">What happens next <small>Optional.</small><textarea name="order_next" rows="3" maxlength="1500">{{ $v('order_next') }}</textarea></label>
      <div class="actions" style="margin:0"><button class="btn ghost" type="submit" name="test" value="order">Save and send me a test</button></div>
    </section>

    <section class="card stack" aria-labelledby="h-review">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
        <h2 id="h-review" style="margin:0">Review request</h2>
        <label class="onoff"><input type="hidden" name="review_on" value="0"><input type="checkbox" name="review_on" value="1" @checked($v('review_on'))> Ask for reviews</label>
      </div>
      <p class="lead" style="margin:0">Sent once, 3 days after you mark an order complete. Never reminders.</p>
      <label class="field">Link to your review page <small>Your Google review link, for example. In Google Business Profile: Ask for reviews, then copy the link.</small><input name="review_url" value="{{ $v('review_url') }}" inputmode="url" placeholder="https://g.page/r/…/review"></label>
      <label class="field">Heading<input name="review_heading" value="{{ $v('review_heading') }}" maxlength="160"></label>
      <label class="field">Message<textarea name="review_message" rows="3" maxlength="1500">{{ $v('review_message') }}</textarea></label>
      <div class="actions" style="margin:0"><button class="btn ghost" type="submit" name="test" value="review">Save and send me a test</button></div>
    </section>

    <div class="actions savebar"><button class="btn" type="submit">Save</button><span class="muted">Saving doesn't change your website. Publish when you're ready.</span></div>
  </form>
@endif
@endsection
