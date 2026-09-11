<?php
function admin_header(string $title, array $user, string $active = ''): void {
    // Sert à afficher "Mon profil"/"Mes activités" pour QUICONQUE est lié à un profil
    // intervenant — pas seulement le rôle "benevole" : super_admin/mavka_admin peuvent
    // l'être aussi (ex. Larysa elle-même est aussi intervenante).
    $stmt = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
    $stmt->execute([$user['id']]);
    $intervenant_id = $stmt->fetchColumn();
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?> — MAVKA</title>
  <link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/admin.css') ?: time() ?>">
</head>
<body>
<div class="mavka-admin-shell">
  <div class="mavka-admin-sidebar">
    <div class="brand">MAVKA</div>
    <a href="/admin/dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
    <?php if ($intervenant_id): ?>
    <a href="/admin/mes-activites.php" class="<?= $active === 'mes-activites' ? 'active' : '' ?>">Mes activités</a>
    <a href="/admin/mon-profil.php" class="<?= $active === 'mon-profil' ? 'active' : '' ?>">Mon profil</a>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['super_admin', 'mavka_admin', 'partenaire'], true)): ?>
    <a href="/admin/activites.php" class="<?= $active === 'activites' ? 'active' : '' ?>">Activités</a>
    <a href="/admin/intervenants.php" class="<?= $active === 'intervenants' ? 'active' : '' ?>">Intervenants</a>
    <?php endif; ?>
    <?php if (in_array($user['role'], ['super_admin', 'mavka_admin'], true)): ?>
    <a href="/admin/messages.php" class="<?= $active === 'messages' ? 'active' : '' ?>">Messages</a>
    <?php endif; ?>
    <?php if ($user['role'] === 'super_admin'): ?>
    <a href="/admin/acces.php" class="<?= $active === 'acces' ? 'active' : '' ?>">Accès</a>
    <?php endif; ?>
    <div style="flex-grow:1;"></div>
    <div style="font-size:12.5px; color:var(--mavka-color-text-muted); padding:10px 12px;"><?= htmlspecialchars($user['email']) ?></div>
    <a href="/admin/logout.php">Déconnexion</a>
  </div>
  <div class="mavka-admin-main">
    <?php
}

function admin_footer(): void {
    ?>
  </div>
</div>
</body>
</html>
    <?php
}

function flash(string $type, string $message): void {
    $class = $type === 'ok' ? 'mavka-flash--ok' : 'mavka-flash--err';
    echo '<div class="mavka-flash ' . $class . '">' . htmlspecialchars($message) . '</div>';
}
