<?php
/**
 * Email delivery — dependency-free.
 *
 * Two drivers, chosen by the 'mail_driver' system_setting:
 *   'mail' (default) — PHP's built-in mail(). Works out of the box on
 *                       servers with sendmail/postfix configured (most
 *                       shared hosting and most production Linux boxes).
 *                       Does nothing useful in local dev without one.
 *   'smtp'            — a minimal hand-rolled SMTP client (AUTH LOGIN,
 *                       optional STARTTLS) for pointing at an external
 *                       relay (a corporate SMTP server, or a provider's
 *                       SMTP endpoint) when the server has no local MTA.
 *
 * Email notifications are OFF by default (system_settings.
 * email_notifications_enabled = '0') — turn them on and configure a
 * driver in Administration once you have somewhere for mail to
 * actually go. Until then, notify() still works exactly as before
 * (in-app only); this file only adds an optional extra delivery path.
 */

function mail_setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        try {
            $stmt = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :k');
            $stmt->execute(['k' => $key]);
            $value = $stmt->fetchColumn();
            $cache[$key] = $value !== false ? $value : $default;
        } catch (PDOException $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

function email_notifications_enabled(): bool
{
    return mail_setting('email_notifications_enabled', '0') === '1';
}

/**
 * Sends a plain-text email. Returns true on apparent success. Never
 * throws — a failed email should never take down the action that
 * triggered it; failures are written to the audit log instead.
 */
function send_email(string $toEmail, string $toName, string $subject, string $body): bool
{
    if (!email_notifications_enabled()) {
        return false;
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromAddress = mail_setting('mail_from_address', 'no-reply@npf-lms.local');
    $fromName = mail_setting('mail_from_name', 'NPF Leave Management System');
    $driver = mail_setting('mail_driver', 'mail');

    try {
        $ok = $driver === 'smtp'
            ? smtp_send($toEmail, $toName, $fromAddress, $fromName, $subject, $body)
            : php_mail_send($toEmail, $toName, $fromAddress, $fromName, $subject, $body);

        if (!$ok) {
            error_log("send_email() failed via driver '{$driver}' to {$toEmail}");
        }
        return $ok;
    } catch (Throwable $e) {
        error_log('send_email() exception: ' . $e->getMessage());
        return false;
    }
}

function php_mail_send(string $toEmail, string $toName, string $fromAddress, string $fromName, string $subject, string $body): bool
{
    $to = sprintf('%s <%s>', mb_encode_mimeheader($toName, 'UTF-8'), $toEmail);
    $headers = implode("\r\n", [
        'From: ' . sprintf('%s <%s>', mb_encode_mimeheader($fromName, 'UTF-8'), $fromAddress),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: NPF-LMS',
    ]);
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

    return @mail($to, $encodedSubject, $body, $headers);
}

/**
 * Minimal SMTP client: connects, EHLO, optional STARTTLS, AUTH LOGIN,
 * MAIL FROM/RCPT TO/DATA. No queuing, no retries — one message, one
 * connection, synchronous. Configure via system_settings (smtp_host,
 * smtp_port, smtp_username, smtp_password, smtp_encryption).
 */
function smtp_send(string $toEmail, string $toName, string $fromAddress, string $fromName, string $subject, string $body): bool
{
    $host = mail_setting('smtp_host');
    $port = (int) mail_setting('smtp_port', '587');
    $username = mail_setting('smtp_username');
    $password = mail_setting('smtp_password');
    $encryption = mail_setting('smtp_encryption', 'tls'); // 'tls' (STARTTLS) | 'ssl' | 'none'

    if ($host === '') {
        error_log('smtp_send(): smtp_host is not configured');
        return false;
    }

    $transport = $encryption === 'ssl' ? 'ssl://' : '';
    $fp = @stream_socket_client("{$transport}{$host}:{$port}", $errno, $errstr, 10);
    if (!$fp) {
        error_log("smtp_send(): connection failed — {$errstr}");
        return false;
    }
    stream_set_timeout($fp, 10);

    $read = function () use ($fp): string {
        $data = '';
        while ($line = fgets($fp, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line of a multi-line reply
        }
        return $data;
    };
    $write = function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };
    $expect = function (string $expectedCode) use ($read): bool {
        $response = $read();
        return str_starts_with($response, $expectedCode);
    };

    if (!$expect('220')) { fclose($fp); return false; }

    $write('EHLO npf-lms');
    if (!$expect('250')) { fclose($fp); return false; }

    if ($encryption === 'tls') {
        $write('STARTTLS');
        if (!$expect('220')) { fclose($fp); return false; }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp); return false;
        }
        $write('EHLO npf-lms');
        if (!$expect('250')) { fclose($fp); return false; }
    }

    if ($username !== '') {
        $write('AUTH LOGIN');
        if (!$expect('334')) { fclose($fp); return false; }
        $write(base64_encode($username));
        if (!$expect('334')) { fclose($fp); return false; }
        $write(base64_encode($password));
        if (!$expect('235')) { fclose($fp); return false; }
    }

    $write('MAIL FROM:<' . $fromAddress . '>');
    if (!$expect('250')) { fclose($fp); return false; }

    $write('RCPT TO:<' . $toEmail . '>');
    if (!$expect('250')) { fclose($fp); return false; }

    $write('DATA');
    if (!$expect('354')) { fclose($fp); return false; }

    $headers = implode("\r\n", [
        'From: ' . sprintf('%s <%s>', $fromName, $fromAddress),
        'To: ' . sprintf('%s <%s>', $toName, $toEmail),
        'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Date: ' . date('r'),
    ]);
    // Per RFC 5321, lines consisting only of "." must be escaped by doubling the leading dot.
    $escapedBody = preg_replace('/^\./m', '..', $body);
    $write($headers . "\r\n\r\n" . $escapedBody . "\r\n.");
    if (!$expect('250')) { fclose($fp); return false; }

    $write('QUIT');
    fclose($fp);
    return true;
}
