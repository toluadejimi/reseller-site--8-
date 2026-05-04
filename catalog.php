<?php
/**
 * Reseller Mini-Site - Product catalog and orders (API).
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists(__DIR__ . '/config.php')) {
    die('Please copy config.sample.php to config.php and set your API key and API_BASE_URL.');
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/catalog_products_lib.php';

$includesDir = __DIR__ . '/includes';
$requiredIncludes = ['head.php', 'header.php', 'footer.php'];
foreach ($requiredIncludes as $file) {
    if (!is_file($includesDir . '/' . $file)) {
        header('Content-Type: text/plain; charset=utf-8');
        die('Missing file: includes/' . $file . '. Upload the full reseller-site folder including the includes/ directory.');
    }
}
$dbPath = defined('DB_PATH') ? DB_PATH : '';
if ($dbPath !== '') {
    require_once __DIR__ . '/init_db.php';
    require_once __DIR__ . '/auth_helpers.php';
}
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

if ($dbPath !== '' && $currentUser === null) {
    header('Location: /login?redirect=' . urlencode('/catalog'));
    exit;
}

$apiKey = defined('RESELLER_API_KEY') ? RESELLER_API_KEY : '';
$baseUrl = rtrim(defined('API_BASE_URL') ? API_BASE_URL : '', '/');
$markup = (float)(function_exists('getSetting') && getSetting('markup_percent') !== null ? getSetting('markup_percent') : (defined('MARKUP_PERCENT') ? MARKUP_PERCENT : 0));
$adminExtra = (float)(function_exists('getSetting') && getSetting('admin_extra_amount') !== null ? getSetting('admin_extra_amount') : 0);
$siteTitle = (function_exists('getSetting') && getSetting('site_title') !== null && getSetting('site_title') !== '') ? getSetting('site_title') : (defined('SITE_TITLE') ? SITE_TITLE : 'Reseller Store');
$businessName = (function_exists('getSetting') && getSetting('business_name') !== null && getSetting('business_name') !== '') ? getSetting('business_name') : (defined('BUSINESS_NAME') ? BUSINESS_NAME : $siteTitle);
$logoUrl = (function_exists('getSetting') && getSetting('logo_url') !== null) ? trim((string)getSetting('logo_url')) : (defined('LOGO_URL') ? trim(LOGO_URL) : '');

$pageTitle = 'Catalog — ' . $businessName;

$products = [];
$orderMessage = '';
if (isset($_GET['ordered']) && $_GET['ordered'] === '1') {
    $orderMessage = 'Order successful. Your order has been recorded.';
}

$canOrder = ($dbPath === '' || $currentUser !== null);
$orderSuccessRedirect = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    if (!$canOrder) {
        header('Location: /login?redirect=' . urlencode('/catalog'));
        exit;
    }
    if ($apiKey && $baseUrl) {
        $fetched = reseller_fetch_catalog_products($apiKey, $baseUrl);
        $products = $fetched['products'];
    }
    $productId = (int) $_POST['product_id'];
    $qty = isset($_POST['qty']) ? (int) $_POST['qty'] : 1;
    $qty = max(1, min(10000, $qty));

    $sellPrice = 0;
    $productName = 'Product #' . $productId;
    foreach ($products as $p) {
        if ((int) $p['id'] === $productId) {
            $productName = $p['name'];
            $sellPrice = round($p['reseller_price'] * (1 + $markup / 100) + $adminExtra, 2);
            break;
        }
    }
    $orderTotal = round($sellPrice * $qty, 2);

    $apiPayload = [
        'product_id' => $productId,
        'qty' => $qty,
        'api_key' => $apiKey,
    ];

    if ($currentUser && $dbPath !== '' && function_exists('getWalletBalance')) {
        $userBalance = getWalletBalance((int) $currentUser['id']);
        if ($userBalance < $orderTotal) {
            $orderMessage = 'Insufficient balance. You have ₦' . number_format($userBalance, 2) . '. This order costs ₦' . number_format($orderTotal, 2) . '. Please fund your wallet (Wallet page).';
        } else {
            $ch = curl_init($baseUrl . '/api/reseller/order');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($apiPayload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'X-Api-Key: ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $orderData = $res ? json_decode($res, true) : [];
            if ($code === 200 && !empty($orderData['success'])) {
                if (function_exists('deductWalletBalance') && deductWalletBalance((int) $currentUser['id'], $orderTotal)) {
                    if (function_exists('recordOrder')) {
                        $details = '';
                        if (!empty($orderData['delivered']) && is_array($orderData['delivered'])) {
                            $parts = [];
                            foreach ($orderData['delivered'] as $d) {
                                $parts[] = isset($d['details']) ? trim((string) $d['details']) : '';
                            }
                            $details = implode("\n", array_filter($parts));
                        }
                        recordOrder((int) $currentUser['id'], $productId, $productName, $qty, $sellPrice, (string) ($orderData['order_id'] ?? ''), $details);
                    }
                    $orderSuccessRedirect = true;
                } else {
                    $orderMessage = 'Order placed with platform but wallet deduction failed. Please contact support.';
                }
            } else {
                $orderMessage = 'Order failed: ' . ($orderData['message'] ?? 'Unknown error');
            }
        }
    } else {
        $ch = curl_init($baseUrl . '/api/reseller/order');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($apiPayload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $orderData = $res ? json_decode($res, true) : [];
        if ($code === 200 && !empty($orderData['success'])) {
            if (function_exists('recordOrder')) {
                $details = '';
                if (!empty($orderData['delivered']) && is_array($orderData['delivered'])) {
                    $parts = [];
                    foreach ($orderData['delivered'] as $d) {
                        $parts[] = isset($d['details']) ? trim((string) $d['details']) : '';
                    }
                    $details = implode("\n", array_filter($parts));
                }
                recordOrder($currentUser ? (int) $currentUser['id'] : null, $productId, $productName, $qty, $sellPrice, (string) ($orderData['order_id'] ?? ''), $details);
            }
            $orderSuccessRedirect = true;
        } else {
            $orderMessage = 'Order failed: ' . ($orderData['message'] ?? 'Unknown error');
        }
    }

    if ($orderSuccessRedirect && $dbPath !== '' && $currentUser) {
        header('Location: /my_orders?ordered=1');
        exit;
    }
    if ($orderSuccessRedirect && ($dbPath === '' || !$currentUser)) {
        header('Location: /catalog?ordered=1');
        exit;
    }
}

$bodyClass = 'page-reseller-index page-catalog';
$layout = 'wide';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <?php if ($orderMessage): ?>
        <div class="alert <?php echo (strpos($orderMessage, 'failed') !== false || strpos($orderMessage, 'Insufficient') !== false) ? 'alert-error' : 'alert-success'; ?>"><p><?php echo htmlspecialchars($orderMessage); ?><?php if (strpos($orderMessage, 'success') !== false): ?> <a href="/my_orders">View My Orders</a><?php endif; ?></p></div>
    <?php endif; ?>

    <p class="catalog-back"><a href="/" class="catalog-back__link">← Home</a></p>
    <h1 class="page-title catalog-page-title">Catalog</h1>
    <p class="catalog-page-intro text-muted">Browse by category, check stock, and order. Inventory loads in a moment after you open this page.</p>

    <div
        id="catalog-root"
        class="catalog-root"
        data-products-url="/catalog_products"
        data-can-order="<?php echo $canOrder ? '1' : '0'; ?>"
        aria-busy="true"
    >
        <div id="catalog-lazy-state" class="catalog-lazy">
            <div class="catalog-lazy__inner">
                <div class="catalog-lazy__spinner" aria-hidden="true"></div>
                <p class="catalog-lazy__title">Loading catalog</p>
                <p class="catalog-lazy__text">Fetching the latest products and prices…</p>
            </div>
            <div class="catalog-skeleton" aria-hidden="true">
                <div class="catalog-skeleton__toolbar">
                    <div class="catalog-skeleton__bar catalog-skeleton__bar--search"></div>
                    <div class="catalog-skeleton__bar catalog-skeleton__bar--select"></div>
                </div>
                <div class="catalog-skeleton__heading"></div>
                <div class="catalog-skeleton__grid">
                    <?php for ($sk = 0; $sk < 8; $sk++): ?>
                    <div class="catalog-skeleton__card"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <div id="catalog-lazy-error" class="alert alert-error catalog-lazy-error" hidden>
            <p id="catalog-lazy-error-text" class="mb-0"></p>
        </div>
        <div id="catalog-main"></div>
    </div>

    <script>
    (function () {
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var t = e.target;
            if (!t || !t.closest) return;
            var a = t.closest('a');
            if (!a || a.getAttribute('target') === '_blank') return;
            var h = a.getAttribute('href');
            if (!h) return;
            if (h === '/catalog' || h === '/catalog.php') {
                document.documentElement.classList.add('catalog-nav-pending');
            }
        }, true);
    })();
    </script>
    <script src="assets/js/catalog_lazy.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
