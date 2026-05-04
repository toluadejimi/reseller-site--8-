<?php
/**
 * Reseller Mini-Site - Landing page (shop lives on catalog.php).
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists(__DIR__ . '/config.php')) {
    die('Please copy config.sample.php to config.php and set your API key and API_BASE_URL.');
}
require_once __DIR__ . '/config.php';

$includesDir = __DIR__ . '/includes';
$requiredIncludes = ['head.php', 'header.php', 'footer.php'];
foreach ($requiredIncludes as $file) {
    if (!is_file($includesDir . '/' . $file)) {
        header('Content-Type: text/plain; charset=utf-8');
        die('Missing file: includes/' . $file . '. Upload the full reseller-site folder including the includes/ directory (head.php, header.php, footer.php). Path checked: ' . $includesDir . '/' . $file);
    }
}
$dbPath = defined('DB_PATH') ? DB_PATH : '';
if ($dbPath !== '') {
    require_once __DIR__ . '/init_db.php';
    require_once __DIR__ . '/auth_helpers.php';
}
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

$siteTitle = (function_exists('getSetting') && getSetting('site_title') !== null && getSetting('site_title') !== '') ? getSetting('site_title') : (defined('SITE_TITLE') ? SITE_TITLE : 'Reseller Store');
$businessName = (function_exists('getSetting') && getSetting('business_name') !== null && getSetting('business_name') !== '') ? getSetting('business_name') : (defined('BUSINESS_NAME') ? BUSINESS_NAME : $siteTitle);
$logoUrl = (function_exists('getSetting') && getSetting('logo_url') !== null) ? trim((string)getSetting('logo_url')) : (defined('LOGO_URL') ? trim(LOGO_URL) : '');

$pageTitle = $businessName;

$bodyClass = 'page-reseller-index';
$layout = 'wide';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <?php if ($dbPath !== '' && !$currentUser): ?>
        <div class="reseller-auth-prompt">
            <a href="login.php?redirect=<?php echo urlencode('catalog.php'); ?>">Login</a> or <a href="register.php">Register</a> to manage your wallet and orders.
        </div>
    <?php endif; ?>

    <section class="landing-hero" aria-labelledby="landing-hero-heading">
        <div class="landing-hero__grid">
            <div class="landing-hero__copy">
                <p class="landing-eyebrow">Social growth, simplified</p>
                <h1 id="landing-hero-heading" class="landing-hero__title">Easy social accounts that boost your business</h1>
                <p class="landing-hero__lead">Skip the slow setup and unreliable sources. Stock up on quality social presence and digital services in one place—clear pricing, fast fulfilment, and a checkout flow built for people who resell, run ads, or scale brands every day.</p>
                <div class="landing-hero__actions">
                    <a href="catalog.php" class="btn btn-primary landing-hero__btn-primary">Browse catalog</a>
                    <?php if ($dbPath !== '' && !$currentUser): ?>
                        <a href="register.php" class="btn btn-secondary landing-hero__btn-secondary">Create free account</a>
                    <?php elseif ($dbPath !== '' && $currentUser): ?>
                        <a href="wallet.php" class="btn btn-secondary landing-hero__btn-secondary">Fund wallet</a>
                    <?php endif; ?>
                </div>
                <ul class="landing-hero__bullets" aria-label="Key benefits">
                    <li>Accounts and services curated for campaigns, outreach, and resale</li>
                    <li>Wallet-backed orders when you enable customer accounts</li>
                    <li>Order history and support channels when you need a hand</li>
                </ul>
            </div>
            <div class="landing-hero__visual" aria-hidden="true">
                <div class="landing-hero__card landing-hero__card--main">
                    <span class="landing-hero__card-label">Ready when you are</span>
                    <strong class="landing-hero__card-title">Social stack on demand</strong>
                    <p class="landing-hero__card-text">Order what you need, scale what works, repeat without friction.</p>
                    <div class="landing-hero__card-metrics">
                        <div><span class="landing-hero__metric-value">Fast</span><span class="landing-hero__metric-label">delivery</span></div>
                        <div><span class="landing-hero__metric-value">Simple</span><span class="landing-hero__metric-label">checkout</span></div>
                        <div><span class="landing-hero__metric-value">Clear</span><span class="landing-hero__metric-label">pricing</span></div>
                    </div>
                </div>
                <div class="landing-hero__card landing-hero__card--accent">
                    <span class="landing-hero__pill">For teams &amp; resellers</span>
                    <p class="landing-hero__accent-text">Grow reach, test creatives, and serve clients—with inventory that matches how you work.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-value" aria-labelledby="landing-value-heading">
        <div class="landing-value__header">
            <h2 id="landing-value-heading" class="landing-section-title">Why businesses choose this store</h2>
            <p class="landing-section-sub">Social proof and distribution move fast. Your supply chain for accounts and related services should keep up—without compromising clarity or trust.</p>
        </div>
        <div class="landing-value__grid">
            <article class="landing-card">
                <div class="landing-card__icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 class="landing-card__title">Built for growth</h3>
                <p class="landing-card__text">Whether you run a small agency or a high-volume resale desk, easy access to social accounts helps you launch campaigns, onboard clients, and iterate without bottlenecks.</p>
            </article>
            <article class="landing-card">
                <div class="landing-card__icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M12 12v.01"/></svg>
                </div>
                <h3 class="landing-card__title">Straightforward buying</h3>
                <p class="landing-card__text">Browse by category on our catalog, see stock and price up front, and check out in a few clicks. Optional wallet funding keeps repeat purchases smooth for your customers.</p>
            </article>
            <article class="landing-card">
                <div class="landing-card__icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3 class="landing-card__title">Operations you can trust</h3>
                <p class="landing-card__text">Orders are recorded and traceable. When something needs attention, you have a path to follow up—so you can focus on revenue, not chaos.</p>
            </article>
        </div>
    </section>

    <section class="landing-steps" aria-labelledby="landing-steps-heading">
        <h2 id="landing-steps-heading" class="landing-section-title landing-section-title--center">From browse to boost in three steps</h2>
        <ol class="landing-steps__list">
            <li class="landing-step"><span class="landing-step__num">1</span><span class="landing-step__body"><strong class="landing-step__title">Pick your products</strong><span class="landing-step__desc">Find the social accounts and services that match your funnel, niche, or client brief.</span></span></li>
            <li class="landing-step"><span class="landing-step__num">2</span><span class="landing-step__body"><strong class="landing-step__title">Check out securely</strong><span class="landing-step__desc">Use your wallet balance when registered, or complete purchase flow as enabled on this store.</span></span></li>
            <li class="landing-step"><span class="landing-step__num">3</span><span class="landing-step__body"><strong class="landing-step__title">Deploy and scale</strong><span class="landing-step__desc">Put accounts to work for ads, organic reach, or resale—then reorder as demand grows.</span></span></li>
        </ol>
        <p class="landing-steps__cta"><a href="catalog.php" class="btn btn-primary landing-hero__btn-primary">Open catalog</a></p>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>
