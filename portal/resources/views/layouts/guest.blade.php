@extends('layouts.base')

@section('body')
<main class="auth">
  <div class="auth-card @yield('width')">
    <a class="brand" href="https://3dplatebuilder.com"><span class="mark" aria-hidden="true">3D</span>3dPlateBuilder.com</a>
    @if (session('status'))<p class="notice good" role="status" style="margin-bottom:16px">{{ session('status') }}</p>@endif
    @yield('content')
  </div>
</main>
@endsection
