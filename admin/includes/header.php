<?php
$currentAdminPage = isset($currentAdminPage) ? $currentAdminPage : '';
$resellerBalanceForNav = function_exists('getResellerPlatformBalance') ? getResellerPlatformBalance() : null;
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-inner">
        <div class="brand">Reseller Admin</div>
        <div class="admin-sidebar-balance">
            <span class="admin-sidebar-balance-label">Reseller balance</span>
            <span class="admin-sidebar-balance-value"><?php echo $resellerBalanceForNav !== null ? '₦' . number_format($resellerBalanceForNav, 2) : '—'; ?></span>
        </div>
        <a href="/admin/" class="<?php echo $currentAdminPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="/admin/settings" class="<?php echo $currentAdminPage === 'settings' ? 'active' : ''; ?>">Site &amp; SprintPay</a>
        <a href="/admin/users" class="<?php echo $currentAdminPage === 'users' ? 'active' : ''; ?>">Customers &amp; wallets</a>
        <a href="/admin/funding" class="<?php echo $currentAdminPage === 'funding' ? 'active' : ''; ?>">Funding</a>
        <a href="/admin/orders" class="<?php echo $currentAdminPage === 'orders' ? 'active' : ''; ?>">Orders</a>
        <a href="/admin/reported_orders" class="<?php echo $currentAdminPage === 'reported' ? 'active' : ''; ?>">Reported orders</a>
        <a href="/catalog" target="_blank">View store</a>
        <a href="/admin/logout">Logout</a>
    </div>
</aside>
<div class="admin-main">
    <header class="admin-header">
        <span class="admin-page-title"><?php echo htmlspecialchars($adminPageTitle ?? 'Admin'); ?></span>
        <button type="button" class="theme-toggle admin-theme-toggle js-theme-toggle" aria-label="Switch color theme" aria-pressed="false" title="Theme">
            <svg class="theme-toggle__sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <svg class="theme-toggle__moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </header>
    <main class="admin-content">
