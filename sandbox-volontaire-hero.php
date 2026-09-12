<?php
// Pas liée dans le menu. Round 2 : seulement B/C/F (retenues), avec une vraie photo cette
// fois plutôt que le logo — sandbox-photo-temp.png, à supprimer avec ce fichier une fois le
// choix final porté dans intervenant.php.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sandbox — hero volontaire (round 2)</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<style>
body{padding:40px 0}
.sb-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:32px}
@media (max-width:1000px){.sb-grid{grid-template-columns:1fr}}
.sb-card{display:flex;flex-direction:column;gap:14px}
.sb-label{font-family:var(--display);font-weight:700;font-size:1.05rem;color:var(--ink)}
.sb-hero{background:var(--mint);border-radius:36px;padding:40px 32px}
.sb-photo-row{display:flex;align-items:center;gap:24px}
.sb-name{font-family:var(--display);font-weight:700;font-size:1.4rem;color:var(--ink)}
.sb-sub{color:var(--ink-3);font-size:.9rem;margin-top:4px}
.sb-photo{width:170px;height:170px;flex:none}
@media (max-width:1000px){.sb-photo-row{flex-direction:column;align-items:flex-start}}

/* B — carré arrondi, ombre bas-gauche */
.sbB{position:relative}
.sbB::before{content:"";position:absolute;width:78%;height:64%;left:-10%;bottom:-12%;border-radius:50%;background:var(--sun);z-index:0}
.sbB img{position:relative;z-index:1;width:100%;height:100%;border-radius:22px;object-fit:cover;display:block}

/* C — carré net (coins droits), ombre plus large */
.sbC{position:relative}
.sbC::before{content:"";position:absolute;width:86%;height:70%;right:-14%;bottom:-16%;border-radius:50%;background:var(--sun);z-index:0}
.sbC img{position:relative;z-index:1;width:100%;height:100%;border-radius:6px;object-fit:cover;display:block}

/* F — carré arrondi légèrement incliné + ombre organique */
.sbF{position:relative}
.sbF::before{content:"";position:absolute;width:80%;height:66%;right:-12%;bottom:-10%;border-radius:44% 56% 50% 50%/54% 48% 52% 46%;background:var(--sun);z-index:0}
.sbF img{position:relative;z-index:1;width:100%;height:100%;border-radius:20px;object-fit:cover;display:block;transform:rotate(-2deg)}
</style>
</head>
<body>
<div class="wrap">
  <h1 style="margin-bottom:6px">Sandbox — hero volontaire (round 2)</h1>
  <p class="lede" style="margin-bottom:32px">B, C, F retenues du premier tour — avec une vraie photo cette fois.</p>

  <div class="sb-grid">
    <div class="sb-card">
      <div class="sb-label">B — ombre bas-gauche</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbB"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">C — coins droits, ombre plus large</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbC"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">F — légèrement incliné + ombre organique</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbF"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
