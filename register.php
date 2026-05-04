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
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    if ($email === '' || $password === '' || $name === '') {
        $error = 'All fields required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $err = registerUser($email, $password, $name);
        if ($err === null) {
            $success = true;
            if (loginUser($email, $password)) {
                header('Location: /catalog');
                exit;
            }
        } else {
            $error = $err;
        }
    }
}
if ($currentUser) {
    header('Location: /catalog');
    exit;
}
$pageTitle = 'Register — ' . htmlspecialchars($businessName);
$authSplitPage = true;
$bodyClass = 'page-auth-split';
$layout = 'wide';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
$loginHref = '/login';
if (isset($_GET['redirect']) && is_string($_GET['redirect']) && $_GET['redirect'] !== '') {
    $loginHref .= '?redirect=' . rawurlencode($_GET['redirect']);
}
?>

<div class="auth-split">
    <aside class="auth-split__visual" aria-hidden="true">
        <div class="auth-split__visual-bg auth-split__visual-bg--register"></div>
        <div class="auth-split__visual-content">
            <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="" class="auth-split__brand-logo" width="120" height="120" decoding="async">
            <?php endif; ?>
            <p class="auth-split__eyebrow">Join the store</p>
            <h2 class="auth-split__brand-title"><?php echo htmlspecialchars($businessName); ?></h2>
            <p class="auth-split__brand-text">Create a free account to unlock the catalog, fund your wallet, and keep every purchase organized.</p>
            <ul class="auth-split__bullets">
                <li>One wallet for repeat orders</li>
                <li>Profile and order history</li>
                <li>Built for resellers and teams</li>
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
                <h1 class="auth-split__title">Create account</h1>
                <p class="auth-split__lead">A few details and you are ready to browse.</p>
                <?php if ($error): ?>
                    <div class="alert alert-error auth-split__alert"><p><?php echo htmlspecialchars($error); ?></p></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="alert alert-success auth-split__alert">Account created. Redirecting…</p>
                <?php else: ?>
                <form method="post" class="auth-split__form" action="/register<?php echo isset($_GET['redirect']) ? '?redirect=' . rawurlencode((string)$_GET['redirect']) : ''; ?>">
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" class="auth-split__input" placeholder="Your name" required autocomplete="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="auth-split__input" placeholder="you@example.com" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="auth-split__input" placeholder="At least 6 characters" required minlength="6" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block auth-split__submit">Create account</button>
                </form>
                <?php endif; ?>
                <p class="auth-split__switch">Already have an account? <a href="<?php echo htmlspecialchars($loginHref); ?>">Sign in</a></p>
            </div>
        </div>
        <p class="auth-split__fineprint">&copy; <?php echo (int) date('Y'); ?> <?php echo htmlspecialchars($businessName); ?></p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
