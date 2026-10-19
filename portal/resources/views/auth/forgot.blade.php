@extends('layouts.guest')
@section('title', 'Reset your password')

@section('content')
<h1 style="font-size:28px">Reset your password</h1>
<p class="muted" style="margin:8px 0 20px">We'll email you a link to choose a new one.</p>
<form method="post" action="{{ route('password.email') }}" class="stack">
  @csrf
  <label class="field">Email
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    @error('email')<span class="err">{{ $message }}</span>@enderror
  </label>
  <button class="btn block" type="submit">Send the link</button>
  <p class="small" style="text-align:center"><a href="{{ route('login') }}">Back to sign in</a></p>
</form>
@endsection
