<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require('admin');
$activites = db()->query('SELECT * FROM activites ORDER BY ordre ASC, date_debut ASC')->fetchAll();

admin_header('Activités', $user, 'activites');
?>
<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>Activités</h1>
  <a href="/admin/activite-form.php" class="mavka-btn mavka-btn--primary">+ Nouvelle activité</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <?php flash('ok', 'Enregistré avec succès.'); ?>
<?php endif; ?>

<table class="mavka-table" style="margin-top:20px;">
  <tr>
    <th>Titre</th><th>Catégorie</th><th>Date</th><th>Lieu</th><th>Places</th><th>Statut</th><th></th>
  </tr>
  <?php foreach ($activites as $a): ?>
  <tr>
    <td><?= htmlspecialchars($a['titre']) ?></td>
    <td><?= htmlspecialchars($a['categorie']) ?></td>
    <td><?= htmlspecialchars($a['date_debut'] ? date('d/m/Y', strtotime($a['date_debut'])) : ($a['recurrence'] ?: '—')) ?></td>
    <td><?= htmlspecialchars($a['lieu']) ?></td>
    <td><?= $a['nombre_places'] !== null ? (int)$a['nombre_places'] : '—' ?></td>
    <td>
      <span class="mavka-badge mavka-badge--<?= $a['statut'] ?>"><?= $a['statut'] ?></span>
      <span class="mavka-badge mavka-badge--brouillon"><?= $a['statut_activite'] ?></span>
    </td>
    <td style="white-space:nowrap;">
      <a href="/admin/activite-form.php?id=<?= $a['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/activite-delete.php?id=<?= $a['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer cette activité ?');">Supprimer</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$activites): ?>
  <tr><td colspan="7" style="color:var(--mavka-color-text-muted);">Aucune activité pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
