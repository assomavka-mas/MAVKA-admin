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

$resultat = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $traites = 0;
    $rows = db()->query('SELECT id, dossier, ' . implode(', ', $champs_avatars) . ' FROM intervenants')->fetchAll();
    foreach ($rows as $iv) {
        if (empty($iv['dossier'])) {
            continue;
        }
        foreach ($champs_avatars as $f) {
            if (empty($iv[$f])) {
                continue;
            }
            $chemin = __DIR__ . '/../assets/uploads/intervenants/' . $iv['dossier'] . '/' . $iv[$f];
            if (is_file($chemin)) {
                retirer_fond_blanc($chemin);
                $traites++;
            }
        }
    }
    $resultat = $traites;
}

admin_header('Nettoyer les avatars existants', $user);
?>
<div class="mavka-form-section" style="padding:20px;">
  <h3>Nettoyer le fond des avatars déjà envoyés</h3>
  <p class="mavka-form-section__hint">Les avatars (MAVKA-avatar et avatars par direction) envoyés avant la mise en place du détourage automatique ont gardé leur fond blanc d'origine. Ce bouton les retraite tous en une fois — sans risque, on peut le relancer plusieurs fois si besoin.</p>

  <?php if ($resultat !== null): ?>
  <p style="color:var(--mavka-color-teal); font-weight:600;">✓ Terminé — <?= $resultat ?> fichier(s) retraité(s).</p>
  <?php endif; ?>

  <form method="post">
    <button type="submit" class="mavka-btn mavka-btn--primary">Nettoyer maintenant</button>
  </form>
</div>
<?php admin_footer(); ?>
