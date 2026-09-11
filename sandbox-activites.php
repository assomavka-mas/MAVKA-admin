<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

// Пісочниця : couleur "a" (vert moyen, mix 42% teal/blanc) choisie pour le bandeau .strip —
// vérification finale avant de la reporter dans assets/event-card.css : toutes les
// activités réelles (pas une seule répétée), sur le même fond --mint que la section
// "Prochaines dates" de l'accueil, où les essais précédents se fondaient.
$activites = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Culture'));

// Aperçu seulement (rien n'est écrit en base) : complète Type d'activité / Public / Nombre
// de places là où c'est vide en base, pour voir les plashkas au complet sur chaque carte.
$exemplesFormat = ['Collectif', 'Individuel'];
$exemplesPublic = ['Familial', 'Enfants et adultes', 'Adultes'];
foreach ($activites as $i => &$a) {
    if (empty($a['format'])) $a['format'] = $exemplesFormat[$i % 2];
    if (empty($a['public'])) $a['public'] = $exemplesPublic[$i % 3];
    if ($a['format'] === 'Individuel') {
        $a['nombre_places'] = null;
    } elseif ($a['nombre_places'] === null || $a['nombre_places'] === '') {
        $a['nombre_places'] = 8 + $i * 4;
    }
}
unset($a);
if ($activites) {
    $activites[0]['categorie_display'] = 'Nouveau cours';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pisochnytsia — couleur du bandeau date</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<link rel="stylesheet" href="/assets/sandbox-event-card.css?v=<?= @filemtime(__DIR__ . '/assets/sandbox-event-card.css') ?: time() ?>">
<style>
  .sandbox-banner{background:#241B28;color:#fff;padding:14px 24px;font-family:var(--body);font-size:.9rem}
  .sandbox-banner b{color:#FFCB4D}
  /* Même fond que .agenda-teaser sur l'accueil — c'est là que ça posait problème. */
  .mint-zone{background:var(--mint);border-radius:var(--r);padding:40px;margin:40px 0}
</style>
</head>
<body>
<div class="sandbox-banner"><b>Пісочниця</b> — варіант "a" (vert moyen) перевіряємо на всіх реальних активностях на фоні "Prochaines dates" (var(--mint)).</div>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 469 734"><image href="/assets/site-img/img-05-10df6582b6.webp" width="469" height="734"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 687 768"><image href="/assets/site-img/img-07-00875b060b.webp" width="687" height="768"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>
<div class="wrap">
  <div class="mint-zone strip-a">
    <div class="head" style="margin-bottom:24px">
      <h2>Prochaines dates</h2>
      <p class="lede">Découvrez nos prochains ateliers, rencontres et événements.</p>
    </div>
    <?= render_events_grid($activites, 'three') ?>
  </div>
</div>
</body>
</html>
