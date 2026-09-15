<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /** @return array{0: bool, 1: string} */
    public static function send(string $to, string $toName, string $subject, string $bodyHtml): array
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $host = setting('smtp_host');

            if ($host !== '') {
                $mail->isSMTP();
                $mail->Host = $host;
                $mail->Port = (int) setting('smtp_puerto', '465');
                $mail->SMTPAuth = setting('smtp_usuario') !== '';
                $mail->Username = setting('smtp_usuario');
                $mail->Password = decrypt_value(setting('smtp_clave'));
                $mail->Timeout = 20;
                $seguridad = setting('smtp_seguridad', 'ssl');
                if ($seguridad === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($seguridad === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                }
            } else {
                $mail->isMail();
            }

            $fromEmail = setting('smtp_remitente', setting('smtp_usuario', setting('ips_email')));
            if ($fromEmail === '') {
                $fromEmail = 'no-reply@' . preg_replace('/^www\./', '', (string) parse_url((string) config('app.url'), PHP_URL_HOST));
            }
            $mail->setFrom($fromEmail, setting('ips_nombre', 'IPS BIOMED'));
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::layout($subject, $bodyHtml);
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $bodyHtml))));
            $mail->send();
            return [true, ''];
        } catch (Throwable $e) {
            return [false, $mail->ErrorInfo ?: $e->getMessage()];
        }
    }

    private static function layout(string $title, string $body): string
    {
        $logo = rtrim((string) config('app.url'), '/') . '/' . logo_path('oscuro');
        $ips = e(setting('ips_nombre', 'IPS BIOMED'));
        $pie = e(trim(setting('ips_direccion') . ' · ' . setting('ips_telefono'), ' ·'));
        return <<<HTML
<!doctype html><html lang="es"><body style="margin:0;background:#eef2f7;font-family:Segoe UI,Arial,sans-serif;color:#1e293b">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden">
<tr><td style="background:#0f2a4a;padding:22px 28px"><img src="{$logo}" alt="{$ips}" height="42" style="display:block;height:42px"></td></tr>
<tr><td style="padding:28px">
<h1 style="font-size:19px;margin:0 0 14px;color:#0f2a4a">{$title}</h1>
<div style="font-size:14px;line-height:1.6">{$body}</div>
</td></tr>
<tr><td style="padding:16px 28px;background:#f8fafc;font-size:12px;color:#64748b">{$ips}<br>{$pie}</td></tr>
</table></td></tr></table></body></html>
HTML;
    }
}
