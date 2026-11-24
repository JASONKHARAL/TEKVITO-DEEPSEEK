<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function send_email(string $to, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = env('MAIL_HOST', '');
        $mail->SMTPAuth = true;
        $mail->Username = env('MAIL_USER', '');
        $mail->Password = env('MAIL_PASS', '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = (int) env('MAIL_PORT', '465');

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            env('MAIL_FROM', 'noreply@tekvito.com'),
            env('MAIL_FROM_NAME', 'Tekvito')
        );

        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('Email delivery failed: ' . $e->getMessage());
        return false;
    }
}