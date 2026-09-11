<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

// Пісочниця : la couleur du bandeau date/lieu (.strip) est la seule question encore ouverte —
// --teal-tint (identique à --mint) et --card-line (déjà essayé en direct, rejeté aussi) se
// fondent tous les deux dans le fond vert pâle de "Prochaines dates". 4 nouvelles variantes
// ici, testées sur ce même fond, avant de reporter la bonne dans assets/event-card.css.
// Charge sandbox-event-card.css (copie de travail), jamais le vrai assets/event-card.css.
$activites = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Culture'));
$exemple = $activites[0] ?? null;
if ($exemple) {
    $exemple['categorie_display'] = 'Nouveau cours';
    if (empty($exemple['format'])) $exemple['format'] = 'Collectif';
    if (empty($exemple['nombre_places'])) $exemple['nombre_places'] = 12;
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
  .compare{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
  .compare h3{font-size:1rem;margin-bottom:12px}
  @media (max-width:820px){.compare{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="sandbox-banner"><b>Пісочниця</b> — вибираємо колір прямокутника з датою (.strip) на фоні "Prochaines dates" (var(--mint)), той самий фон, де попередні спроби зливались.</div>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 469 734"><image href="/assets/site-img/img-05-10df6582b6.webp" width="469" height="734"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 687 768"><image href="/assets/site-img/img-07-00875b060b.webp" width="687" height="768"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>
<div class="wrap">
  <?php if ($exemple): ?>
  <div class="mint-zone">
    <div class="compare">
      <div>
        <h3>a — vert moyen (mix 42% avec blanc)</h3>
        <div class="strip-a"><?= render_event_card($exemple) ?></div>
      </div>
      <div>
        <h3>b — vert plein (même teal que le bouton), texte blanc</h3>
        <div class="strip-b"><?= render_event_card($exemple) ?></div>
      </div>
      <div>
        <h3>c — bandeau blanc, seul le carré date reste vert</h3>
        <div class="strip-c"><?= render_event_card($exemple) ?></div>
      </div>
      <div>
        <h3>d — vert foncé (teal-deep), texte blanc</h3>
        <div class="strip-d"><?= render_event_card($exemple) ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
