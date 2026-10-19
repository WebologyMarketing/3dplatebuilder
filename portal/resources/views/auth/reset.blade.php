@extends('layouts.guest')
@section('title', 'Choose a new password')

@section('content')
<h1 style="font-size:28px;margin-bottom:20px">Choose a new password</h1>
<form method="post" action="{{ route('password.update') }}" class="stack">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <label class="field">Email
    <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
    @error('email')<span class="err">{{ $message }}</span>@enderror
  </label>
  <label class="field">New password <small>At least 10 characters</small>
    <input type="password" name="password" autocomplete="new-password" required>
    @error('password')<span class="err">{{ $message }}</span>@enderror
  </label>
  <label class="field">New password again
    <input type="password" name="password_confirmation" autocomplete="new-password" required>
  </label>
  <button class="btn block" type="submit">Save password</button>
</form>
@endsection
