<!doctype html>
<html lang="en-GB">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $heading }}</title></head>
<body style="margin:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#16191c">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden">
<tr><td style="background:#16191c;padding:20px 28px">
<span style="display:inline-block;width:28px;height:28px;line-height:28px;text-align:center;border-radius:8px;background:#F7D333;color:#16191c;font-weight:800;font-size:12px">3D</span>
<span style="color:#ffffff;font-weight:700;font-size:16px;margin-left:8px;vertical-align:middle">3dPlateBuilder.com</span>
</td></tr>
<tr><td style="padding:28px">
<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3">{{ $heading }}</h1>
@foreach ($lines as $line)
<p style="margin:0 0 14px;font-size:15px;line-height:1.55">{{ $line }}</p>
@endforeach
@if ($url && $button)
<p style="margin:22px 0 4px"><a href="{{ $url }}" style="display:inline-block;background:#16191c;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 22px;border-radius:999px">{{ $button }}</a></p>
@endif
</td></tr>
<tr><td style="padding:18px 28px;border-top:1px solid #ececef;font-size:12.5px;color:#5b6065;line-height:1.5">
Homee Ltd trading as 3dPlateBuilder.com. Questions? Reply to this email or raise a ticket in your account.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
