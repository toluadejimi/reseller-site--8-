<?php /** Before </body>. */ ?>
<script>
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('reduce-motion');
    }
})();
(function () {
    var minMs = document.documentElement.classList.contains('reduce-motion') ? 0 : 480;
    var t0 = Date.now();
    function hideLoader() {
        var wait = Math.max(0, minMs - (Date.now() - t0));
        setTimeout(function () {
            document.documentElement.classList.remove('is-loading');
            var el = document.getElementById('page-loader');
            if (el) el.setAttribute('aria-busy', 'false');
        }, wait);
    }
    if (document.readyState === 'complete') hideLoader();
    else window.addEventListener('load', hideLoader);
})();
(function () {
    function syncToggleUi(t) {
        document.querySelectorAll('.js-theme-toggle').forEach(function (btn) {
            btn.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
            btn.setAttribute('title', t === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
        });
    }
    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        try { localStorage.setItem('reseller-theme', t); } catch (e) {}
        syncToggleUi(t);
    }
    syncToggleUi(document.documentElement.getAttribute('data-theme') || 'light');
    document.querySelectorAll('.js-theme-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cur = document.documentElement.getAttribute('data-theme') || 'light';
            apply(cur === 'dark' ? 'light' : 'dark');
        });
    });
})();
</script>
<?php
if (!defined('RESELLER_API_KEY') && is_file(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
if (defined('WIDGET_SCRIPT_URL') && defined('WIDGET_ORG_ID')) {
    $widgetSrc = trim((string) WIDGET_SCRIPT_URL);
    $widgetOrg = trim((string) WIDGET_ORG_ID);
    if ($widgetSrc !== '' && $widgetOrg !== '') {
        ?>
<script src="<?php echo htmlspecialchars($widgetSrc, ENT_QUOTES, 'UTF-8'); ?>" data-org="<?php echo htmlspecialchars($widgetOrg, ENT_QUOTES, 'UTF-8'); ?>" async></script>
        <?php
    }
}
