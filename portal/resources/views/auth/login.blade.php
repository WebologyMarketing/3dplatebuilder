@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<h1 style="font-size:30px;margin-bottom:20px">Sign in</h1>
<form method="post" action="{{ route('login') }}" class="stack">
  @csrf
  <label class="field">Email
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    @error('email')<span class="err">{{ $message }}</span>@enderror
  </label>
  <label class="field">Password
    <input type="password" name="password" autocomplete="current-password" required>
  </label>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
    <a class="small" href="{{ route('password.request') }}">Forgotten your password?</a>
  </div>
  <button class="btn block" type="submit">Sign in</button>
  <p class="small muted" style="text-align:center">New here? <a href="{{ route('signup') }}">Start your shop</a></p>
</form>
@endsection
