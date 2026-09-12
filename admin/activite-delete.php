<?php
require_once __DIR__ . '/../includes/auth.php';

$user = auth_require(['super_admin', 'mavka_admin']);
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM activites WHERE id = ?')->execute([$id]);
}
header('Location: /admin/activites.php');
exit;
