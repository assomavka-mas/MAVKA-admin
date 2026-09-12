<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/site_functions.php';

// Section "Actívité" du volontaire : sa propre vue d'admin/activites.php, mais sans les
// réglages (pas de champs modifiables, pas de statut/catégorie/etc.) — juste la carte
// publique de chaque activité (identique à ce que verront les visiteurs) et, s'il en manque,
// le seul geste qu'il/elle peut faire ici : l'accepter.
$user = auth_require();

$stmt = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$stmt->execute([$user['id']]);
$intervenant_id = $stmt->fetchColumn();

if (!$intervenant_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('
    SELECT a.*, ai.accepte AS mon_acceptation,
        (SELECT COUNT(*) FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id) AS nb_intervenants,
        (SELECT COUNT(*) FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 1) AS nb_acceptes,
        (SELECT GROUP_CONCAT(iv.nom SEPARATOR ", ") FROM activite_intervenant ai2 JOIN intervenants iv ON iv.id = ai2.intervenant_id WHERE ai2.activite_id = a.id AND ai2.accepte = 0 AND ai2.intervenant_id != ?) AS autres_en_attente
    FROM activites a
    JOIN activite_intervenant ai ON ai.activite_id = a.id
    WHERE ai.intervenant_id = ?
    ORDER BY a.date_debut ASC
');
$stmt->execute([$intervenant_id, $intervenant_id]);
$mes_activites = $stmt->fetchAll();

admin_header('Mes activités', $user, 'mes-activites');
?>
<h1>Mes activités</h1>
<?php if (isset($_GET['accepte'])): ?>
  <?php flash('ok', 'Activité acceptée — merci !'); ?>
<?php endif; ?>
<p style="color:var(--mavka-color-text-muted); font-size:13.5px;">Les activités où tu es intervenant·e, telles qu'elles apparaîtront sur le site (photo, description, lien d'inscription compris). Une activité encadrée en rouge n'est pas encore visible sur le site public : il lui manque le lien d'inscription, ou ton accord sur les conditions et la description.</p>
<div class="mavka-mes-activites-grid">
  <?php foreach ($mes_activites as $a): ?>
  <?php $incomplet = activite_incomplete($a); ?>
  <div class="mavka-mes-activite-item<?= $incomplet ? ' mavka-mes-activite-item--incomplet' : '' ?>">
    <?= render_event_card($a) ?>
    <div class="mavka-mes-activite-validation">
      <span class="mavka-badge mavka-badge--<?= $a['statut'] ?>"><?= $a['statut'] ?></span>
      <?php if (empty($a['lien_inscription'])): ?>
        <span class="mavka-tag-alerte">⚠ En attente du lien d'inscription</span>
      <?php elseif (empty($a['mon_acceptation'])): ?>
        <form method="post" action="/admin/activite-accepter.php" onsubmit="return confirm('Confirmez-vous les conditions et la description de « <?= htmlspecialchars(addslashes($a['titre'])) ?> » ?');">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <button type="submit" class="mavka-btn mavka-btn--sm mavka-btn--primary">Accepter cette activité</button>
        </form>
      <?php elseif (!empty($a['autres_en_attente'])): ?>
        <span class="mavka-fill-yes">✓ Tu as accepté</span>
        <span class="mavka-tag-alerte">— en attente de <?= htmlspecialchars($a['autres_en_attente']) ?></span>
      <?php else: ?>
        <span class="mavka-fill-yes">✓ Acceptée par tou·te·s</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$mes_activites): ?>
  <p style="color:var(--mavka-color-text-muted);">Aucune activité pour l'instant.</p>
  <?php endif; ?>
</div>
<style>
  @import url('https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap');
  @import url('/assets/event-card.css?v=<?= @filemtime(__DIR__ . '/../assets/event-card.css') ?: time() ?>');
  .mavka-mes-activites-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 18px; }
  .mavka-mes-activite-item { border-radius: 26px; padding: 4px; }
  .mavka-mes-activite-item--incomplet { background: var(--mavka-color-danger-bg); border: 2px solid var(--mavka-color-danger-text); }
  .mavka-mes-activite-validation { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 14px 10px 8px; }
</style>
<?php admin_footer(); ?>
