<?php
/**
 * SprintPay callback: credit user wallet after successful payment.
 * Matches the main site’s e_fund webhook format (ApiController@e_fund).
 *
 * Expected webhook request (POST, form or JSON):
 *   - order_id  (required) – same as ref we sent to SprintPay (fund request reference)
 *   - amount    (required) – amount to credit
 *   - email     (optional) – used on main site; reseller looks up by order_id only
 *
 * Reference is read from: order_id, ref, trans_id, reference, referenceid, reference_id.
 * If order_id + amount are present, payment is treated as success (no status field needed).
 * If SPRINTPAY_VERIFY_ENABLED is true, we verify ref via {API_BASE_URL}/api/verify-transaction
 * and only credit when it returns message "completed" (status == 4).
 * Response: JSON with status, message, and optionally order_id, amount, credited.
 */
require_once __DIR__ . '/init_db.php';
require_once __DIR__ . '/auth_helpers.php';

$__reseller_callback_code = 200;
$__reseller_callback_json = ['status' => true, 'message' => 'OK'];

$pdo = getDb();
if (!$pdo) {
    $__reseller_callback_code = 503;
    $__reseller_callback_json = ['status' => false, 'message' => 'Database not configured.'];
    goto send;
}

$jsonBody = [];
$raw = file_get_contents('php://input');
if ($raw !== false && $raw !== '' && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
    }
}
$input = array_merge($jsonBody, $_GET, $_POST);

function fund_callback_normalize_auth_header(): string
{
    $h = '';
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower((string) $k) === 'authorization') {
                $h = trim((string) $v);
                break;
            }
        }
    }
    if ($h === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $h = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
    }
    if ($h === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $h = trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    if (stripos($h, 'Bearer ') === 0) {
        $h = trim(substr($h, 7));
    }
    return $h;
}

function fund_callback_secret_debug_meta(string $provided, string $expected): array
{
    $dbg = [
        'expected_key_len' => strlen($expected),
        'provided_key_len' => strlen($provided),
        'provided_via' => $provided !== '' ? 'body_or_query' : 'none',
    ];
    $auth = fund_callback_normalize_auth_header();
    if ($auth !== '') {
        $dbg['provided_via'] = 'authorization_header';
        $dbg['provided_auth_len'] = strlen($auth);
    }
    if ($expected !== '' && $provided !== '') {
        $dbg['body_key_matches'] = hash_equals($expected, $provided);
    }
    if ($expected !== '' && $auth !== '') {
        $dbg['auth_matches'] = hash_equals($expected, $auth);
    }
    return $dbg;
}

$providedKey = trim((string) ($input['key'] ?? ''));
$authToken = fund_callback_normalize_auth_header();
$expectedKey = '';
if (defined('ENKPAY_WEBHOOK_KEY')) {
    $expectedKey = (string) ENKPAY_WEBHOOK_KEY;
}

// Optional validation: if ENKPAY_WEBHOOK_KEY is set, require matching key.
if ($expectedKey !== '') {
    // EnkPay may send both a JSON `key` and an `Authorization` header; accept either.
    $hasAny = ($providedKey !== '' || $authToken !== '');
    if (!$hasAny) {
        $__reseller_callback_code = 401;
        $__reseller_callback_json = ['status' => false, 'message' => 'Unauthorized.'];
        if (defined('FUND_CALLBACK_DEBUG') && FUND_CALLBACK_DEBUG) {
            $__reseller_callback_json['debug'] = fund_callback_secret_debug_meta('', $expectedKey);
        }
        goto send;
    }
    $bodyOk = false;
    $authOk = false;
    if ($providedKey !== '') {
        $bodyOk = function_exists('hash_equals') ? hash_equals($expectedKey, $providedKey) : ($expectedKey === $providedKey);
    }
    if ($authToken !== '') {
        $authOk = function_exists('hash_equals') ? hash_equals($expectedKey, $authToken) : ($expectedKey === $authToken);
    }
    $okKey = ($bodyOk || $authOk);
    if (!$okKey) {
        $__reseller_callback_code = 401;
        $__reseller_callback_json = ['status' => false, 'message' => 'Unauthorized.'];
        if (defined('FUND_CALLBACK_DEBUG') && FUND_CALLBACK_DEBUG) {
            $__reseller_callback_json['debug'] = fund_callback_secret_debug_meta($providedKey, $expectedKey);
        }
        goto send;
    }
}

$reference = trim((string)($input['order_id'] ?? $input['ref'] ?? $input['trans_id'] ?? $input['reference'] ?? $input['referenceid'] ?? $input['reference_id'] ?? ''));
$amount = (float)($input['amount'] ?? 0);
$status = $input['status'] ?? $input['transaction_status'] ?? '';

if ($reference === '' || $amount <= 0) {
    $__reseller_callback_code = 400;
    $__reseller_callback_json = ['status' => false, 'message' => 'Missing order_id or amount.'];
    goto send;
}

function verifySprintPayTransaction(string $ref): array
{
    $base = '';
    if (defined('SPRINTPAY_VERIFY_BASE_URL') && trim((string) SPRINTPAY_VERIFY_BASE_URL) !== '') {
        $base = rtrim((string) SPRINTPAY_VERIFY_BASE_URL, '/');
    } else {
        $base = rtrim(defined('API_BASE_URL') ? (string) API_BASE_URL : '', '/');
    }
    $enabled = defined('SPRINTPAY_VERIFY_ENABLED') ? (bool) SPRINTPAY_VERIFY_ENABLED : false;
    if (!$enabled) {
        return ['ok' => true, 'amount' => null, 'message' => 'verification disabled'];
    }
    if ($base === '') {
        return ['ok' => false, 'amount' => null, 'message' => 'Verify base URL not set'];
    }
    $url = $base . '/api/verify-transaction';
    $apiKey = defined('RESELLER_API_KEY') ? (string) RESELLER_API_KEY : '';
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    if ($apiKey !== '') {
        $headers[] = 'X-Api-Key: ' . $apiKey;
    }
    $payload = json_encode(['ref' => $ref], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return ['ok' => false, 'amount' => null, 'message' => 'verification encode failed'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$res) {
        return ['ok' => false, 'amount' => null, 'message' => 'verification failed', 'http_code' => $code];
    }
    // WAF/HTML pages (Imunify360, etc.) may return HTTP 200 with HTML; never treat as JSON success.
    $trim = ltrim((string) $res);
    if ($trim !== '' && ($trim[0] === '<' || stripos($res, 'Imunify360') !== false)) {
        return ['ok' => false, 'amount' => null, 'message' => 'verification blocked (WAF/HTML response)', 'http_code' => $code];
    }
    $data = json_decode($res, true);
    if (!is_array($data)) {
        return ['ok' => false, 'amount' => null, 'message' => 'verification invalid response'];
    }
    $msg = strtolower((string)($data['message'] ?? ''));
    $statusOk = !empty($data['status']);
    if ($statusOk && $msg === 'completed') {
        $amt = null;
        if (isset($data['data']['amount'])) {
            $amt = (float) $data['data']['amount'];
        }
        return ['ok' => true, 'amount' => $amt, 'message' => 'completed'];
    }
    return ['ok' => false, 'amount' => null, 'message' => $data['message'] ?? 'incomplete', 'http_code' => $code];
}

// Same as e_fund: webhook is only called on success, so having order_id + amount = success
$isSuccess = (
    $status === '1' || $status === 1 ||
    strtolower((string)$status) === 'success' ||
    strtolower((string)$status) === 'completed' ||
    strtolower((string)$status) === 'successful' ||
    $status === '' || $status === null
);

if ($isSuccess) {
    $v = verifySprintPayTransaction($reference);
    if (empty($v['ok'])) {
        $__reseller_callback_code = 422;
        $__reseller_callback_json = [
            'status' => false,
            'message' => 'Something went wrong. Please try again.',
            'order_id' => $reference,
            'credited' => false,
        ];
        if (defined('FUND_CALLBACK_DEBUG') && FUND_CALLBACK_DEBUG) {
            $__reseller_callback_json['debug'] = [
                'verify_message' => (string) ($v['message'] ?? ''),
                'verify_http_code' => $v['http_code'] ?? null,
            ];
        }
        goto send;
    }
    if (isset($v['amount']) && $v['amount'] !== null && (float) $v['amount'] > 0) {
        $amount = (float) $v['amount'];
    }
    $ok = completeFundRequestByReference($reference, $amount);
    if ($ok) {
        $__reseller_callback_json = [
            'status' => true,
            'message' => 'NGN ' . number_format($amount, 2) . ' has been successfully added to your wallet.',
            'order_id' => $reference,
            'amount' => $amount,
            'credited' => true,
        ];
    } else {
        $__reseller_callback_json = [
            'status' => true,
            'message' => 'Transaction already confirmed or not found.',
            'order_id' => $reference,
            'amount' => $amount,
            'credited' => false,
        ];
    }
    goto send;
}

$__reseller_callback_json = [
    'status' => true,
    'message' => 'OK',
    'order_id' => $reference,
    'amount' => $amount,
];

send:
$__reseller_callback_body = json_encode($__reseller_callback_json);
if (defined('RESELLER_CALLBACK_VIA_LARAVEL')) {
    return;
}
http_response_code($__reseller_callback_code);
header('Content-Type: application/json; charset=utf-8');
echo $__reseller_callback_body;
exit;
