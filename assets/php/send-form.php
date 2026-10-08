<?php
/**
 * Contact-form mail handler for the Ahmad Jan site.
 *
 * Receives the form's fields as JSON, validates/sanitizes them, saves the lead
 * to disk, and emails it via authenticated SMTP (fallback: PHP mail()).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ---- config -----------------------------------------------------------
$recipient = ['developerajcreationz@gmail.com', 'ahmadjan1012@gmail.com'];
$siteName  = 'Ahmad Jan — Contact Form';

// ---- only accept POST --------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ---- read + decode body -------------------------------------------------
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST; // fallback for a normal form-encoded submit
}

function field(array $data, string $key, int $maxLen = 500): string
{
    $val = isset($data[$key]) ? (string) $data[$key] : '';
    $val = trim($val);
    // strip anything that could be used for header injection in an email client
    $val = preg_replace('/[\r\n]+/', ' ', $val) ?? '';
    if (function_exists('mb_substr')) {
        $val = mb_substr($val, 0, $maxLen);
    } else {
        $val = substr($val, 0, $maxLen);
    }
    return $val;
}

// Honeypot: a hidden field named "company" that real visitors never fill in.
$honeypot = field($data, 'company', 100);
if ($honeypot !== '') {
    // Silently pretend success so bots don't learn anything, but send nothing.
    echo json_encode(['success' => true]);
    exit;
}

$name       = field($data, 'name', 120);
$email      = field($data, 'email', 200);
$store      = field($data, 'store', 300);
$budgetMin  = field($data, 'budgetMin', 20);
$budgetMax  = field($data, 'budgetMax', 20);
$videosMin  = field($data, 'videosMin', 20);
$videosMax  = field($data, 'videosMax', 20);
// free-sample form on the main page (all optional, so the other pages' forms are unaffected)
$clip       = field($data, 'clip', 500);
$platform   = field($data, 'platform', 40);
$about      = field($data, 'about', 1500);
$plan       = field($data, 'plan', 40);
$isSample   = ($clip !== '');

$errors = [];
if ($name === '') {
    $errors[] = 'name';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fill in a valid name and email.', 'fields' => $errors]);
    exit;
}

// ---- build the email -----------------------------------------------------
$subject = $isSample ? "Free sample request: {$name}" : "New lead from the site: {$name}";

if ($isSample) {
    $bodyLines = [
        "New FREE SAMPLE request from the Raw-to-Reel page.",
        "",
        "Name:         {$name}",
        "Email:        {$email}",
        "Raw clip:     {$clip}",
        "Posts on:     " . ($platform !== '' ? $platform : '—'),
        "Package:      " . ($plan !== '' ? $plan : '—'),
        "Video is about: " . ($about !== '' ? $about : '—'),
        "",
        "Submitted: " . date('Y-m-d H:i:s T'),
    ];
} else {
$bodyLines = [
    "New submission from the Ahmad Jan contact form.",
    "",
    "Name:              {$name}",
    "Email:             {$email}",
    "Shopify store:     " . ($store !== '' ? $store : '—'),
    "Budget per video:  " . ($budgetMin !== '' || $budgetMax !== '' ? "\${$budgetMin} - \${$budgetMax}" : '—'),
    "Ad videos / month: " . ($videosMin !== '' || $videosMax !== '' ? "{$videosMin} - {$videosMax}" : '—'),
    "",
    "Submitted: " . date('Y-m-d H:i:s T'),
];
}
$body = implode("\n", $bodyLines);

// ---- delivery -------------------------------------------------------------
// 1) Save the lead to disk first, so it survives even if every mail route fails.
// 2) Send through the site's own mailbox over authenticated SMTP (best
//    deliverability). Credentials live in smtp-config.php, ABOVE the web root
//    (never in git, never web-accessible). See smtp-config.example.php.
// 3) If SMTP is not configured or fails, fall back to PHP mail().
// The visitor only sees an error if all of it fails.

$baseDir = dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
$cfg = [];
// Preferred: above the web root. Alternative: /_private/ inside it (blocked from the web by its .htaccess).
foreach ([$baseDir . '/smtp-config.php', ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/_private/smtp-config.php'] as $cfgFile) {
    if (is_file($cfgFile)) {
        $loaded = include $cfgFile;
        if (is_array($loaded)) {
            $cfg = $loaded;
            break;
        }
    }
}

// -- 1) durable copy
$saved = false;
$leadDir = null;
foreach ([$baseDir . '/form-leads', ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/_private/form-leads'] as $d) {
    if ((is_dir($d) || @mkdir($d, 0750, true)) && is_writable($d)) {
        $leadDir = $d;
        break;
    }
}
if ($leadDir !== null) {
    $entry = "==== " . date('c') . " ====\nSubject: {$subject}\n{$body}\n\n";
    $saved = @file_put_contents($leadDir . '/leads.log', $entry, FILE_APPEND | LOCK_EX) !== false;
}
if (!$saved) {
    error_log("send-form lead (could not write file): {$subject} | {$email} | " . str_replace("\n", ' / ', $body));
}

// -- 2) SMTP
function smtp_send(array $c, string $to, string $subject, string $body, string $replyName, string $replyEmail, string &$err): bool
{
    $host = $c['host'] ?? 'smtp.hostinger.com';
    $port = (int) ($c['port'] ?? 465);
    $user = $c['user'] ?? '';
    $pass = $c['pass'] ?? '';
    $from = $c['from'] ?? $user;
    $fromName = $c['from_name'] ?? 'Website form';
    if ($user === '' || $pass === '') {
        $err = 'smtp not configured';
        return false;
    }
    $fp = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port, $en, $es, 15);
    if (!$fp) {
        $err = "connect failed: {$es}";
        return false;
    }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };
    $cmd = function (string $line, string $ok) use ($fp, $read, &$err): bool {
        fwrite($fp, $line . "\r\n");
        $r = $read();
        if (strpos($r, $ok) !== 0) {
            $err = trim($r);
            return false;
        }
        return true;
    };
    $ehlo = 'supads.ajcreationz.co';
    if (strpos($read(), '220') !== 0) { $err = 'bad greeting'; fclose($fp); return false; }
    if (!$cmd("EHLO {$ehlo}", '250')) { fclose($fp); return false; }
    if ($port !== 465) {
        if (!$cmd('STARTTLS', '220') || !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err = $err ?: 'starttls failed'; fclose($fp); return false; }
        if (!$cmd("EHLO {$ehlo}", '250')) { fclose($fp); return false; }
    }
    if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode($user), '334') || !$cmd(base64_encode($pass), '235')) { fclose($fp); return false; }
    if (!$cmd("MAIL FROM:<{$from}>", '250') || !$cmd("RCPT TO:<{$to}>", '250') || !$cmd('DATA', '354')) { fclose($fp); return false; }

    $enc = function (string $v): string { return '=?UTF-8?B?' . base64_encode($v) . '?='; };
    $msg  = "From: {$enc($fromName)} <{$from}>\r\n";
    $msg .= "To: <{$to}>\r\n";
    $msg .= "Reply-To: {$enc($replyName)} <{$replyEmail}>\r\n";
    $msg .= "Subject: {$enc($subject)}\r\n";
    $msg .= 'Date: ' . date('r') . "\r\n";
    $msg .= 'Message-ID: <' . bin2hex(random_bytes(12)) . '@supads.ajcreationz.co>' . "\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($body));
    fwrite($fp, $msg . "\r\n.\r\n");
    $r = $read();
    $ok = strpos($r, '250') === 0;
    if (!$ok) { $err = trim($r); }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

$recipients = $cfg['to'] ?? $recipient;
$recipients = is_array($recipients) ? $recipients : [$recipients];

$sent = false;
$smtpErr = '';
foreach ($recipients as $to) {
    if (smtp_send($cfg, (string) $to, $subject, $body, $name, $email, $smtpErr)) {
        $sent = true;
    }
}
if (!$sent && $smtpErr !== '') {
    error_log('send-form SMTP failed: ' . $smtpErr);
}

// -- 3) fallback: PHP mail()
if (!$sent) {
    $fromDomain = 'localhost';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $fromDomain = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['HTTP_HOST']);
    }
    $headers = [];
    $headers[] = "From: {$siteName} <no-reply@{$fromDomain}>";
    $headers[] = "Reply-To: {$name} <{$email}>";
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    foreach ($recipients as $to) {
        if (@mail((string) $to, $subject, $body, implode("\r\n", $headers))) {
            $sent = true;
        }
    }
}

// The lead is on disk even if mail failed, so only report failure when nothing worked at all.
if ($sent || $saved) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not send the message right now. Please try again shortly, or email us directly.',
    ]);
}
