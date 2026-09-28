<?php
if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $ambiente_local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);

    if (!$https && !$ambiente_local) {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: https://' . $host . $uri, true, 301);
        exit();
    }

    $secure = $https;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
