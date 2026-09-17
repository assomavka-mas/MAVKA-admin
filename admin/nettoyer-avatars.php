<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Outil ponctuel : les avatars (MAVKA-avatar + avatars par direction) envoyés AVANT la mise en
// place du détourage automatique (retirer_fond_blanc(), voir includes/functions.php) ont gardé
// leur fond blanc d'origine — le détourage ne s'applique qu'aux nouveaux envois. Ce bouton
// retraite en une fois tous les avatars déjà en base, sans avoir à les réenvoyer un par un.
$user = auth_require(['super_admin']);

$champs_avatars = ['avatar_mavka', 'avatar_domaine_culture', 'avatar_domaine_education', 'avatar_domaine_bien_etre', 'avatar_domaine_initiatives'];

$journal = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $journal = [];
    $rows = db()->query('SELECT id, nom, dossier, ' . implode(', ', $champs_avatars) . ' FROM intervenants')->fetchAll();
    foreach ($rows as $iv) {
        foreach ($champs_avatars as $f) {
            if (empty($iv[$f])) {
                continue;
            }
            if (empty($iv['dossier'])) {
                $journal[] = ['nom' => $iv['nom'], 'champ' => $f, 'statut' => 'dossier-vide'];
                continue;
            }
            $chemin = __DIR__ . '/../assets/uploads/intervenants/' . $iv['dossier'] . '/' . $iv[$f];
            $journal[] = ['nom' => $iv['nom'], 'champ' => $f, 'statut' => retirer_fond_blanc($chemin)];
        }
    }
}

admin_header('Nettoyer les avatars existants', $user);
?>
<div class="mavka-form-section" style="padding:20px;">
  <h3>Nettoyer le fond des avatars déjà envoyés</h3>
  <p class="mavka-form-section__hint">Les avatars (MAVKA-avatar et avatars par direction) envoyés avant la mise en place du détourage automatique ont gardé leur fond blanc d'origine. Ce bouton les retraite tous en une fois — sans risque, on peut le relancer plusieurs fois si besoin.</p>

  <?php if ($journal !== null): ?>
    <?php if (!$journal): ?>
    <p>Aucun avatar trouvé en base.</p>
    <?php else: ?>
    <table style="margin-top:14px; border-collapse:collapse; width:100%;">
      <thead><tr><th style="text-align:left; padding:6px 10px;">Personne</th><th style="text-align:left; padding:6px 10px;">Avatar</th><th style="text-align:left; padding:6px 10px;">Résultat</th></tr></thead>
      <tbody>
      <?php foreach ($journal as $l): ?>
        <tr style="border-top:1px solid var(--mavka-color-cream-soft);">
          <td style="padding:6px 10px;"><?= htmlspecialchars($l['nom']) ?></td>
          <td style="padding:6px 10px;"><?= htmlspecialchars($l['champ']) ?></td>
          <td style="padding:6px 10px; font-weight:600; color:<?= $l['statut'] === 'ok' ? 'var(--mavka-color-teal)' : '#c0392b' ?>;"><?= $l['statut'] === 'ok' ? '✓ nettoyé' : '✗ ' . htmlspecialchars($l['statut']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  <?php endif; ?>

  <form method="post">
    <button type="submit" class="mavka-btn mavka-btn--primary">Nettoyer maintenant</button>
  </form>
</div>
<?php admin_footer(); ?>
