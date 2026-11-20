<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require('super_admin');

if (isset($_GET['delete'])) {
    $id_a_supprimer = (int)$_GET['delete'];
    if ($id_a_supprimer !== (int)$user['id']) { // on ne peut pas se supprimer soi-même
        db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id_a_supprimer]);
    }
    header('Location: /admin/acces.php');
    exit;
}

$comptes = db()->query('
    SELECT a.*, iv.nom AS intervenant_nom
    FROM admins a
    LEFT JOIN intervenants iv ON iv.id = a.intervenant_id
    ORDER BY FIELD(a.role, "super_admin", "mavka_admin", "partenaire", "benevole"), a.email
')->fetchAll();

$labels_role = [
    'super_admin' => 'Super admin',
    'mavka_admin' => 'Mavka-admin',
    'benevole' => 'Bénévole / Intervenant',
    'partenaire' => 'Partenaire (lecture seule)',
];

admin_header('Accès', $user, 'acces');
?>
<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>Gérer les accès</h1>
  <a href="/admin/acces-form.php" class="mavka-btn mavka-btn--primary">+ Nouvel accès</a>
</div>
<p style="font-size:13.5px; color:var(--mavka-color-text-muted); max-width:600px;">Ici tu crées les accès Mavka-admin (président·e, trésorier·ère, secrétaire) et Partenaire (lecture seule). Les accès Bénévole se créent depuis la fiche de chaque intervenant·e.</p>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>

<table class="mavka-table" style="margin-top:16px;">
  <tr><th>Nom</th><th>Email</th><th>Rôle</th><th></th></tr>
  <?php foreach ($comptes as $c): ?>
  <tr>
    <td><?= htmlspecialchars($c['nom'] ?: $c['intervenant_nom'] ?: '—') ?></td>
    <td><?= htmlspecialchars($c['email']) ?></td>
    <td><span class="mavka-badge mavka-badge--success"><?= htmlspecialchars($labels_role[$c['role']] ?? $c['role']) ?></span></td>
    <td style="white-space:nowrap;">
      <?php if ($c['role'] !== 'benevole'): ?>
      <a href="/admin/acces-form.php?id=<?= $c['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <?php endif; ?>
      <?php if ((int)$c['id'] !== (int)$user['id']): ?>
      <a href="/admin/acces.php?delete=<?= $c['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Révoquer cet accès ?');">Révoquer</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php admin_footer(); ?>
