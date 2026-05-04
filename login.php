<?php
require_once __DIR__ . '/init_db.php';
require_once __DIR__ . '/auth_helpers.php';
if (getDb() === null) {
    header('Location: /');
    exit;
}
$businessName = (function_exists('getSetting') && getSetting('business_name')) ? getSetting('business_name') : (defined('BUSINESS_NAME') ? BUSINESS_NAME : (defined('SITE_TITLE') ? SITE_TITLE : 'Store'));
$logoUrl = (function_exists('getSetting') && getSetting('logo_url') !== null) ? trim((string)getSetting('logo_url')) : (defined('LOGO_URL') ? trim(LOGO_URL) : '');
$currentUser = getCurrentUser();
$dbPath = defined('DB_PATH') ? DB_PATH : '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($email === '' || $pass === '') {
        $error = 'Email and password required.';
    } elseif (loginUser($email, $pass)) {
        $redirect = safe_internal_redirect_path($_GET['redirect'] ?? null, '/catalog');
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
if ($currentUser) {
    header('Location: /catalog');
    exit;
}
$pageTitle = 'Login — ' . htmlspecialchars($businessName);
$authSplitPage = true;
$bodyClass = 'page-auth-split';
$layout = 'wide';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
$registerHref = '/register';
if (isset($_GET['redirect']) && is_string($_GET['redirect']) && $_GET['redirect'] !== '') {
    $registerHref .= '?redirect=' . rawurlencode($_GET['redirect']);
}
?>

<div class="auth-split">
    <aside class="auth-split__visual" aria-hidden="true">
        <div class="auth-split__visual-bg"></div>
        <div class="auth-split__visual-content">
            <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="" class="auth-split__brand-logo" width="120" height="120" decoding="async">
            <?php endif; ?>
            <p class="auth-split__eyebrow">Welcome back</p>
            <h2 class="auth-split__brand-title"><?php echo htmlspecialchars($businessName); ?></h2>
            <p class="auth-split__brand-text">Sign in to browse the catalog, manage your wallet, and track every order in one place.</p>
            <ul class="auth-split__bullets">
                <li>Secure wallet-backed checkout</li>
                <li>Live inventory and clear pricing</li>
                <li>Order history when you need it</li>
            </ul>
        </div>
    </aside>
    <div class="auth-split__main">
        <div class="auth-split__topbar">
            <a href="/" class="auth-split__back">← Store home</a>
            <?php require __DIR__ . '/includes/auth_theme_toggle.php'; ?>
        </div>
        <div class="auth-split__main-inner">
            <div class="auth-split__card">
                <h1 class="auth-split__title">Sign in</h1>
                <p class="auth-split__lead">Enter your email and password to continue.</p>
                <?php if ($error): ?>
                    <div class="alert alert-error auth-split__alert"><p><?php echo htmlspecialchars($error); ?></p></div>
                <?php endif; ?>
                <form method="post" class="auth-split__form" action="/login<?php echo isset($_GET['redirect']) ? '?redirect=' . rawurlencode((string)$_GET['redirect']) : ''; ?>">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="auth-split__input" placeholder="you@example.com" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="auth-split__input" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block auth-split__submit">Continue</button>
                </form>
                <p class="auth-split__switch">New here? <a href="<?php echo htmlspecialchars($registerHref); ?>">Create an account</a></p>
            </div>
        </div>
        <p class="auth-split__fineprint">&copy; <?php echo (int) date('Y'); ?> <?php echo htmlspecialchars($businessName); ?></p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
