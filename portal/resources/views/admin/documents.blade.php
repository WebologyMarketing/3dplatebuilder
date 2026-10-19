@extends('layouts.admin')
@section('title', 'Documents')

@section('content')
<header class="head"><h1>Documents</h1></header>

<section class="stack">
  <h2>To check</h2>
  @forelse ($pending as $d)
    <article class="card" id="doc-{{ $d->id }}">
      <div class="card-head">
        <div><h3>{{ $d->shop->name }}</h3><p class="small muted">RNPS registration · uploaded {{ $d->created_at->format('j M Y, H:i') }} · their RNPS number: {{ $d->shop->rnps_number ?: 'not given' }}</p></div>
        <a class="btn ghost sm" href="{{ route('admin.documents.file', $d) }}" target="_blank" rel="noopener">Open {{ \Illuminate\Support\Str::limit($d->original_name, 30) }}</a>
      </div>
      <form method="post" action="{{ route('admin.documents.review', $d) }}" class="stack" style="gap:10px">
        @csrf
        <label class="field">Reason, if rejecting <small>They'll see this</small><input name="note" maxlength="1000"></label>
        <div class="actions">
          <button class="btn" name="decision" value="approved" type="submit">Approve</button>
          <button class="btn danger" name="decision" value="rejected" type="submit">Reject</button>
        </div>
      </form>
    </article>
  @empty
    <p class="empty card">Nothing to check.</p>
  @endforelse
</section>

@if ($reviewed->isNotEmpty())
<section class="card flush">
  <div class="card-head"><h2>Checked</h2></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Shop</th><th>File</th><th>Decision</th><th>When</th><th>Note</th></tr></thead>
    <tbody>@foreach ($reviewed as $d)
      <tr><td>{{ $d->shop->name }}</td><td><a href="{{ route('admin.documents.file', $d) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($d->original_name, 30) }}</a></td>
        <td><span class="pill {{ $d->status === 'approved' ? 'good' : 'bad' }}">{{ ucfirst($d->status) }}</span></td>
        <td class="nowrap">{{ $d->reviewed_at?->format('j M Y') }}</td><td>{{ $d->note }}</td></tr>
    @endforeach</tbody>
  </table></div>
</section>
@endif
@endsection
