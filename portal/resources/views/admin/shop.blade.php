@extends('layouts.admin')
@section('title', $shop->name)

@php
  $stagePill = ['not_paid' => 'grey', 'onboarding' => 'warn', 'in_build' => 'info', 'review' => 'purple', 'live' => 'good', 'cancelled' => 'grey'];
  $bs = \App\Support\BuilderSettings::forShop($shop);
  $items = $shop->shopProducts()->get();
  $docPill = ['approved' => 'good', 'pending' => 'warn', 'rejected' => 'bad'];
@endphp

@section('content')
<header class="head">
  <div>
    <p class="crumb"><a href="{{ route('admin.shops') }}">Customers</a> / {{ $shop->name }}</p>
    <h1>{{ $shop->name }}</h1>
    <p class="sub" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center"><span class="pill {{ $stagePill[$shop->stage] ?? 'grey' }}">{{ $shop->stageLabel() }}</span> {{ $shop->planName() }} plan · signed up {{ $shop->created_at->format('j M Y') }}</p>
  </div>
  <div class="actions">
    <a class="btn ghost" href="{{ route('admin.shops.export', $shop) }}">Download build details</a>
    @if ($shop->stripe_customer_id)<a class="btn ghost" href="https://dashboard.stripe.com/customers/{{ $shop->stripe_customer_id }}" target="_blank" rel="noopener">Open in Stripe</a>@endif
  </div>
</header>

<div class="cols">
  <div class="col-main">
    <form method="post" action="{{ route('admin.shops.update', $shop) }}" class="card stack">
      @csrf
      <h2>Stage and links</h2>
      <div class="grid-2">
        <label class="field">Stage <small>"Their review" or "Live" emails them. "Live" also starts their monthly billing.</small>
          <select name="stage">@foreach (\App\Models\Shop::STAGES as $k => $label)<option value="{{ $k }}" @selected($shop->stage === $k)>{{ $label }}</option>@endforeach</select>
        </label>
        <label class="field">Preview link<input type="url" name="preview_url" value="{{ old('preview_url', $shop->preview_url) }}" placeholder="https://"></label>
        <label class="field">Live website<input type="url" name="live_url" value="{{ old('live_url', $shop->live_url) }}" placeholder="https://"></label>
      </div>
      <label class="field">Private notes <small>Only you see these</small><textarea name="admin_notes">{{ old('admin_notes', $shop->admin_notes) }}</textarea></label>
      <div><button class="btn" type="submit">Save</button></div>
    </form>

    <section class="card">
      <h2 style="margin-bottom:12px">Their details</h2>
      <div class="grid-2">
        <dl class="facts">
          <dt>Company</dt><dd>{{ $shop->legal_name ?: '—' }}</dd>
          <dt>Trading as</dt><dd>{{ $shop->trading_name ?: '—' }}</dd>
          <dt>Company no.</dt><dd>{{ $shop->company_number ?: '—' }}</dd>
          <dt>VAT no.</dt><dd>{{ $shop->vat_number ?: '—' }}</dd>
          <dt>Address</dt><dd style="white-space:pre-line">{{ $shop->registered_address ?: '—' }}</dd>
        </dl>
        <dl class="facts">
          <dt>Shop email</dt><dd>{{ $shop->contact_email ?: '—' }}</dd>
          <dt>Domain</dt><dd>{{ $shop->domain ?: '—' }}</dd>
          <dt>On plates</dt><dd>{{ $shop->supplier_name ?: '—' }} {{ $shop->supplier_postcode }}</dd>
          <dt>RNPS no.</dt><dd>{{ $shop->rnps_number ?: '—' }}</dd>
          <dt>Collection</dt><dd style="white-space:pre-line">{{ $shop->collection_address ?: 'Postal only' }}</dd>
        </dl>
      </div>
    </section>

    <section class="card">
      <h2 style="margin-bottom:12px">Brand</h2>
      <div class="grid-2">
        <div>
          @if ($shop->logo_path)<div class="logo-box"><img src="{{ route('admin.shops.file', [$shop, 'logo']) }}" alt="{{ $shop->name }} logo"></div>
            <p class="small" style="margin-top:6px"><a href="{{ route('admin.shops.file', [$shop, 'logo']) }}?download=1">Download logo</a>@if ($shop->logo_white_path) · <a href="{{ route('admin.shops.file', [$shop, 'logo-white']) }}?download=1">White version</a>@endif</p>
          @else<p class="empty">No logo yet.</p>@endif
        </div>
        <dl class="facts">
          <dt>Main colour</dt><dd>@if ($shop->accent_colour)<span style="display:inline-block;width:14px;height:14px;border-radius:4px;vertical-align:-2px;background:{{ $shop->accent_colour }};box-shadow:0 0 0 1px var(--line-2)"></span> {{ strtoupper($shop->accent_colour) }}@else — @endif</dd>
          <dt>Dark colour</dt><dd>@if ($shop->dark_colour)<span style="display:inline-block;width:14px;height:14px;border-radius:4px;vertical-align:-2px;background:{{ $shop->dark_colour }}"></span> {{ strtoupper($shop->dark_colour) }}@else — @endif</dd>
          <dt>Heading font</dt><dd>{{ $shop->heading_font ?: '—' }}</dd>
          @if ($shop->font_files)<dt>Own fonts</dt><dd>@foreach ($shop->font_files as $i => $ff)<a href="{{ route('admin.shops.file', [$shop, 'font-'.$i]) }}">{{ $ff['name'] }}</a><br>@endforeach</dd>@endif
        </dl>
      </div>
    </section>

    <section class="card">
      <h2 style="margin-bottom:12px">Builder and products</h2>
      <dl class="facts" style="margin-bottom:14px">
        <dt>Published</dt><dd>{{ $shop->published_version ? 'Version '.$shop->published_version.', '.$shop->published_at->timezone('Europe/London')->format('j M Y, H:i') : 'Not yet' }}{{ $shop->published_version && $shop->hasUnpublishedChanges() ? ' (unpublished changes)' : '' }}</dd>
        <dt>Website has</dt><dd>{{ $shop->site_version ? 'Version '.$shop->site_version : '—' }}{{ $shop->site_checked_at ? ', checked in '.$shop->site_checked_at->diffForHumans() : '' }}</dd>
      </dl>
      <div class="table-wrap" style="margin-bottom:14px"><table>
        <thead><tr><th>Style</th><th class="num">Pair</th><th class="num">One</th></tr></thead>
        <tbody>@foreach ($bs['styles'] as $id => $st)@if ($st['on'])<tr><td>{{ $catalogue['styles'][$id]['label'] ?? $id }}</td><td class="num">£{{ $st['pair'] }}</td><td class="num">£{{ $st['single'] }}</td></tr>@endif @endforeach</tbody>
      </table></div>
      <dl class="facts">
        @foreach (['sizes' => 'Sizes', 'badges' => 'Badges', 'borders' => 'Borders', 'extras' => 'Extras'] as $g => $label)
          <dt>{{ $label }}</dt><dd style="font-weight:500">{{ collect($bs[$g])->filter(fn ($o) => $o['on'])->map(fn ($o, $id) => ($catalogue[$g][$id]['label'] ?? $id).' £'.$o['price'])->implode(', ') ?: '—' }}</dd>
        @endforeach
        <dt>Own add-ons</dt><dd style="font-weight:500">{{ collect($bs['custom'])->filter(fn ($a) => $a['on'])->map(fn ($a) => $a['name'].' £'.$a['price'])->implode(', ') ?: '—' }}</dd>
        <dt>Products</dt><dd style="font-weight:500">{{ $items->map(fn ($i) => $i->name.' ('.$i->priceLabel($bs).')'.($i->active ? '' : ', hidden'))->implode('; ') ?: '—' }}</dd>
      </dl>
    </section>

    <section class="card flush">
      <div class="card-head"><h2>Payments</h2></div>
      @if ($payments->isEmpty())<p class="empty" style="padding:0 22px 16px">No invoices yet.</p>@else
      <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>For</th><th class="num">Amount</th><th>Status</th></tr></thead>
        <tbody>@foreach ($payments as $pay)
          <tr><td class="nowrap">{{ $pay->created_at->format('j M Y') }}</td><td>{{ $pay->description }}</td><td class="num">{{ money($pay->amount_pence) }}</td>
            <td>@if ($pay->status === 'paid')<span class="pill good">Paid</span>@elseif ($pay->isOverdue())<span class="pill bad">Overdue</span>@elseif ($pay->status === 'open')<span class="pill warn">Due</span>@else<span class="pill grey">{{ ucfirst($pay->status) }}</span>@endif</td></tr>
        @endforeach</tbody>
      </table></div>@endif
    </section>
  </div>

  <aside class="col-side">
    <section class="card">
      <h2 style="font-size:17px;margin-bottom:12px">Set-up progress</h2>
      <div class="rows">
        @foreach ($steps as $s)<div class="row"><span>{{ $s['title'] }}</span><span class="pill {{ $s['done'] ? 'good' : (! empty($s['started']) ? 'warn' : 'grey') }}">{{ $s['done'] ? 'Done' : (! empty($s['started']) ? 'Started' : 'To do') }}</span></div>@endforeach
      </div>
    </section>

    <section class="card">
      <h2 style="font-size:17px;margin-bottom:12px">Account</h2>
      <dl class="facts">
        @foreach ($shop->users as $u)<dt>Login</dt><dd>{{ $u->name }}<br><a href="mailto:{{ $u->email }}">{{ $u->email }}</a></dd>@endforeach
        <dt>Monthly</dt><dd>{{ money($shop->monthlyPence()) }}</dd>
        <dt>Set-up paid</dt><dd>{{ $shop->setup_paid_at?->format('j M Y') ?? 'Not yet' }}</dd>
        <dt>Subscription</dt><dd>{{ $shop->subscription_status ?? '—' }}</dd>
        <dt>Monthly billing</dt><dd>@if ($shop->billingStarted())Started {{ $shop->live_at?->format('j M Y') }}{{ $shop->current_period_end ? ', next '.$shop->current_period_end->format('j M Y') : '' }}@else Starts when you set them to Live @endif</dd>
        @if ($shop->setup_paid_at)<dt>Minimum term ends</dt><dd>{{ $shop->setup_paid_at->copy()->addMonths(config('portal.minimum_term_months'))->format('j M Y') }}</dd>@endif
      </dl>
    </section>

    <section class="card">
      <h2 style="font-size:17px;margin-bottom:8px">Licence key</h2>
      <p class="small muted" style="margin-bottom:10px">Goes in their builder plugin so it gets updates.</p>
      @if ($shop->licence_key)<p class="mono" style="margin-bottom:12px;overflow-wrap:anywhere">{{ $shop->licence_key }}</p>@else<p class="empty">Made when they pay.</p>@endif
      <form method="post" action="{{ route('admin.shops.licence', $shop) }}">@csrf<button class="btn ghost sm" type="submit">{{ $shop->licence_key ? 'Make a new key' : 'Make a key now' }}</button></form>
    </section>

    <section class="card">
      <h2 style="font-size:17px;margin-bottom:8px">Documents</h2>
      <div class="rows">
        @forelse ($shop->documents->sortByDesc('id') as $d)
          <div class="row"><a href="{{ route('admin.documents.file', $d) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($d->original_name, 28) }}</a><span class="pill {{ $docPill[$d->status] }}">{{ ucfirst($d->status) }}</span></div>
        @empty<p class="empty">None uploaded.</p>@endforelse
      </div>
      @if ($shop->documents->where('status', 'pending')->isNotEmpty())<a class="btn sm" style="margin-top:10px" href="{{ route('admin.documents') }}">Check now</a>@endif
    </section>

    <section class="card">
      <h2 style="font-size:17px;margin-bottom:8px">Tickets</h2>
      <div class="rows">
        @forelse ($tickets as $t)<a class="row" href="{{ route('admin.tickets.show', $t) }}" style="text-decoration:none"><span>{{ \Illuminate\Support\Str::limit($t->subject, 30) }}</span><span class="pill grey">{{ $t->statusLabel(true) }}</span></a>
        @empty<p class="empty">No tickets.</p>@endforelse
      </div>
    </section>

    @if ($shop->addonRequests->isNotEmpty())
    <section class="card">
      <h2 style="font-size:17px;margin-bottom:8px">Add-ons</h2>
      <div class="rows">@foreach ($shop->addonRequests as $a)<div class="row"><span>{{ $a->name() }}</span><span class="pill {{ $a->status === 'requested' ? 'warn' : 'good' }}">{{ ucfirst($a->status) }}</span></div>@endforeach</div>
    </section>
    @endif
  </aside>
</div>
@endsection
