<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM intervenants WHERE id = ? AND actif = 1');
$stmt->execute([$id]);
$iv = $stmt->fetch();

if (!$iv) {
    http_response_code(404);
}

$ateliers = $iv ? site_intervenant_ateliers($iv['id']) : [];
$activitesVolontaire = $iv ? site_activites_intervenant($iv['id']) : [];
$photoUrl = ($iv && $iv['photo'] && $iv['dossier'])
    ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo'])
    : '/assets/site-img/img-01-017fac3c9d.webp';

// Présentation / Parcours / Ma vision, affichés en onglets (pilule) sur la page publique.
$sections = [];
if ($iv) {
    if ($iv['bio']) $sections[] = ['key' => 'presentation', 'label' => 'Présentation', 'title' => 'Qui est ' . $iv['nom'], 'text' => $iv['bio']];
    if ($iv['parcours_personnel']) $sections[] = ['key' => 'parcours', 'label' => 'Parcours', 'title' => 'Son parcours', 'text' => $iv['parcours_personnel']];
    if ($iv['vision']) $sections[] = ['key' => 'vision', 'label' => 'Ma vision', 'title' => 'Sa vision', 'text' => $iv['vision']];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $iv ? htmlspecialchars($iv['nom']) . ' — MAVKA' : 'Volontaire introuvable — MAVKA' ?></title>
<meta name="description" content="<?= $iv ? htmlspecialchars($iv['nom'] . ' à MAVKA. ' . ($iv['resume'] ?? '')) : '' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Figtree:wght@400;500;600&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<link rel="stylesheet" href="/assets/event-card.css?v=<?= @filemtime(__DIR__ . '/assets/event-card.css') ?: time() ?>">
<style>
.iv-hero{display:grid;grid-template-columns:220px 1fr;gap:36px;align-items:center}
.iv-hero img{width:220px;height:220px;border-radius:50%;object-fit:cover;background:var(--mint)}
@media (max-width:640px){.iv-hero{grid-template-columns:1fr;justify-items:start}.iv-hero img{width:160px;height:160px}}
.iv-ateliers{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
@media (max-width:820px){.iv-ateliers{grid-template-columns:1fr}}
.iv-atelier{padding:22px;border-radius:var(--r);background:var(--card);border:2px solid var(--mint)}
.iv-atelier p{margin-top:8px;font-size:.96rem;white-space:pre-line}
.back{display:inline-flex;gap:6px;color:var(--ink-2);font-weight:600;font-size:.92rem;margin-bottom:8px}

.tabpanel h2{margin-bottom:14px}
.tabpanel p{color:var(--ink-2);max-width:44em;white-space:pre-line}

.tabs-pill{display:inline-flex;gap:2px;padding:4px;border-radius:999px;background:var(--card);border:2px solid var(--mint);margin-bottom:28px}
.tabs-pill button{border:0;background:transparent;border-radius:999px;padding:10px 22px;font:inherit;font-family:var(--display);font-weight:700;font-size:.95rem;color:var(--ink-2);cursor:pointer}
.tabs-pill button[aria-selected="true"]{background:var(--teal);color:#fff}
</style>
</head>
<body>

<header>
  <div class="wrap bar">
    <a class="brand" href="/index.php#accueil"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""><b>MAVKA</b></a>
    <nav aria-label="Navigation principale">
      <a href="/index.php#agenda">Agenda</a>
      <a href="/index.php#activites">Activités</a>
      <a href="/index.php#equipe" aria-current="page">L'équipe</a>
      <a href="/index.php#intervenants">Devenir intervenant</a>
      <a href="/index.php#collectivites">Collectivités</a>
    </nav>
    <a class="btn btn-primary btn-sm" href="/index.php#contact">Contact</a>
  </div>
</header>

<main>
<?php if (!$iv): ?>
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">L'équipe</span>
        <h1>Cette page n'existe pas (ou plus)</h1>
        <p class="lede">La personne recherchée n'est pas dans l'équipe active de MAVKA.</p>
      </div>
      <p><a class="btn btn-primary" href="/index.php#equipe">Voir toute l'équipe</a></p>
    </div>
  </section>
<?php else: ?>
  <section>
    <div class="wrap">
      <a class="back" href="/index.php#equipe">← Toute l'équipe</a>
      <div class="iv-hero">
        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($iv['nom']) ?>">
        <div>
          <span class="eyebrow">Membre de l'équipe</span>
          <h1 style="margin-top:12px"><?= htmlspecialchars($iv['nom']) ?></h1>
          <?php if ($iv['resume']): ?><p class="lede" style="margin-top:12px"><?= htmlspecialchars($iv['resume']) ?></p><?php endif; ?>
          <?php if ($iv['domaine']): ?><p style="margin-top:10px;color:var(--ink-3);font-size:.92rem"><?= htmlspecialchars($iv['domaine']) ?></p><?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if ($sections): ?>
  <section class="sand">
    <div class="wrap">
      <div class="tabset">
        <div class="tabs-pill" role="tablist" data-tablist aria-label="Sections du profil">
          <?php foreach ($sections as $i => $s): ?>
          <button type="button" role="tab" data-tab="<?= $s['key'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= htmlspecialchars($s['label']) ?></button>
          <?php endforeach; ?>
        </div>
        <?php foreach ($sections as $i => $s): ?>
        <div class="tabpanel" data-panel="<?= $s['key'] ?>"<?= $i === 0 ? '' : ' hidden' ?>>
          <h2><?= htmlspecialchars($s['title']) ?></h2>
          <p><?= htmlspecialchars($s['text']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($ateliers): ?>
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Ce que <?= htmlspecialchars($iv['nom']) ?> propose</span>
        <h2>Ses savoir-faire</h2>
        <p class="lede">Ses grands domaines — pas forcément programmés dans l'immédiat, mais ce vers quoi <?= htmlspecialchars($iv['nom']) ?> avance, pas à pas.</p>
      </div>
      <div class="iv-ateliers">
        <?php foreach ($ateliers as $at): ?>
        <div class="iv-atelier">
          <h3><?= htmlspecialchars($at['titre']) ?></h3>
          <?php if ($at['description']): ?><p><?= htmlspecialchars($at['description']) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($activitesVolontaire): ?>
  <section class="sand">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Concret, dès maintenant</span>
        <h2>Ses activités</h2>
      </div>
      <?= render_events_grid($activitesVolontaire) ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="tight">
    <div class="wrap cta-band">
      <div>
        <h3>Envie de participer à un atelier de <?= htmlspecialchars($iv['nom']) ?> ?</h3>
        <p>Écrivez-nous, nous vous mettrons en relation ou vous préviendrons de la prochaine date.</p>
        <div class="actions"><a class="btn btn-primary" href="/index.php#contact">Nous écrire</a></div>
      </div>
      <svg class="mascot" viewBox="0 0 310 769" style="aspect-ratio:310/769"><use href="#m-stand"/></svg>
    </div>
  </section>
<?php endif; ?>
</main>

<footer>
  <div class="wrap">
    <div class="row">
      <div>
        <a class="brand" href="/index.php#accueil"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""><b>MAVKA</b></a>
        <p style="margin-top:12px;max-width:26em">Association loi 1901, Charente. Culture, éducation, bien-être et développement personnel, dans sept communes autour d'Angoulême.</p>
      </div>
      <div class="cols">
        <div class="col"><b>Découvrir</b><a href="/index.php#agenda">Agenda</a><a href="/index.php#activites">Activités</a><a href="/index.php#equipe">L'équipe</a></div>
        <div class="col"><b>Rejoindre</b><a href="/index.php#intervenants">Devenir intervenant</a><a href="/index.php#collectivites">Collectivités</a><a href="/index.php#contact">Contact</a></div>
        <div class="col"><b>Nous joindre</b><a href="tel:+33656682153">+33 6 56 68 21 53</a><a href="mailto:asso.mavka@gmail.com">asso.mavka@gmail.com</a><span>Lundi au samedi, 10h à 17h</span></div>
      </div>
    </div>
    <div class="legal">
      <span>© 2026 MAVKA · mavka16.fr</span>
      <a href="/index.php#contact">Mentions légales et protection des données</a>
    </div>
  </div>
</footer>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol>
</svg>

<script>
(function(){
  document.querySelectorAll('[data-tablist]').forEach(function(list){
    list.addEventListener('click', function(e){
      var btn = e.target.closest('[role="tab"]');
      if (!btn) return;
      var panels = list.closest('.tabset').querySelectorAll('.tabpanel');
      list.querySelectorAll('[role="tab"]').forEach(function(t){ t.setAttribute('aria-selected', String(t === btn)); });
      panels.forEach(function(p){ p.hidden = (p.dataset.panel !== btn.dataset.tab); });
    });
  });
})();
</script>
</body>
</html>
