<?php
/**
 * CSRF protection. Requires an active session (config.php starts one).
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/** Call at the top of any POST handler. Stops the request cold on mismatch. */
function csrf_require(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        http_response_code(419);
        die('Your session has expired or the request could not be verified. Please go back and try again.');
    }
}
