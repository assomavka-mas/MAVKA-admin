<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// Alerte email "RC Pro à renouveler" (voir aussi le badge sur le tableau de bord). Deux façons
// de lancer ce script :
// 1) Un vrai cron sur l'hébergement, une fois par jour, via l'URL avec le jeton secret
//    (CRON_ALERTES_TOKEN dans config.php) — tourne seul, pas besoin d'être connecté.
// 2) Un clic manuel dans l'admin (super_admin/mavka_admin) pour tester ou relancer à la main.
$via_cron = defined('CRON_ALERTES_TOKEN') && CRON_ALERTES_TOKEN !== '' && hash_equals(CRON_ALERTES_TOKEN, (string)($_GET['cle'] ?? ''));

$user = null;
if (!$via_cron) {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/layout.php';
    $user = auth_require(['super_admin', 'mavka_admin']);
}

// Ne renvoie pas le même rappel tous les jours : une fois envoyée pour un document donné,
// l'alerte se tait 7 jours (assurance_alerte_envoyee_le) avant de revenir si rien n'a changé.
// Se réinitialise automatiquement dès que la date d'échéance est mise à jour (intervenant-form.php).
function envoyer_alertes_rc_pro(): int {
    $stmt = db()->query("SELECT id, nom, assurance_date FROM intervenants
        WHERE actif = 1 AND assurance_date IS NOT NULL AND assurance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND (assurance_alerte_envoyee_le IS NULL OR assurance_alerte_envoyee_le <= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
        ORDER BY assurance_date ASC");
    $docs = $stmt->fetchAll();
    if (!$docs) {
        return 0;
    }

    $lignes = array_map(function ($d) {
        $mot = strtotime($d['assurance_date']) < strtotime('today') ? 'expirée le' : 'expire le';
        return '- ' . $d['nom'] . ' : RC Pro ' . $mot . ' ' . date('d/m/Y', strtotime($d['assurance_date']));
    }, $docs);
    $lien = defined('SITE_URL') ? SITE_URL . '/admin/dashboard.php' : '/admin/dashboard.php';
    $corps = "Documents à renouveler :\n\n" . implode("\n", $lignes) . "\n\nVoir le détail : $lien";

    $destinataires = db()->query("SELECT email FROM admins WHERE role IN ('super_admin','mavka_admin')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($destinataires as $email) {
        mavka_smtp_envoyer($email, 'MAVKA — RC Pro à renouveler (' . count($docs) . ')', $corps);
    }

    $maj = db()->prepare('UPDATE intervenants SET assurance_alerte_envoyee_le = CURDATE() WHERE id = ?');
    foreach ($docs as $d) {
        $maj->execute([$d['id']]);
    }
    return count($docs);
}

$resultat = null;
if ($via_cron || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultat = envoyer_alertes_rc_pro();
}

if ($via_cron) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $resultat . " alerte(s) envoyée(s).\n";
    exit;
}

admin_header('Alertes documents', $user, 'alertes-documents');
?>
<div class="mavka-form-section" style="padding:20px;">
  <h3>Alertes email — RC Pro à renouveler</h3>
  <p class="mavka-form-section__hint">Envoie un email à tous les super_admin/mavka_admin listant les RC Pro expirées ou qui expirent dans les 30 jours. Un même document ne relance pas plus d'une fois tous les 7 jours. Pour un envoi automatique quotidien, configure un cron sur ton hébergement qui appelle cette URL avec le jeton secret (voir config.php, CRON_ALERTES_TOKEN) — demande-moi si tu veux la marche à suivre.</p>

  <?php if ($resultat !== null): ?>
  <p style="color:var(--mavka-color-teal); font-weight:600;">✓ <?= $resultat ?> alerte(s) envoyée(s) (0 = rien à signaler, ou déjà envoyé il y a moins de 7 jours).</p>
  <?php endif; ?>

  <form method="post">
    <button type="submit" class="mavka-btn mavka-btn--primary">Envoyer maintenant</button>
  </form>
</div>
<?php admin_footer(); ?>
