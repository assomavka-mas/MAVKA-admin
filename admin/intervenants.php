<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require('admin');

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM intervenants WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/intervenants.php');
    exit;
}

$intervenants = db()->query('SELECT * FROM intervenants ORDER BY nom')->fetchAll();

admin_header('Intervenants', $user, 'intervenants');
?>
<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>Intervenants / bénévoles</h1>
  <a href="/admin/intervenant-form.php" class="mavka-btn mavka-btn--primary">+ Nouvel intervenant</a>
</div>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>

<table class="mavka-table" style="margin-top:16px;">
  <tr><th></th><th>Nom</th><th>Rôle</th><th>Statut</th><th>Spécialité</th><th>Email</th><th></th></tr>
  <?php foreach ($intervenants as $iv): ?>
  <tr>
    <td>
      <?php if ($iv['photo'] && $iv['dossier']): ?>
      <img src="/assets/uploads/intervenants/<?= htmlspecialchars($iv['dossier']) ?>/<?= htmlspecialchars($iv['photo']) ?>" alt="" style="width:36px; height:36px; border-radius:50%; object-fit:cover; display:block;">
      <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($iv['nom']) ?></td>
    <td><?= htmlspecialchars($iv['role_titre']) ?></td>
    <td><?= implode(' + ', intervenant_statuts($iv)) ?></td>
    <td><?= htmlspecialchars($iv['specialite']) ?></td>
    <td><?= htmlspecialchars($iv['email']) ?></td>
    <td style="white-space:nowrap;">
      <a href="/admin/intervenant-form.php?id=<?= $iv['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/intervenants.php?delete=<?= $iv['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer ?');">Supprimer</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$intervenants): ?>
  <tr><td colspan="7" style="color:var(--mavka-color-text-muted);">Aucun intervenant pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
