<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require();

$voit_stats = in_array($user['role'], ['super_admin', 'mavka_admin', 'partenaire'], true);
$intervenant_id = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$intervenant_id->execute([$user['id']]);
$intervenant_id = $intervenant_id->fetchColumn();

if ($voit_stats) {
    $count = db()->query('SELECT COUNT(*) c FROM activites WHERE statut = "publie"')->fetch()['c'];
    $count_intervenants = db()->query('SELECT COUNT(*) c FROM intervenants WHERE actif = 1')->fetch()['c'];
    if (peut_editer($user)) {
        $messages_non_lus = db()->query('SELECT COUNT(*) c FROM messages_contact WHERE lu = 0')->fetch()['c'];
    }
}

// Affiché pour tout compte lié à un profil intervenant (super_admin, mavka_admin ou benevole
// qui est aussi bénévole elle-même) — pas seulement pour le rôle "benevole".
if ($intervenant_id) {
    $stmt = db()->prepare('
        SELECT a.* FROM activites a
        JOIN activite_intervenant ai ON ai.activite_id = a.id
        WHERE ai.intervenant_id = ?
        ORDER BY a.date_debut ASC
    ');
    $stmt->execute([$intervenant_id]);
    $mes_activites = $stmt->fetchAll();
}

admin_header('Tableau de bord', $user, 'dashboard');
?>
<h1>Bonjour !</h1>
<?php if ($voit_stats): ?>
  <div style="display:flex; gap:16px; margin-top:20px; flex-wrap:wrap;">
    <div class="mavka-card" style="flex:1; min-width:180px;">
      <div style="font-size:13px; color:var(--mavka-color-text-muted);">Activités publiées</div>
      <div style="font-size:32px; font-family:var(--mavka-font-display); color:var(--mavka-color-teal);"><?= (int)$count ?></div>
    </div>
    <div class="mavka-card" style="flex:1; min-width:180px;">
      <div style="font-size:13px; color:var(--mavka-color-text-muted);">Intervenants actifs</div>
      <div style="font-size:32px; font-family:var(--mavka-font-display); color:var(--mavka-color-teal);"><?= (int)$count_intervenants ?></div>
    </div>
    <?php if (peut_editer($user)): ?>
    <div class="mavka-card" style="flex:1; min-width:180px;">
      <div style="font-size:13px; color:var(--mavka-color-text-muted);">Messages non lus</div>
      <div style="font-size:32px; font-family:var(--mavka-font-display); color:var(--mavka-color-orange);"><?= (int)$messages_non_lus ?></div>
    </div>
    <?php endif; ?>
  </div>
  <?php if (peut_editer($user)): ?>
  <p style="margin-top:24px;"><a href="/admin/activites.php" class="mavka-btn mavka-btn--primary">Gérer les activités</a></p>
  <?php else: ?>
  <p style="margin-top:24px; color:var(--mavka-color-text-muted); font-size:13.5px;">Accès en lecture seule.</p>
  <?php endif; ?>
<?php endif; ?>

  <?php if (isset($_GET['accepte'])): ?>
    <?php flash('ok', 'Activité acceptée — merci !'); ?>
  <?php endif; ?>
<?php if ($intervenant_id): ?>
  <h2 style="margin-top:<?= $voit_stats ? '32px' : '20px' ?>;">Mes activités</h2>
  <p style="color:var(--mavka-color-text-muted); font-size:13.5px;">Les activités où vous êtes intervenant·e. Une activité en rouge n'est pas encore visible sur le site public : il lui manque le lien d'inscription, ou votre accord sur les conditions et la description.</p>
  <table class="mavka-table" style="margin-top:16px;">
    <tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Statut</th><th>Validation</th></tr>
    <?php foreach ($mes_activites as $a): ?>
    <?php $incomplet = activite_incomplete($a, true); ?>
    <tr<?= $incomplet ? ' class="mavka-row--incomplete"' : '' ?>>
      <td><?= htmlspecialchars($a['titre']) ?></td>
      <td><?= htmlspecialchars($a['date_debut'] ?: $a['recurrence']) ?></td>
      <td><?= htmlspecialchars($a['lieu']) ?></td>
      <td><span class="mavka-badge mavka-badge--<?= $a['statut'] ?>"><?= $a['statut'] ?></span></td>
      <td>
        <?php if (empty($a['lien_inscription'])): ?>
          <span class="mavka-tag-alerte">⚠ En attente du lien d'inscription</span>
        <?php elseif (empty($a['accepte_intervenant'])): ?>
          <form method="post" action="/admin/activite-accepter.php" style="display:inline;" onsubmit="return confirm('Confirmez-vous les conditions et la description de « <?= htmlspecialchars(addslashes($a['titre'])) ?> » ?');">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button type="submit" class="mavka-btn mavka-btn--sm mavka-btn--primary">Accepter cette activité</button>
          </form>
        <?php else: ?>
          <span class="mavka-fill-yes">✓ Acceptée</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$mes_activites): ?>
    <tr><td colspan="5" style="color:var(--mavka-color-text-muted);">Aucune activité pour l'instant.</td></tr>
    <?php endif; ?>
  </table>
<?php elseif (!$voit_stats): ?>
  <p style="color:var(--mavka-color-text-muted);">Aucun profil intervenant lié à ce compte pour l'instant.</p>
<?php endif; ?>
<?php admin_footer(); ?>
