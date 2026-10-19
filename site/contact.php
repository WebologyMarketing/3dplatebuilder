<?php
/**
 * Form handler for Plesk (or any PHP host): the Start your shop sign-up and Ask a question.
 * Emails each one to hello@3dplatebuilder.com and answers the form with JSON.
 * Both forms post here (https://3dplatebuilder.com/contact.php), with a hidden "type" field.
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
	reply(false, 'Use the form on the Start your shop page.', 405);
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

$type = field('type', 20) === 'signup' ? 'signup' : 'question';

// Label => [field name, max length]. Only these are emailed.
$fields = $type === 'signup' ? [
	'Plan'               => ['plan', 20],
	'Name'               => ['name', 200],
	'Email'              => ['email', 200],
	'Business'           => ['business', 200],
	'RNPS supplier name' => ['supplier', 200],
	'Supplier postcode'  => ['postcode', 20],
	'Current website'    => ['website', 300],
	'New shop domain'    => ['domain', 200],
	'Brand colour'       => ['colour', 20],
	'RNPS confirmed'     => ['rnps', 5],
	'Terms accepted'     => ['terms', 5],
] : [
	'Name'     => ['name', 200],
	'Email'    => ['email', 200],
	'Business' => ['business', 200],
];
$required = $type === 'signup' ? ['plan', 'name', 'email', 'business', 'supplier', 'postcode', 'rnps', 'terms'] : ['name', 'email'];

$values = [];
foreach ($fields as $label => [$name, $max]) {
	$values[$name] = field($name, $max);
}
$message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 5000);

foreach ($required as $name) {
	if ($values[$name] === '') {
		reply(false, 'Please fill in the required fields.', 422);
	}
}
if ($type === 'question' && $message === '') {
	reply(false, 'Please type your question.', 422);
}
if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
	reply(false, 'Please check your email address.', 422);
}

$lines = [];
foreach ($fields as $label => [$name, $max]) {
	$lines[] = $label . ': ' . ($values[$name] !== '' ? $values[$name] : '-');
}
$what = $type === 'signup' ? 'Start your shop' : 'Ask a question';
$body = implode("\n", array_merge($lines, ['', $message, '', "Sent from the $what form at " . date('j M Y, H:i') . '.']));

$who = $values['business'] !== '' ? $values['business'] : $values['name'];
$subject = '=?UTF-8?B?' . base64_encode(($type === 'signup' ? 'New sign-up: ' : 'Question: ') . $who) . '?=';
$headers = implode("\r\n", [
	"From: 3D Plate Builder <$from>",
	"Reply-To: {$values['email']}",
	'MIME-Version: 1.0',
	'Content-Type: text/plain; charset=UTF-8',
]);

if (!mail($to, $subject, $body, $headers, '-f' . $from)) {
	reply(false, 'The form could not send.', 500);
}
reply(true, 'Sent.');
