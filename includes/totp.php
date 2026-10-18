<?php
/**
 * TOTP two-factor authentication (RFC 6238), pure PHP — no Composer
 * package. Compatible with Google Authenticator, Authy, 1Password, etc.
 * since it's just standard HMAC-SHA1-based HOTP on a 30-second step.
 */

const TOTP_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32_encode(string $data): string
{
    if ($data === '') {
        return '';
    }
    $binary = '';
    foreach (str_split($data) as $byte) {
        $binary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }
    $binary = str_pad($binary, (int) (ceil(strlen($binary) / 5) * 5), '0', STR_PAD_RIGHT);

    $encoded = '';
    foreach (str_split($binary, 5) as $chunk) {
        $encoded .= TOTP_ALPHABET[bindec($chunk)];
    }
    return $encoded;
}

function base32_decode(string $encoded): string
{
    $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded));
    $binary = '';
    foreach (str_split($encoded) as $char) {
        $pos = strpos(TOTP_ALPHABET, $char);
        if ($pos === false) {
            continue;
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) < 8) {
            break; // trailing padding bits, not a full byte
        }
        $bytes .= chr(bindec($chunk));
    }
    return $bytes;
}

/** Generates a new random base32 secret (160 bits, the RFC-recommended size). */
function totp_generate_secret(): string
{
    return base32_encode(random_bytes(20));
}

function totp_code(string $base32Secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string
{
    $timestamp ??= time();
    $key = base32_decode($base32Secret);
    $counter = (int) floor($timestamp / $period);
    $counterBytes = pack('N*', 0, $counter); // 8-byte big-endian counter

    $hash = hash_hmac('sha1', $counterBytes, $key, true);
    $offset = ord($hash[19]) & 0x0f;

    $truncated = ((ord($hash[$offset]) & 0x7f) << 24)
        | (ord($hash[$offset + 1]) << 16)
        | (ord($hash[$offset + 2]) << 8)
        | ord($hash[$offset + 3]);

    $code = $truncated % (10 ** $digits);
    return str_pad((string) $code, $digits, '0', STR_PAD_LEFT);
}

/** Verifies a code, allowing $window steps of clock drift on either side (default ±30s). */
function totp_verify(string $base32Secret, string $code, int $window = 1, int $period = 30): bool
{
    $code = trim($code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($base32Secret, $now + ($i * $period), $period), $code)) {
            return true;
        }
    }
    return false;
}

function totp_provisioning_uri(string $base32Secret, string $accountLabel, string $issuer = 'NPF-LMS'): string
{
    return sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30',
        rawurlencode($issuer), rawurlencode($accountLabel), $base32Secret, rawurlencode($issuer)
    );
}
