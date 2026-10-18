<?php
/**
 * Request a demo form handler for Plesk (or any PHP host).
 * Emails each request to hello@3dplatebuilder.com and answers the form with JSON.
 * The demo form posts here (https://3dplatebuilder.com/contact.php).
 * Needs PHP mail() working on the server (Plesk: Mail settings for the domain switched on).
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$to   = 'hello@3dplatebuilder.com';            // where requests go
$from = 'noreply@3dplatebuilder.com';   // a mailbox on your own domain, so SPF/DKIM pass

function reply($ok, $message, $code = 200) {
	http_response_code($code);
	echo json_encode(['ok' => $ok, 'message' => $message]);
	exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	reply(false, 'Use the form on the Request a demo page.', 405);
}

// Spam trap: real visitors leave this hidden field empty.
if (!empty($_POST['company_url'])) {
	reply(true, 'Thanks.');
}

// One line, trimmed, no control characters (stops header injection), capped length.
function field($name, $max = 200) {
	$v = trim((string) ($_POST[$name] ?? ''));
	$v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v);
	return mb_substr($v, 0, $max);
}

$name     = field('name');
$business = field('business');
$email    = field('email');
$website  = field('website', 300);
$platform = field('platform', 80);
$orders   = field('orders', 80);
$plan     = field('plan', 40);
$message  = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 5000);

if ($name === '' || $business === '' || $platform === '' || $orders === '') {
	reply(false, 'Please fill in the required fields.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	reply(false, 'Please check your email address.', 422);
}

$body = implode("\n", [
	"Name: $name",
	"Business: $business",
	"Email: $email",
	'Website: ' . ($website !== '' ? $website : '-'),
	"Current platform: $platform",
	"Plate orders a month: $orders",
	'Plan: ' . ($plan !== '' ? $plan : 'Not sure yet'),
	'',
	$message,
	'',
	'Sent from the Request a demo form at ' . date('j M Y, H:i') . '.',
]);

$subject = '=?UTF-8?B?' . base64_encode("Demo request: $business") . '?=';
$headers = implode("\r\n", [
	"From: 3D Plate Builder <$from>",
	"Reply-To: $email",
	'MIME-Version: 1.0',
	'Content-Type: text/plain; charset=UTF-8',
]);

if (!mail($to, $subject, $body, $headers, '-f' . $from)) {
	reply(false, 'The form could not send.', 500);
}
reply(true, 'Sent.');
