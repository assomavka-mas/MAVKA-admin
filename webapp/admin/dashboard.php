<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require();

if ($user['role'] === 'admin') {
    $count = db()->query('SELECT COUNT(*) c FROM activites WHERE statut = "publie"')->fetch()['c'];
    $messages_non_lus = db()->query('SELECT COUNT(*) c FROM messages_contact WHERE lu = 0')->fetch()['c'];
} else {
    $stmt = db()->prepare('
        SELECT a.* FROM activites a
        JOIN activite_intervenant ai ON ai.activite_id = a.id
        JOIN admins ad ON ad.intervenant_id = ai.intervenant_id
        WHERE ad.id = ?
        ORDER BY a.date_debut ASC
    ');
    $stmt->execute([$user['id']]);
    $mes_activites = $stmt->fetchAll();
}

admin_header('Tableau de bord', $user, 'dashboard');
?>
<h1>Bonjour !</h1>
<?php if ($user['role'] === 'admin'): ?>
  <div style="display:flex; gap:16px; margin-top:20px;">
    <div class="mavka-card" style="flex:1;">
      <div style="font-size:13px; color:var(--mavka-color-text-muted);">Activités publiées</div>
      <div style="font-size:32px; font-family:var(--mavka-font-display); color:var(--mavka-color-teal);"><?= (int)$count ?></div>
    </div>
    <div class="mavka-card" style="flex:1;">
      <div style="font-size:13px; color:var(--mavka-color-text-muted);">Messages non lus</div>
      <div style="font-size:32px; font-family:var(--mavka-font-display); color:var(--mavka-color-orange);"><?= (int)$messages_non_lus ?></div>
    </div>
  </div>
  <p style="margin-top:24px;"><a href="/admin/activites.php" class="mavka-btn mavka-btn--primary">Gérer les activités</a></p>
<?php else: ?>
  <p style="color:var(--mavka-color-text-muted);">Voici les activités où vous êtes intervenant·e.</p>
  <table class="mavka-table" style="margin-top:16px;">
    <tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Statut</th></tr>
    <?php foreach ($mes_activites as $a): ?>
    <tr>
      <td><?= htmlspecialchars($a['titre']) ?></td>
      <td><?= htmlspecialchars($a['date_debut'] ?: $a['recurrence']) ?></td>
      <td><?= htmlspecialchars($a['lieu']) ?></td>
      <td><span class="mavka-badge mavka-badge--<?= $a['statut'] ?>"><?= $a['statut'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$mes_activites): ?>
    <tr><td colspan="4" style="color:var(--mavka-color-text-muted);">Aucune activité pour l'instant.</td></tr>
    <?php endif; ?>
  </table>
<?php endif; ?>
<?php admin_footer(); ?>
