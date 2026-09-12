<?php
// Pas liée dans le menu. Round 4 : F retenue comme base — 2 variantes, angle de l'ombre
// organique plus marqué (moins "presque ovale", plus reconnaissable comme forme organique) ;
// G sans inclinaison de la photo, H avec (comme F). Photo réelle (sandbox-photo-temp.png), à
// supprimer avec ce fichier une fois le choix final porté dans intervenant.php.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sandbox — hero volontaire (round 4)</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<style>
body{padding:40px 0}
.sb-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:40px;max-width:900px}
@media (max-width:800px){.sb-grid{grid-template-columns:1fr}}
.sb-card{display:flex;flex-direction:column;gap:14px}
.sb-label{font-family:var(--display);font-weight:700;font-size:1.05rem;color:var(--ink)}
.sb-hero{background:var(--mint);border-radius:36px;padding:40px 32px}
.sb-photo-row{display:flex;align-items:center;gap:24px}
.sb-name{font-family:var(--display);font-weight:700;font-size:1.4rem;color:var(--ink)}
.sb-sub{color:var(--ink-3);font-size:.9rem;margin-top:4px}
.sb-photo{width:180px;height:180px;flex:none}
@media (max-width:800px){.sb-photo-row{flex-direction:column;align-items:flex-start}}

/* G — droite (pas d'inclinaison), ombre organique à l'angle plus marqué */
.sbG{position:relative}
.sbG::before{content:"";position:absolute;width:82%;height:70%;right:-13%;bottom:-12%;border-radius:62% 38% 40% 60%/58% 42% 65% 35%;background:var(--sun);z-index:0}
.sbG img{position:relative;z-index:1;width:100%;height:100%;border-radius:20px;object-fit:cover;display:block}

/* H — légèrement inclinée (comme F), même ombre organique à l'angle plus marqué */
.sbH{position:relative}
.sbH::before{content:"";position:absolute;width:82%;height:70%;right:-13%;bottom:-12%;border-radius:62% 38% 40% 60%/58% 42% 65% 35%;background:var(--sun);z-index:0}
.sbH img{position:relative;z-index:1;width:100%;height:100%;border-radius:20px;object-fit:cover;display:block;transform:rotate(-2deg)}
</style>
</head>
<body>
<div class="wrap">
  <h1 style="margin-bottom:6px">Sandbox — hero volontaire (round 4)</h1>
  <p class="lede" style="margin-bottom:32px">F retenue comme base — angle de l'ombre organique augmenté, avec/sans inclinaison de la photo.</p>

  <div class="sb-grid">
    <div class="sb-card">
      <div class="sb-label">G — droite, ombre organique marquée</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbG"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">H — inclinée, ombre organique marquée</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbH"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
