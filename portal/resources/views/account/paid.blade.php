@extends('layouts.account')
@section('title', 'Confirming your payment')

@section('content')
<section class="card" style="max-width:620px">
  <h1 style="font-size:28px">Thanks, we're just confirming your payment</h1>
  <p class="muted" style="margin:10px 0 20px">This usually takes a few seconds. This page checks again on its own.</p>
  <a class="btn ghost" href="{{ request()->fullUrl() }}">Check now</a>
</section>
@push('scripts')<script>setTimeout(function(){location.reload()},4000)</script>@endpush
@endsection
