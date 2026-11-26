<?php
// backend/services/EmailService.php

declare(strict_types=1);

final class EmailService
{
    public static function sendVerification(int $userId, string $email, string $firstName, string $token): bool
    {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost:5173'), '/');
        $link = $baseUrl . '/verify-email?token=' . $token;

        $subject = 'Verify your TEKVITO email';
        $body = self::verificationHtml($firstName, $link);

        $sent = send_email($email, $subject, $body);

        try {
            db()->prepare(
                'INSERT INTO email_logs (user_id, recipient_email, subject, template_name, delivery_status, sent_at, error_message)
                 VALUES (:uid, :to, :subj, :tpl, :status, :sent, :err)'
            )->execute([
                ':uid'    => $userId,
                ':to'     => $email,
                ':subj'   => $subject,
                ':tpl'    => 'verification',
                ':status' => $sent ? 'sent' : 'failed',
                ':sent'   => $sent ? date('Y-m-d H:i:s') : null,
                ':err'    => $sent ? null : 'PHPMailer send failed',
            ]);
        } catch (Throwable $e) {
            error_log('[EmailService] DB log failed: ' . $e->getMessage());
        }

        self::writeLogFile($email, $subject, $link, $sent ? 'sent' : 'failed');

        return $sent;
    }

    private static function verificationHtml(string $firstName, string $link): string
    {
        $safeName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:40px 20px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <tr><td style="background:linear-gradient(135deg,#7c3aed 0%,#ec4899 100%);padding:32px 40px;color:#ffffff;">
          <div style="font-size:22px;font-weight:800;letter-spacing:1px;">TEKVITO</div>
        </td></tr>
        <tr><td style="padding:40px;">
          <h1 style="margin:0 0 16px;font-size:24px;color:#111827;">Hi {$safeName},</h1>
          <p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#374151;">
            Welcome to TEKVITO! Please verify your email address to complete your registration.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;">
            <tr><td>
              <a href="{$safeLink}" style="display:inline-block;background:#111827;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">
                Verify my email
              </a>
            </td></tr>
          </table>
          <p style="margin:0 0 12px;font-size:14px;color:#6b7280;line-height:1.6;">Or copy this link into your browser:</p>
          <p style="margin:0 0 24px;font-size:13px;color:#7c3aed;word-break:break-all;">{$safeLink}</p>
          <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
            This link expires in 24 hours. If you didn't register, you can ignore this email.
          </p>
        </td></tr>
        <tr><td style="padding:24px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;text-align:center;">
          © TEKVITO — the modern hiring platform
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private static function writeLogFile(string $to, string $subject, string $link, string $status): void
    {
        $dir = __DIR__ . '/../storage/logs';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $entry = str_repeat('=', 72) . "\n"
               . 'DATE:   ' . date('c') . "\n"
               . "TO:     $to\n"
               . "SUBJ:   $subject\n"
               . "STATUS: $status\n"
               . "LINK:   $link\n\n";

        file_put_contents($dir . '/emails.log', $entry, FILE_APPEND);
    }
}