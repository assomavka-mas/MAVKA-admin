<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Liste des organisations partenaires (mairies, centres sociaux, fondations...). Détail —
// contacts, rencontres, projets — dans partenaire-form.php. Réservé à super_admin/mavka_admin.
$user = auth_require(['super_admin', 'mavka_admin']);

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM partenaires_organisations WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/partenaires.php');
    exit;
}

$statuts_labels = [
    'potentiel' => 'Potentiel',
    'actif' => 'Actif',
    'partenaire' => 'Partenaire',
    'inactif' => 'Inactif',
    'en_pause' => 'En pause',
];
$types_labels = [
    'mairie' => 'Mairie',
    'centre_social' => 'Centre social',
    'fondation' => 'Fondation',
    'association' => 'Association',
    'entreprise' => 'Entreprise',
    'autre' => 'Autre',
];

$organisations = db()->query('
    SELECT o.*,
        (SELECT MAX(date_rencontre) FROM partenaires_rencontres r WHERE r.organisation_id = o.id) AS derniere_rencontre
    FROM partenaires_organisations o
')->fetchAll();

$tri = $_GET['sort'] ?? 'nom';
$sens = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$colonnes_triables = ['nom', 'type', 'ville', 'statut', 'derniere_rencontre'];
if (in_array($tri, $colonnes_triables, true)) {
    usort($organisations, function ($a, $b) use ($tri, $sens) {
        $va = $tri === 'derniere_rencontre' ? ($a[$tri] ?? '') : mb_strtolower((string)($a[$tri] ?? ''));
        $vb = $tri === 'derniere_rencontre' ? ($b[$tri] ?? '') : mb_strtolower((string)($b[$tri] ?? ''));
        $cmp = $va <=> $vb;
        return $sens === 'desc' ? -$cmp : $cmp;
    });
}

function part_tri_lien(string $col, string $libelle, string $triActuel, string $sensActuel): string {
    $prochainSens = ($triActuel === $col && $sensActuel === 'asc') ? 'desc' : 'asc';
    $fleche = $triActuel === $col ? ($sensActuel === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="?sort=' . urlencode($col) . '&dir=' . $prochainSens . '">' . htmlspecialchars($libelle) . $fleche . '</a>';
}

admin_header('Contacts', $user, 'partenaires');
?>
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
  <h1>Contacts</h1>
  <a href="/admin/partenaire-form.php" class="mavka-btn mavka-btn--primary">+ Nouveau contact</a>
</div>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 0;">Mairies, centres sociaux, fondations, associations... Clique un titre de colonne pour trier (Nom, Type, Ville, Statut, Dernière rencontre). Statut « Partenaire » = relation établie, à distinguer des contacts encore au stade « Potentiel ».</p>

<table class="mavka-table" style="margin-top:12px;">
  <tr>
    <th><?= part_tri_lien('nom', 'Nom', $tri, $sens) ?></th>
    <th><?= part_tri_lien('type', 'Type', $tri, $sens) ?></th>
    <th><?= part_tri_lien('ville', 'Ville', $tri, $sens) ?></th>
    <th><?= part_tri_lien('statut', 'Statut', $tri, $sens) ?></th>
    <th><?= part_tri_lien('derniere_rencontre', 'Dernière rencontre', $tri, $sens) ?></th>
    <th></th>
  </tr>
  <?php foreach ($organisations as $o): ?>
  <tr>
    <td><a href="/admin/partenaire-form.php?id=<?= $o['id'] ?>"><?= htmlspecialchars($o['nom']) ?></a></td>
    <td><?= htmlspecialchars($types_labels[$o['type']] ?? $o['type']) ?></td>
    <td><?= htmlspecialchars($o['ville'] ?? '') ?></td>
    <td><?= htmlspecialchars($statuts_labels[$o['statut']] ?? $o['statut']) ?></td>
    <td><?= $o['derniere_rencontre'] ? htmlspecialchars(date('d/m/Y', strtotime($o['derniere_rencontre']))) : '—' ?></td>
    <td style="white-space:nowrap;">
      <a href="/admin/partenaire-form.php?id=<?= $o['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/partenaires.php?delete=<?= $o['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer ce partenaire et tout son historique (contacts, rencontres, projets) ?');">Supprimer</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$organisations): ?>
  <tr><td colspan="6" style="color:var(--mavka-color-text-muted);">Aucun partenaire pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
