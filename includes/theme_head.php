<?php
/** Inline in <head> before CSS so first paint uses correct theme. */
?>
<script>
(function () {
    try {
        var s = localStorage.getItem('reseller-theme');
        var t = (s === 'light' || s === 'dark') ? s : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', t);
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();
</script>
