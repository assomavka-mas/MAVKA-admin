<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require();

$voit_stats = in_array($user['role'], ['super_admin', 'mavka_admin', 'partenaire'], true);
$intervenant_id = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$intervenant_id->execute([$user['id']]);
$intervenant_id = $intervenant_id->fetchColumn();

// Alerte "Dossier Prestataire" (voir admin/intervenant-form.php) : RC Pro expirée ou qui expire
// dans les 30 jours — visible uniquement à super_admin/mavka_admin, jamais aux bénévoles.
$documents_a_renouveler = [];
if (in_array($user['role'], ['super_admin', 'mavka_admin'], true)) {
    $stmt = db()->query("SELECT id, nom, assurance_date FROM intervenants
        WHERE actif = 1 AND assurance_date IS NOT NULL AND assurance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY assurance_date ASC");
    $documents_a_renouveler = $stmt->fetchAll();
}

if ($voit_stats) {
    // Compte celles VRAIMENT visibles sur le site (vue activites_publiques : lien + acceptation
    // de chacun·e comprise), pas juste celles marquées statut="publie" en base — sinon le
    // chiffre ne reflète pas ce qu'un visiteur voit réellement.
    $count = db()->query('SELECT COUNT(*) c FROM activites_publiques')->fetch()['c'];
    $count_intervenants = db()->query('SELECT COUNT(*) c FROM intervenants WHERE actif = 1')->fetch()['c'];
    if (peut_editer($user)) {
        $messages_non_lus = db()->query('SELECT COUNT(*) c FROM messages_contact WHERE lu = 0')->fetch()['c'];
    }
}

admin_header('Tableau de bord', $user, 'dashboard');
?>
<h1>Bonjour !</h1>
<?php if ($documents_a_renouveler): ?>
  <div class="mavka-card" style="margin-top:20px; border-color:var(--mavka-color-orange); background:#FFF6E9;">
    <div style="font-weight:700; color:var(--mavka-color-orange);">⚠️ RC Professionnelle à renouveler (<?= count($documents_a_renouveler) ?>)</div>
    <ul style="margin:10px 0 0; padding-left:20px; font-size:14px;">
      <?php foreach ($documents_a_renouveler as $d): ?>
      <li>
        <a href="/admin/intervenant-form.php?id=<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></a>
        — <?= strtotime($d['assurance_date']) < strtotime('today') ? 'expirée le' : 'expire le' ?> <?= htmlspecialchars(date('d/m/Y', strtotime($d['assurance_date']))) ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
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

<?php if ($intervenant_id): ?>
  <div style="display:flex; gap:16px; margin-top:<?= $voit_stats ? '32px' : '20px' ?>; flex-wrap:wrap;">
    <a href="/admin/mes-activites.php" class="mavka-card" style="flex:1; min-width:220px; text-decoration:none;">
      <div style="font-family:var(--mavka-font-display); font-size:20px; color:var(--mavka-color-ink);">Mes activités →</div>
      <div style="font-size:13.5px; color:var(--mavka-color-text-muted); margin-top:6px;">Voir tes activités et accepter celles qui l'attendent.</div>
    </a>
    <a href="/admin/mes-rapports.php" class="mavka-card" style="flex:1; min-width:220px; text-decoration:none;">
      <div style="font-family:var(--mavka-font-display); font-size:20px; color:var(--mavka-color-ink);">Mes rapports →</div>
      <div style="font-size:13.5px; color:var(--mavka-color-text-muted); margin-top:6px;">Comptes rendus, frais et bilans.</div>
    </a>
    <a href="/admin/mon-profil.php" class="mavka-card" style="flex:1; min-width:220px; text-decoration:none;">
      <div style="font-family:var(--mavka-font-display); font-size:20px; color:var(--mavka-color-ink);">Mon profil →</div>
      <div style="font-size:13.5px; color:var(--mavka-color-text-muted); margin-top:6px;">Tes informations, ta page publique, tes documents.</div>
    </a>
  </div>
<?php elseif (!$voit_stats): ?>
  <p style="color:var(--mavka-color-text-muted);">Aucun profil intervenant lié à ce compte pour l'instant.</p>
<?php endif; ?>
<?php admin_footer(); ?>
