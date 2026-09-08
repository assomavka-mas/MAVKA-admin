<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require('admin');

if (isset($_GET['lu'])) {
    db()->prepare('UPDATE messages_contact SET lu = 1 WHERE id = ?')->execute([(int)$_GET['lu']]);
    header('Location: /admin/messages.php');
    exit;
}
if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM messages_contact WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/messages.php');
    exit;
}

$messages = db()->query('SELECT * FROM messages_contact ORDER BY created_at DESC')->fetchAll();

admin_header('Messages', $user, 'messages');
?>
<h1>Messages du formulaire de contact</h1>

<?php foreach ($messages as $m): ?>
<div class="mavka-card" style="margin-bottom:14px; <?= $m['lu'] ? 'opacity:.65;' : '' ?>">
  <div style="display:flex; justify-content:space-between;">
    <div>
      <strong><?= htmlspecialchars($m['nom']) ?></strong>
      &lt;<a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a>&gt;
      <?php if ($m['sujet']): ?> — <?= htmlspecialchars($m['sujet']) ?><?php endif; ?>
    </div>
    <div style="font-size:12.5px; color:var(--mavka-color-text-muted);"><?= htmlspecialchars($m['created_at']) ?></div>
  </div>
  <p style="white-space:pre-wrap; margin-top:10px;"><?= htmlspecialchars($m['message']) ?></p>
  <div style="display:flex; gap:8px;">
    <?php if (!$m['lu']): ?>
    <a href="/admin/messages.php?lu=<?= $m['id'] ?>" class="mavka-btn mavka-btn--sm">Marquer comme lu</a>
    <?php endif; ?>
    <a href="/admin/messages.php?delete=<?= $m['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
       onclick="return confirm('Supprimer ce message ?');">Supprimer</a>
  </div>
</div>
<?php endforeach; ?>
<?php if (!$messages): ?>
<p style="color:var(--mavka-color-text-muted);">Aucun message pour l'instant.</p>
<?php endif; ?>
<?php admin_footer(); ?>
