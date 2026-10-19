@extends('layouts.admin')
@section('title', 'Plugin updates')

@section('content')
<header class="head">
  <div><h1>Plugin updates</h1><p class="sub">Shops with a current subscription and a licence key ({{ $licensed }} now) get the newest version here as a normal WordPress update.</p></div>
</header>

<div class="cols">
  <section class="col-main card flush">
    <div class="card-head"><h2>Versions</h2></div>
    @if ($releases->isEmpty())
      <p class="empty" style="padding:0 22px 16px">No versions published yet.</p>
    @else
      <div class="table-wrap"><table>
        <thead><tr><th>Version</th><th>Published</th><th>Notes</th></tr></thead>
        <tbody>@foreach ($releases as $i => $r)
          <tr><td><b>{{ $r->version }}</b> @if ($loop->first)<span class="pill good">Latest</span>@endif</td><td class="nowrap">{{ $r->created_at->format('j M Y') }}</td><td style="white-space:pre-line">{{ $r->notes }}</td></tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </section>

  <form method="post" action="{{ route('admin.releases') }}" enctype="multipart/form-data" class="col-side card stack">
    @csrf
    <h2>Publish a version</h2>
    <label class="field">Version <small>Must match the plugin's header, like 1.10.4</small><input name="version" value="{{ old('version') }}" required></label>
    <label class="field">Plugin zip<input type="file" name="zip" accept=".zip" required></label>
    <label class="field">What's new <small>Shown in their WordPress updates screen</small><textarea name="notes">{{ old('notes') }}</textarea></label>
    <div class="grid-2">
      <label class="field">Needs WordPress<input name="requires_wp" value="{{ old('requires_wp', '6.4') }}"></label>
      <label class="field">Needs PHP<input name="requires_php" value="{{ old('requires_php', '8.0') }}"></label>
    </div>
    <button class="btn" type="submit">Publish</button>
  </form>
</div>
@endsection
