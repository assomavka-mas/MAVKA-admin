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

// Pour la comparaison, on complète ici (aperçu seulement, rien n'est écrit en base) les
// activités qui n'ont pas encore de Type d'activité / Public / Nombre de places renseignés,
// pour voir les trois plashkas ensemble sur chaque carte.
$exemplesFormat = ['Collectif', 'Individuel'];
$exemplesPublic = ['Familial', 'Enfants et adultes', 'Adultes'];
foreach ($activites as $i => &$a) {
    if (empty($a['format'])) $a['format'] = $exemplesFormat[$i % 2];
    if (empty($a['public'])) $a['public'] = $exemplesPublic[$i % 3];
    // Individuel = pas de nombre de places (même règle que admin/activite-form.php).
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
  .variant-block{margin-bottom:56px;padding-bottom:56px;border-bottom:1px solid var(--line)}
  .variant-block h3{font-size:1.1rem;margin-bottom:16px}
  .dark-compare{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;max-width:760px;margin-bottom:56px;padding-bottom:56px;border-bottom:1px solid var(--line)}
  .dark-compare h3{font-size:1.1rem;margin-bottom:16px}
  /* Simule une photo sombre (portrait pris en intérieur, vêtements foncés...) pour juger la
     lisibilité des plashkas translucides dans le pire des cas — pas une vraie photo. */
  .dark-photo .cover{background:linear-gradient(160deg,#2a2130,#4a3550)}
  .dark-photo .cover::after{content:"";position:absolute;inset:0;z-index:0;background:radial-gradient(circle at 30% 20%,rgba(0,0,0,.15),rgba(0,0,0,.55) 80%)}
  .dark-photo .cover .mascot{opacity:.35}
</style>
</head>
<body>
<div class="sandbox-banner"><b>Пісочниця</b> — тут можна вільно міняти вигляд картки Activité (assets/sandbox-event-card.css), не займаючи реальний сайт. Type d'activité / Public / Nombre de places, де порожні в базі, тут дозаповнені прикладом лише для перегляду — в базу нічого не пишеться.</div>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 469 734"><image href="/assets/site-img/img-05-10df6582b6.webp" width="469" height="734"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 687 768"><image href="/assets/site-img/img-07-00875b060b.webp" width="687" height="768"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>
<div class="wrap" style="padding:40px 0">

  <?php if ($exemple): ?>
  <div>
    <h2 style="margin-bottom:8px">v-white vs v-dark, sur une photo sombre simulée</h2>
    <p class="lede" style="margin-bottom:24px">Fond de photo assombri artificiellement (pas une vraie photo) pour juger la lisibilité dans le pire des cas.</p>
    <div class="dark-compare">
      <div><h3>v-white — blanc translucide</h3><div class="v-white dark-photo"><?= render_event_card($exemple) ?></div></div>
      <div><h3>v-dark — encre translucide</h3><div class="v-dark dark-photo"><?= render_event_card($exemple) ?></div></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="variant-block v-white">
    <h3>v-white — blanc translucide</h3>
    <?= render_events_grid($activites, 'three') ?>
  </div>

  <div class="variant-block v-cream">
    <h3>v-cream — carte blanche + fine bordure</h3>
    <?= render_events_grid($activites, 'three') ?>
  </div>

  <div class="variant-block v-dark" style="border-bottom:none;margin-bottom:0;padding-bottom:0">
    <h3>v-dark — encre translucide</h3>
    <?= render_events_grid($activites, 'three') ?>
  </div>

</div>
</body>
</html>
