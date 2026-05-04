<?php
$layout = isset($layout) ? $layout : 'default';
$authSplitPage = !empty($authSplitPage);
$businessName = isset($businessName) ? $businessName : (defined('BUSINESS_NAME') ? BUSINESS_NAME : 'Store');
$logoUrl = isset($logoUrl) ? $logoUrl : (defined('LOGO_URL') ? trim(LOGO_URL) : '');
$currentUser = isset($currentUser) ? $currentUser : (function_exists('getCurrentUser') ? getCurrentUser() : null);
$dbPath = isset($dbPath) ? $dbPath : (defined('DB_PATH') ? DB_PATH : '');
$catalogHref = '/catalog';
if (!$authSplitPage && $dbPath !== '' && !$currentUser) {
    $catalogHref = '/login?redirect=' . rawurlencode('/catalog');
}
?>
<?php if ($authSplitPage): ?>
<div class="site-wrap site-wrap--auth-split">
<?php else: ?>
<header class="site-header">
    <div class="site-header-inner">
        <a href="/" class="site-brand">
            <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($businessName); ?>" class="site-logo">
            <?php endif; ?>
            <span class="site-title"><?php echo htmlspecialchars($businessName); ?></span>
        </a>
        <div class="site-header-end">
        <button type="button" class="theme-toggle js-theme-toggle" aria-label="Switch color theme" aria-pressed="false" title="Theme">
            <svg class="theme-toggle__sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <svg class="theme-toggle__moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a href="<?php echo htmlspecialchars($catalogHref); ?>" class="site-header-catalog">Catalog</a>
        <?php if ($dbPath !== ''): ?>
        <button type="button" class="site-nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">
            <span class="site-nav-toggle__bar"></span>
            <span class="site-nav-toggle__bar"></span>
            <span class="site-nav-toggle__bar"></span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Main">
            <?php if ($currentUser): ?>
                <?php
                $headerBalance = (function_exists('getWalletBalance')) ? getWalletBalance((int)$currentUser['id']) : 0;
                ?>
                <a href="/wallet" class="nav-wallet" title="My Wallet">₦<?php echo number_format($headerBalance, 2); ?></a>
                <a href="/my_orders">My Orders</a>
                <a href="/profile">Profile</a>
                <a href="/logout">Logout</a>
            <?php else: ?>
                <a href="/login">Login</a>
                <a href="/register">Register</a>
            <?php endif; ?>
        </nav>
        <script>(function(){var t=document.querySelector('.site-nav-toggle');var n=document.getElementById('site-nav');if(t&&n){t.addEventListener('click',function(){var o=n.classList.toggle('site-nav--open');t.setAttribute('aria-expanded',o);});document.addEventListener('click',function(e){if(!t.contains(e.target)&&!n.contains(e.target)){n.classList.remove('site-nav--open');t.setAttribute('aria-expanded','false');}});}})();</script>
        <?php endif; ?>
        </div>
    </div>
</header>
<div class="site-wrap <?php echo $layout === 'narrow' ? 'narrow' : ($layout === 'wide' ? 'wide' : ''); ?>">
<?php endif; ?>
