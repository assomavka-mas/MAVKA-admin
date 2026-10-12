<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Vue transversale de tous les projets/accords, tous partenaires confondus, en tableau kanban
// par statut — pour voir d'un coup d'œil où en est chaque projet sans ouvrir chaque fiche.
$user = auth_require(['super_admin', 'mavka_admin']);

$statuts_projet_labels = ['en_cours' => 'En cours', 'termine' => 'Terminé', 'abandonne' => 'Abandonné'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'changer_statut') {
    $statut = $_POST['statut'] ?? '';
    if (isset($statuts_projet_labels[$statut])) {
        db()->prepare('UPDATE partenaires_projets SET statut = ? WHERE id = ?')->execute([$statut, (int)($_POST['projet_id'] ?? 0)]);
    }
    header('Location: /admin/projets.php');
    exit;
}

$projets = db()->query('
    SELECT p.*, o.nom AS organisation_nom,
        GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ", ") AS participants_noms
    FROM partenaires_projets p
    JOIN partenaires_organisations o ON o.id = p.organisation_id
    LEFT JOIN partenaires_projets_participants pp ON pp.projet_id = p.id
    LEFT JOIN partenaires_contacts c ON c.id = pp.contact_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
')->fetchAll();

$colonnes = array_fill_keys(array_keys($statuts_projet_labels), []);
foreach ($projets as $p) {
    $colonnes[$p['statut']][] = $p;
}

admin_header('Projets & accords', $user, 'projets');
?>
<h1>Projets &amp; accords</h1>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 16px;">Tous les projets/accords, tous partenaires confondus. Change le statut directement depuis une carte, ou ouvre la fiche du partenaire pour le détail.</p>

<div class="mavka-kanban">
  <?php foreach ($statuts_projet_labels as $val => $label): ?>
  <div class="mavka-kanban-col mavka-kanban-col--<?= $val ?>">
    <p class="mavka-kanban-col__title"><?= htmlspecialchars($label) ?> <span class="mavka-kanban-count"><?= count($colonnes[$val]) ?></span></p>
    <?php foreach ($colonnes[$val] as $p): ?>
    <div class="mavka-kanban-card">
      <p class="mavka-kanban-card__title"><?= htmlspecialchars($p['nom']) ?></p>
      <p class="mavka-kanban-card__meta"><a href="/admin/partenaire-form.php?id=<?= $p['organisation_id'] ?>#projets"><?= htmlspecialchars($p['organisation_nom']) ?></a></p>
      <?php if ($p['date_debut'] || $p['date_fin']): ?>
      <p class="mavka-kanban-card__meta">
        <?= $p['date_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_debut']))) : '?' ?>
        —
        <?= $p['date_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_fin']))) : 'en cours' ?>
      </p>
      <?php endif; ?>
      <?php if ($p['description']): ?>
      <p class="mavka-kanban-card__desc"><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 140, '…')) ?></p>
      <?php endif; ?>
      <?php if ($p['participants_noms']): ?>
      <p class="mavka-kanban-card__meta">👥 <?= htmlspecialchars($p['participants_noms']) ?></p>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="changer_statut">
        <input type="hidden" name="projet_id" value="<?= $p['id'] ?>">
        <select name="statut" onchange="this.form.submit()">
          <?php foreach ($statuts_projet_labels as $sval => $slabel): ?>
          <option value="<?= $sval ?>" <?= $sval === $val ? 'selected' : '' ?>><?= htmlspecialchars($slabel) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (!$colonnes[$val]): ?>
    <p style="font-size:12.5px; color:var(--mavka-color-text-muted);">Aucun projet.</p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
