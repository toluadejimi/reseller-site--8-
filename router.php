<?php
/**
 * PHP built-in server router (php -S ignores .htaccess).
 *
 * From this project root:
 *   php -S localhost:8989 router.php
 *
 * Then /catalog, /login, /admin/orders, etc. work like on Apache.
 */
$root = __DIR__;
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? rawurldecode($uri) : '/';
if ($uri === '' || $uri === false) {
    $uri = '/';
}
if (strpos($uri, '..') !== false) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bad request';
    return;
}

// Existing file (catalog.php, assets, images, etc.) — let the server handle it
if ($uri !== '/') {
    $full = $root . $uri;
    if (is_file($full)) {
        return false;
    }
}

// fund_callback (extensionless, same as .htaccess)
if (preg_match('#^/fund_callback/?$#i', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/fund_callback.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/fund_callback.php';
    require $root . '/fund_callback.php';
    return;
}

// /admin and /admin/ → admin dashboard
if ($uri === '/admin' || $uri === '/admin/') {
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/admin/index.php';
    require $root . '/admin/index.php';
    return;
}

// /path/ → path.php when present
if (preg_match('#^(.+)/$#', $uri, $m) && $m[1] !== '') {
    $trimmed = $m[1];
    $php = $root . $trimmed . '.php';
    if (is_file($php)) {
        $_SERVER['SCRIPT_NAME'] = $trimmed . '.php';
        $_SERVER['SCRIPT_FILENAME'] = $php;
        require $php;
        return;
    }
}

// /path → path.php
if ($uri !== '/') {
    $php = $root . $uri . '.php';
    if (is_file($php)) {
        $_SERVER['SCRIPT_NAME'] = $uri . '.php';
        $_SERVER['SCRIPT_FILENAME'] = $php;
        require $php;
        return;
    }
}

// Home
if ($uri === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
    require $root . '/index.php';
    return;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
