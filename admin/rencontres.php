<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Vue transversale de toutes les rencontres, tous partenaires confondus — le détail reste dans
// l'onglet Rencontres de partenaire-form.php, cette page sert à repérer d'un coup d'œil ce qui
// s'est passé récemment sans ouvrir chaque fiche une par une.
$user = auth_require(['super_admin', 'mavka_admin']);

$types_rencontre_labels = ['rencontre' => 'Rencontre', 'appel' => 'Appel', 'email' => 'Email', 'courrier' => 'Courrier'];

$rencontres = db()->query('
    SELECT r.*, o.nom AS organisation_nom,
        GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ", ") AS participants_noms
    FROM partenaires_rencontres r
    JOIN partenaires_organisations o ON o.id = r.organisation_id
    LEFT JOIN partenaires_rencontres_participants rp ON rp.rencontre_id = r.id
    LEFT JOIN partenaires_contacts c ON c.id = rp.contact_id
    GROUP BY r.id
    ORDER BY r.date_rencontre DESC, r.heure_rencontre DESC
')->fetchAll();

admin_header('Rencontres', $user, 'rencontres');
?>
<h1>Rencontres</h1>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 16px;">Toutes les rencontres, tous partenaires confondus, les plus récentes en premier. Clique une organisation pour ouvrir sa fiche complète.</p>

<table class="mavka-table">
  <tr><th>Organisation</th><th>Date</th><th>Type</th><th>Lieu</th><th>Sujet</th><th>Participants</th></tr>
  <?php foreach ($rencontres as $r): ?>
  <tr>
    <td><a href="/admin/partenaire-form.php?id=<?= $r['organisation_id'] ?>#rencontres"><?= htmlspecialchars($r['organisation_nom']) ?></a></td>
    <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rencontre']))) ?><?= $r['heure_rencontre'] ? ' à ' . htmlspecialchars(substr($r['heure_rencontre'], 0, 5)) : '' ?></td>
    <td><?= htmlspecialchars($types_rencontre_labels[$r['type']] ?? $r['type']) ?></td>
    <td><?= $r['lieu'] ? htmlspecialchars($r['lieu']) : '—' ?></td>
    <td><?= htmlspecialchars($r['sujet'] ?? '') ?></td>
    <td><?= htmlspecialchars($r['participants_noms'] ?? '') ?: '—' ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rencontres): ?>
  <tr><td colspan="6" style="color:var(--mavka-color-text-muted);">Aucune rencontre pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
