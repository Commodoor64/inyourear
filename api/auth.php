<?php
// api/auth.php
// Returns the Basic Auth username, looking in every place a server might put it.
// Basic Auth itself is handled by .htaccess; this just reads who logged in.

function current_user(): string {
    // 1. PHP as an Apache module — the clean, common case.
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return $_SERVER['PHP_AUTH_USER'];
    }

    // 2. CGI / FastCGI — Apache exposes it here instead.
    if (!empty($_SERVER['REMOTE_USER']))          return $_SERVER['REMOTE_USER'];
    if (!empty($_SERVER['REDIRECT_REMOTE_USER'])) return $_SERVER['REDIRECT_REMOTE_USER'];

    // 3. Last resort: decode the raw Authorization header ourselves.
    $hdr = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';
    if (stripos($hdr, 'Basic ') === 0) {
        $decoded = base64_decode(substr($hdr, 6));
        if ($decoded !== false && str_contains($decoded, ':')) {
            return explode(':', $decoded, 2)[0];   // the part before the ':' is the username
        }
    }

    // Nothing found — server isn't passing the username through to PHP.
    return 'unknown';
}