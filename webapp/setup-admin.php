<?php
// ОДНОРАЗОВИЙ скрипт: додає перший обліковий запис адміністратора за Google-email.
// ПІСЛЯ використання ОБОВ'ЯЗКОВО видали цей файл із сервера — інакше будь-хто,
// хто знайде цю адресу, зможе додати собі адмін-доступ.

require_once __DIR__ . '/includes/db.php';

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $stmt = db()->prepare('INSERT INTO admins (email, password_hash, role) VALUES (?, NULL, "admin")
                                ON DUPLICATE KEY UPDATE role = "admin"');
        $stmt->execute([$email]);
        $done = true;
    }
}
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Setup admin</title></head>
<body style="font-family:sans-serif; max-width:420px; margin:60px auto;">
<h2>Ajouter le premier administrateur</h2>
<p>Indique l'adresse email de ton compte Google — c'est avec ce compte que tu te connecteras ensuite.</p>
<?php if ($done): ?>
  <p style="color:green;">Compte créé ! Connecte-toi avec Google sur <a href="/admin/login.php">/admin/login.php</a>.</p>
  <p style="color:red; font-weight:bold;">Supprime maintenant ce fichier (setup-admin.php) du serveur.</p>
<?php else: ?>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Email Google<br><input type="email" name="email" required style="width:100%; padding:8px;"></label><br><br>
    <button type="submit">Créer</button>
  </form>
<?php endif; ?>
</body>
</html>
