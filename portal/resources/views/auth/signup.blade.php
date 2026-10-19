@extends('layouts.guest')
@section('title', 'Sign up')
@section('width', 'wide')

@section('content')
<h1 style="font-size:30px">Start your shop</h1>
<p class="muted" style="margin:8px 0 22px">Make your account, then pay your set-up fee and first month. After that, you add your details and we build your shop.</p>

<form method="post" action="{{ route('signup') }}" class="stack" novalidate>
  @csrf
  <fieldset style="border:0;margin:0;padding:0">
    <legend class="field" style="margin-bottom:8px">Your plan</legend>
    <div class="plans-pick">
      @foreach ($plans as $key => $p)
        <label class="plan-opt">
          <input type="radio" name="plan" value="{{ $key }}" @checked(old('plan', $plan) === $key)>
          <b>{{ $p['name'] }}</b>
          <span class="p">{{ money($p['monthly']) }} a month</span>
          <span>+ {{ money($p['setup']) }} set-up</span>
        </label>
      @endforeach
    </div>
    @error('plan')<p class="err">{{ $message }}</p>@enderror
    <p class="small muted" style="margin-top:8px">12-month minimum, then 30 days' notice by email to cancel. We're not VAT registered, so there's no VAT to add. <a href="https://3dplatebuilder.com/pricing.html" target="_blank" rel="noopener">Compare plans</a></p>
  </fieldset>

  <div class="grid-2">
    <label class="field">Business name
      <input name="business" value="{{ old('business') }}" autocomplete="organization" required>
      @error('business')<span class="err">{{ $message }}</span>@enderror
    </label>
    <label class="field">Your name
      <input name="name" value="{{ old('name') }}" autocomplete="name" required>
      @error('name')<span class="err">{{ $message }}</span>@enderror
    </label>
  </div>
  <label class="field">Email
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
    @error('email')<span class="err">{{ $message }}</span>@enderror
  </label>
  <div class="grid-2">
    <label class="field">Password <small>At least 10 characters</small>
      <input type="password" name="password" autocomplete="new-password" required>
      @error('password')<span class="err">{{ $message }}</span>@enderror
    </label>
    <label class="field">Password again
      <input type="password" name="password_confirmation" autocomplete="new-password" required>
    </label>
  </div>
  <label class="check">
    <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
    <span>I've read the <a href="https://3dplatebuilder.com/privacy.html" target="_blank" rel="noopener">privacy notice</a> and the <a href="https://3dplatebuilder.com/terms.html" target="_blank" rel="noopener">terms</a>.</span>
  </label>
  @error('terms')<p class="err">{{ $message }}</p>@enderror
  <button class="btn block" type="submit">Create account and go to payment →</button>
  <p class="small muted" style="text-align:center">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</form>
@endsection
