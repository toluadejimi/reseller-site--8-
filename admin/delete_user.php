<?php
require_once __DIR__ . '/../admin_helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    header('Location: /admin/users');
    exit;
}
$userId = (int) $_POST['user_id'];
if ($userId < 1) {
    header('Location: /admin/users?error=invalid');
    exit;
}
adminDeleteUser($userId);
header('Location: /admin/users?deleted=1');
exit;
