<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

auth_start();
if (auth_user()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$denied = isset($_GET['denied']);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion — MAVKA</title>
  <link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/admin.css') ?: time() ?>">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
  <div class="mavka-login-wrap">
    <div class="mavka-card" style="text-align:center;">
      <h2>Espace MAVKA</h2>
      <p style="color:var(--mavka-color-text-muted); font-size:13.5px; margin-bottom:20px;">Connexion administrateur / bénévole</p>

      <?php if ($denied): ?>
        <div class="mavka-flash mavka-flash--err">Cette adresse n'a pas accès à l'espace MAVKA.</div>
      <?php endif; ?>

      <div id="g_id_onload"
           data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>"
           data-login_uri="<?= htmlspecialchars(SITE_URL) ?>/admin/google-callback.php"
           data-ux_mode="redirect">
      </div>
      <div class="g_id_signin" data-type="standard" data-theme="outline" data-size="large" data-locale="fr" style="display:flex; justify-content:center;"></div>
    </div>
  </div>
</body>
</html>
