<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$company = trim((string) ($_POST['company'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$honeypot = trim((string) ($_POST['website'] ?? ''));

if ($honeypot !== '') {
    echo json_encode(['ok' => true, 'message' => 'Thank you.']);
    exit;
}

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please complete the required fields with a valid email address.']);
    exit;
}

$smtpHost = getenv('SMTP_HOST') ?: '';
$smtpUsername = getenv('SMTP_USERNAME') ?: '';
$smtpPassword = getenv('SMTP_PASSWORD') ?: '';
$smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
$smtpEncryption = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls');
$recipient = getenv('CONTACT_TO') ?: 'GM@amaraglobal-resources.com';
$fromAddress = getenv('CONTACT_FROM') ?: ($smtpUsername ?: 'noreply@amaraglobal-resources.com');

if ($smtpHost === '' || $smtpUsername === '' || $smtpPassword === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'The contact service is not configured yet.']);
    exit;
}

$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeCompany = htmlspecialchars($company ?: 'Not provided', ENT_QUOTES, 'UTF-8');
$safePhone = htmlspecialchars($phone ?: 'Not provided', ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->Port = $smtpPort;

    if ($smtpEncryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($fromAddress, 'Amara Global Website');
    $mail->addAddress($recipient);
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->Subject = 'New website inquiry from ' . $safeName;
    $mail->Body = "<h2>New website inquiry</h2><p><strong>Name:</strong> {$safeName}</p><p><strong>Email:</strong> {$safeEmail}</p><p><strong>Company:</strong> {$safeCompany}</p><p><strong>Phone:</strong> {$safePhone}</p><p><strong>Message:</strong><br>{$safeMessage}</p>";
    $mail->AltBody = "New website inquiry\n\nName: {$name}\nEmail: {$email}\nCompany: {$company}\nPhone: {$phone}\n\nMessage:\n{$message}";
    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Thank you — we will be in touch shortly.']);
} catch (Exception $exception) {
    error_log('Contact form mail error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'We could not send your message right now. Please try again shortly.']);
}
