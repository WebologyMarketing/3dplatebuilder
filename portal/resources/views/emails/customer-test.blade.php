<!doctype html>
<html lang="en-GB">
<body style="margin:0;padding:0;background:#eeeff1;font-family:Arial,Helvetica,sans-serif;color:#16191c">
<p style="margin:0;padding:12px 16px;background:#1f5fd6;color:#fff;font-size:13px;text-align:center">Test email from your 3dPlateBuilder account. Your customers see this with your logo and their own order.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eeeff1"><tr><td align="center" style="padding:32px 12px">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:14px;overflow:hidden">
  <tr><td style="background:{{ $dark }};color:#fff;padding:20px 28px;font-size:18px;font-weight:bold">{{ $name }}</td></tr>
  <tr><td style="background:{{ $accent }};height:6px;font-size:0;line-height:0">&nbsp;</td></tr>
  <tr><td style="padding:32px 28px">
  @if ($which === 'review')
    <h1 style="margin:0 0 12px;font-size:26px;line-height:1.2;text-align:center">{{ $t('review_heading') }}</h1>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#3d4247;text-align:center;white-space:pre-line">{{ $t('review_message') }}</p>
    <p style="margin:0 0 20px;text-align:center"><a href="{{ $e['review_url'] ?: '#' }}" style="display:inline-block;background:{{ $accent }};color:{{ $ink }};text-decoration:none;font-weight:bold;font-size:17px;padding:15px 28px;border-radius:999px">Leave a review</a></p>
    <p style="margin:0;font-size:14px;color:#3d4247;text-align:center">Something not right? Reply to this email and we'll sort it.</p>
  @else
    <h1 style="margin:0 0 12px;font-size:26px;line-height:1.2">{{ $t('order_heading') }}</h1>
    @if (filled($e['order_message']))<p style="margin:0 0 22px;font-size:16px;line-height:1.6;color:#3d4247;white-space:pre-line">{{ $t('order_message') }}</p>@endif
    @if (filled($e['order_needs']))
      <div style="background:#f6f6f7;border-radius:12px;padding:18px;margin:0 0 22px">
        <p style="margin:0 0 6px;font-size:16px;font-weight:bold">What we need from you</p>
        <p style="margin:0;font-size:15px;line-height:1.6;color:#3d4247;white-space:pre-line">{{ $t('order_needs') }}</p>
      </div>
    @endif
    @if (filled($e['order_next']))
      <p style="margin:0 0 6px;font-size:16px;font-weight:bold">What happens next</p>
      <p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#3d4247;white-space:pre-line">{{ $t('order_next') }}</p>
    @endif
    <p style="margin:0 0 8px;font-size:12px;font-weight:bold;letter-spacing:.08em;color:#5b6065">YOUR ORDER #1042 (EXAMPLE)</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px">
      <tr><td style="padding:9px 0;border-bottom:1px solid #e8e8ea">4D Gel 3mm plates, front &amp; rear</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e8e8ea">£64.99</td></tr>
      <tr><td style="padding:9px 0;font-weight:bold">Total paid</td><td align="right" style="padding:9px 0;font-weight:bold">£64.99</td></tr>
    </table>
  @endif
  </td></tr>
  <tr><td style="background:#f6f6f7;padding:18px 28px;font-size:13px;color:#5b6065">{{ $name }} · Registered number plate supplier</td></tr>
</table>
</td></tr></table>
</body>
</html>
