<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Vue transversale de toutes les rencontres, tous partenaires confondus — le détail reste dans
// l'onglet Rencontres de partenaire-form.php, cette page sert à repérer d'un coup d'œil les
// prochaines actions à faire sans ouvrir chaque fiche une par une.
$user = auth_require(['super_admin', 'mavka_admin']);

$types_rencontre_labels = ['rencontre' => 'Rencontre', 'appel' => 'Appel', 'email' => 'Email', 'courrier' => 'Courrier'];
$etapes_labels = [
    'premiere_rencontre' => 'Première rencontre',
    'co_construction' => 'Co-construction',
    'phase_pilote' => 'Phase pilote',
    'mise_en_place' => 'Mise en place progressive',
    'faire_evoluer' => 'Faire évoluer le partenariat',
    'bilan' => 'Faire le bilan et décider de la suite',
];

$rencontres = db()->query('
    SELECT r.*, o.nom AS organisation_nom
    FROM partenaires_rencontres r
    JOIN partenaires_organisations o ON o.id = r.organisation_id
    ORDER BY r.date_rencontre DESC, r.heure_rencontre DESC
')->fetchAll();

admin_header('Rencontres', $user, 'rencontres');
?>
<h1>Rencontres</h1>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 16px;">Toutes les rencontres, tous partenaires confondus, les plus récentes en premier. Clique une organisation pour ouvrir sa fiche complète.</p>

<table class="mavka-table">
  <tr><th>Organisation</th><th>Date</th><th>Type</th><th>Lieu</th><th>Sujet</th><th>Étape</th><th>Prochaine action</th></tr>
  <?php foreach ($rencontres as $r): ?>
  <tr>
    <td><a href="/admin/partenaire-form.php?id=<?= $r['organisation_id'] ?>#rencontres"><?= htmlspecialchars($r['organisation_nom']) ?></a></td>
    <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rencontre']))) ?><?= $r['heure_rencontre'] ? ' à ' . htmlspecialchars(substr($r['heure_rencontre'], 0, 5)) : '' ?></td>
    <td><?= htmlspecialchars($types_rencontre_labels[$r['type']] ?? $r['type']) ?></td>
    <td><?= $r['lieu'] ? htmlspecialchars($r['lieu']) : '—' ?></td>
    <td><?= htmlspecialchars($r['sujet'] ?? '') ?></td>
    <td><?= $r['etape_parcours'] ? htmlspecialchars($etapes_labels[$r['etape_parcours']] ?? $r['etape_parcours']) : '—' ?></td>
    <td>
      <?php if ($r['date_prochaine_action']): ?>
        <?= htmlspecialchars($r['prochaine_action'] ?? '') ?> — <?= htmlspecialchars(date('d/m/Y', strtotime($r['date_prochaine_action']))) ?>
        <a href="<?= htmlspecialchars(google_calendar_lien(
            'MAVKA — ' . $r['organisation_nom'] . ' : ' . ($r['prochaine_action'] ?: 'Suivi'),
            $r['date_prochaine_action'],
            $r['compte_rendu'] ?? ''
        )) ?>" target="_blank" rel="noopener" title="Ajouter à Google Calendar">📅</a>
      <?php else: ?>—<?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rencontres): ?>
  <tr><td colspan="7" style="color:var(--mavka-color-text-muted);">Aucune rencontre pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
