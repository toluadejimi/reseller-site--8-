<?php
require_once __DIR__ . '/../admin_helpers.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/');
    exit;
}

$dbPath = defined('DB_PATH') ? DB_PATH : '';
if ($dbPath === '' || !function_exists('getDb') || getDb() === null) {
    $noDb = true;
} else {
    $noDb = false;
}

$error = '';
$setup = !$noDb && !isAdminSetup();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$noDb) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($setup) {
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            setSetting('admin_password_hash', $hash);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 'admin';
            header('Location: /admin/');
            exit;
        }
    } else {
        if (adminLogin($password)) {
            header('Location: /admin/');
            exit;
        }
        $error = 'Invalid password.';
    }
}

$adminPageTitle = $setup ? 'Set admin password' : 'Admin login';
?>
<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/../includes/theme_head.php'; ?>
    <title><?php echo $setup ? 'Setup' : 'Login'; ?> – Reseller Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<?php require __DIR__ . '/../includes/theme_body_open.php'; ?>
<div class="site-wrap narrow" style="margin-top: 60px;">
    <div style="text-align: right; margin-bottom: 10px;">
        <button type="button" class="theme-toggle js-theme-toggle" aria-label="Switch color theme" aria-pressed="false" title="Theme">
            <svg class="theme-toggle__sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <svg class="theme-toggle__moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
    </div>
    <div class="auth-card">
        <h1 class="page-title"><?php echo $noDb ? 'Admin' : ($setup ? 'Set admin password' : 'Admin login'); ?></h1>
        <?php if ($noDb): ?>
            <div class="alert alert-error"><p>Database not configured. Set <strong>DB_PATH</strong> in config.php to use the admin panel.</p></div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><p><?php echo htmlspecialchars($error); ?></p></div>
        <?php endif; ?>
        <?php if (!$noDb): ?>
        <form method="post" class="admin-form">
            <div class="form-group">
                <label for="password"><?php echo $setup ? 'Choose a password (min 8 characters)' : 'Password'; ?></label>
                <input type="password" id="password" name="password" required minlength="<?php echo $setup ? '8' : '1'; ?>">
            </div>
            <?php if ($setup): ?>
            <div class="form-group">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><?php echo $setup ? 'Create &amp; log in' : 'Log in'; ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/theme_footer.php'; ?>
</body>
</html>
