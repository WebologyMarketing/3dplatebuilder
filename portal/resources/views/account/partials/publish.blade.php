@php
  $changes = $shop->hasUnpublishedChanges();
  $site = $shop->siteUrl();
  $onSite = $shop->published_version && $shop->site_version === $shop->published_version;
@endphp
<section class="card publish {{ $changes ? 'is-pending' : '' }}" aria-labelledby="h-pub">
  <div>
    <h2 id="h-pub">{{ $changes ? ($shop->published_version ? 'You have changes to publish' : 'Ready when you are') : 'Everything\'s published' }}</h2>
    <p class="lead" style="margin:0">
      @if ($changes && ! $shop->published_version)
        Nothing shows on your website until you publish. You can publish as often as you like.
      @elseif ($changes)
        Your website shows what you published on {{ $shop->published_at->timezone('Europe/London')->format('j M, H:i') }}. Publish to send these changes.
      @else
        Published {{ $shop->published_at->timezone('Europe/London')->format('j M, H:i') }}.
        @if (! $site) We'll use these when we build your shop.
        @elseif ($onSite) Your website has it.
        @else Your website will pick it up shortly.
        @endif
      @endif
    </p>
  </div>
  <form method="post" action="{{ route('publish') }}">
    @csrf
    <button class="btn" type="submit" @disabled(! $changes)>Publish to my website</button>
  </form>
</section>
