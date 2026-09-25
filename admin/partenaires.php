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

// Liste des villes pour le filtre — regroupées sans tenir compte de la casse ("Garat" et "GARAT"
// comptent comme la même ville), sinon une saisie moins uniforme ferait doublon dans le menu.
$villes_par_cle = [];
foreach ($organisations as $o) {
    $v = trim((string)($o['ville'] ?? ''));
    if ($v === '') continue;
    $cle = mb_strtolower($v);
    if (!isset($villes_par_cle[$cle])) $villes_par_cle[$cle] = $v;
}
$villes_disponibles = array_values($villes_par_cle);
sort($villes_disponibles, SORT_FLAG_CASE | SORT_STRING);

$filtre_type = $_GET['type'] ?? '';
$filtre_ville = $_GET['ville'] ?? '';
if ($filtre_type !== '' && isset($types_labels[$filtre_type])) {
    $organisations = array_values(array_filter($organisations, fn($o) => $o['type'] === $filtre_type));
}
if ($filtre_ville !== '') {
    $organisations = array_values(array_filter($organisations, fn($o) => mb_strtolower(trim((string)($o['ville'] ?? ''))) === mb_strtolower($filtre_ville)));
}

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

function part_tri_lien(string $col, string $libelle, string $triActuel, string $sensActuel, string $filtre_type, string $filtre_ville): string {
    $prochainSens = ($triActuel === $col && $sensActuel === 'asc') ? 'desc' : 'asc';
    $fleche = $triActuel === $col ? ($sensActuel === 'asc' ? ' ▲' : ' ▼') : '';
    $params = ['sort' => $col, 'dir' => $prochainSens];
    if ($filtre_type !== '') $params['type'] = $filtre_type;
    if ($filtre_ville !== '') $params['ville'] = $filtre_ville;
    return '<a href="?' . http_build_query($params) . '">' . htmlspecialchars($libelle) . $fleche . '</a>';
}

admin_header('Contacts', $user, 'partenaires');
?>
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
  <h1>Contacts<?php if ($filtre_type !== '' || $filtre_ville !== ''): ?><span style="font-weight:400; color:var(--mavka-color-text-muted); font-size:18px;"> — <?= count($organisations) ?></span><?php endif; ?></h1>
  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <form method="get" style="display:contents;">
      <?php if ($tri !== 'nom' || $sens !== 'asc'): ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($tri) ?>">
      <input type="hidden" name="dir" value="<?= htmlspecialchars($sens) ?>">
      <?php endif; ?>
      <select name="type" class="mavka-btn mavka-btn--sm" style="cursor:pointer;" onchange="this.form.submit();">
        <option value="">— Tous les types —</option>
        <?php foreach ($types_labels as $val => $label): ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= $filtre_type === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="ville" class="mavka-btn mavka-btn--sm" style="cursor:pointer;" onchange="this.form.submit();">
        <option value="">— Toutes les villes —</option>
        <?php foreach ($villes_disponibles as $v): ?>
        <option value="<?= htmlspecialchars($v) ?>" <?= mb_strtolower($filtre_ville) === mb_strtolower($v) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit" class="mavka-btn mavka-btn--sm">Filtrer</button></noscript>
    </form>
    <a href="/admin/partenaire-form.php" class="mavka-btn mavka-btn--primary">+ Nouveau contact</a>
  </div>
</div>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 0;">Mairies, centres sociaux, fondations, associations... Clique un titre de colonne pour trier (Nom, Type, Ville, Statut, Dernière rencontre), ou filtre par Type/Ville ci-dessus. Statut « Partenaire » = relation établie, à distinguer des contacts encore au stade « Potentiel ».</p>

<table class="mavka-table" style="margin-top:12px;">
  <tr>
    <th><?= part_tri_lien('nom', 'Nom', $tri, $sens, $filtre_type, $filtre_ville) ?></th>
    <th><?= part_tri_lien('type', 'Type', $tri, $sens, $filtre_type, $filtre_ville) ?></th>
    <th><?= part_tri_lien('ville', 'Ville', $tri, $sens, $filtre_type, $filtre_ville) ?></th>
    <th><?= part_tri_lien('statut', 'Statut', $tri, $sens, $filtre_type, $filtre_ville) ?></th>
    <th>Email</th>
    <th>Téléphone</th>
    <th><?= part_tri_lien('derniere_rencontre', 'Dernière rencontre', $tri, $sens, $filtre_type, $filtre_ville) ?></th>
    <th></th>
  </tr>
  <?php foreach ($organisations as $o): ?>
  <tr>
    <td><a href="/admin/partenaire-form.php?id=<?= $o['id'] ?>"><?= htmlspecialchars($o['nom']) ?></a></td>
    <td><?= htmlspecialchars($types_labels[$o['type']] ?? $o['type']) ?></td>
    <td><?= htmlspecialchars($o['ville'] ?? '') ?></td>
    <td><?= htmlspecialchars($statuts_labels[$o['statut']] ?? $o['statut']) ?></td>
    <td><?php if ($o['email_general']): ?><a href="mailto:<?= htmlspecialchars($o['email_general']) ?>"><?= htmlspecialchars($o['email_general']) ?></a><?php else: ?>—<?php endif; ?></td>
    <td><?= htmlspecialchars($o['telephone'] ?? '') ?: '—' ?></td>
    <td><?= $o['derniere_rencontre'] ? htmlspecialchars(date('d/m/Y', strtotime($o['derniere_rencontre']))) : '—' ?></td>
    <td style="white-space:nowrap;">
      <a href="/admin/partenaire-form.php?id=<?= $o['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/partenaires.php?delete=<?= $o['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer ce partenaire et tout son historique (contacts, rencontres, projets) ?');">Supprimer</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$organisations): ?>
  <tr><td colspan="8" style="color:var(--mavka-color-text-muted);">Aucun contact pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
