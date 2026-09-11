<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

// Пісочниця : redesign de la carte Activité, isolée du reste du site — charge
// sandbox-event-card.css (copie de travail), jamais le vrai assets/event-card.css.
// Pas de lien dans la nav ; accessible seulement par son URL directe. Une fois le
// design validé : reporter dans assets/event-card.css, puis supprimer ce fichier
// et assets/sandbox-event-card.css.
$activites = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Culture'));
$exemple = $activites[0] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pisochnytsia — carte Activité</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<link rel="stylesheet" href="/assets/sandbox-event-card.css?v=<?= @filemtime(__DIR__ . '/assets/sandbox-event-card.css') ?: time() ?>">
<style>
  .sandbox-banner{background:#241B28;color:#fff;padding:14px 24px;font-family:var(--body);font-size:.9rem}
  .sandbox-banner b{color:#FFCB4D}
  .variants{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:56px}
  .variants h3{font-size:.95rem;margin-bottom:10px;color:var(--ink-2)}
</style>
</head>
<body>
<div class="sandbox-banner"><b>Пісочниця</b> — тут можна вільно міняти вигляд картки Activité (assets/sandbox-event-card.css), не займаючи реальний сайт.</div>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 469 734"><image href="/assets/site-img/img-05-10df6582b6.webp" width="469" height="734"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 687 768"><image href="/assets/site-img/img-07-00875b060b.webp" width="687" height="768"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>
<div class="wrap" style="padding:40px 0">
  <div class="head" style="margin-bottom:24px">
    <h2>Plashkas Format/Public/Places — 3 variantes neutres</h2>
    <p class="lede">corner--tl (sous-titre, ex. « Nouveau cours ») reste jaune dans les trois. La bordure verte 1px et la bande de date/lieu en vert sont déjà appliquées partout ci-dessous.</p>
  </div>
  <?php if ($exemple): $exemple['categorie_display'] = 'Nouveau cours'; if (empty($exemple['nombre_places'])) $exemple['nombre_places'] = 12; ?>
  <div class="variants">
    <div><h3>v-white — blanc translucide</h3><div class="v-white"><?= render_event_card($exemple) ?></div></div>
    <div><h3>v-cream — carte + fine bordure</h3><div class="v-cream"><?= render_event_card($exemple) ?></div></div>
    <div><h3>v-dark — encre translucide</h3><div class="v-dark"><?= render_event_card($exemple) ?></div></div>
  </div>
  <?php endif; ?>

  <div class="head" style="margin-bottom:32px">
    <span class="eyebrow">Culture</span>
    <h2>Créer de ses mains, découvrir une tradition</h2>
  </div>
  <?= render_events_grid($activites, 'three') ?>
</div>
</body>
</html>
