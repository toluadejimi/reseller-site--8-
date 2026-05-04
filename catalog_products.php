<?php
/**
 * JSON catalog for lazy-load on /catalog (same auth rules as catalog.php).
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!file_exists(__DIR__ . '/config.php')) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['ok' => false, 'error' => 'Configuration missing.', 'products' => []]);
    exit;
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_products_lib.php';

$dbPath = defined('DB_PATH') ? DB_PATH : '';
if ($dbPath !== '') {
    require_once __DIR__ . '/init_db.php';
    require_once __DIR__ . '/auth_helpers.php';
}
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

if ($dbPath !== '' && $currentUser === null) {
    header('Content-Type: application/json; charset=utf-8', true, 401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized.', 'products' => []]);
    exit;
}

$apiKey = defined('RESELLER_API_KEY') ? RESELLER_API_KEY : '';
$baseUrl = rtrim(defined('API_BASE_URL') ? API_BASE_URL : '', '/');
$markup = (float) (function_exists('getSetting') && getSetting('markup_percent') !== null ? getSetting('markup_percent') : (defined('MARKUP_PERCENT') ? MARKUP_PERCENT : 0));
$adminExtra = (float) (function_exists('getSetting') && getSetting('admin_extra_amount') !== null ? getSetting('admin_extra_amount') : 0);

$r = reseller_fetch_catalog_products($apiKey, $baseUrl);
$products = $r['products'];
$error = $r['error'];

$out = [];
foreach ($products as $p) {
    if (!is_array($p) || !isset($p['id'])) {
        continue;
    }
    $rp = isset($p['reseller_price']) ? (float) $p['reseller_price'] : 0.0;
    $out[] = [
        'id' => (int) $p['id'],
        'name' => isset($p['name']) ? (string) $p['name'] : '',
        'category' => isset($p['category']) ? (string) $p['category'] : '',
        'in_stock' => isset($p['in_stock']) ? (int) $p['in_stock'] : 0,
        'image_url' => isset($p['image_url']) ? (string) $p['image_url'] : '',
        'amount' => round($rp * (1 + $markup / 100) + $adminExtra, 2),
    ];
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$payload = json_encode([
    'ok' => $error === '',
    'error' => $error,
    'products' => $out,
], JSON_UNESCAPED_UNICODE);
if ($payload === false) {
    $payload = '{"ok":false,"error":"Server error","products":[]}';
}
echo $payload;
