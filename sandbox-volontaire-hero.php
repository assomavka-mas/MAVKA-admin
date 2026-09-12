<?php
// Pas liée dans le menu — sert uniquement à comparer des variantes de traitement "photo +
// jaune" pour le hero volontaire, avant de porter le choix retenu dans intervenant.php.
// Photo de substitution : /assets/site-img/img-01 (utilisée déjà comme repli dans l'app quand
// un·e volontaire n'a pas de photo), juste pour juger la forme/l'effet, pas le contenu.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sandbox — hero volontaire</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<style>
body{padding:40px 0}
.sb-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:40px}
@media (max-width:900px){.sb-grid{grid-template-columns:1fr}}
.sb-card{display:flex;flex-direction:column;gap:14px}
.sb-label{font-family:var(--display);font-weight:700;font-size:1.05rem;color:var(--ink)}
.sb-hero{background:var(--mint);border-radius:36px;padding:44px}
.sb-photo-row{display:flex;align-items:center;gap:28px}
.sb-name{font-family:var(--display);font-weight:700;font-size:1.4rem;color:var(--ink)}
.sb-sub{color:var(--ink-3);font-size:.9rem;margin-top:4px}

/* --- variante A : carré arrondi, ellipse jaune décalée en bas à droite (effet ombre) --- */
.sbA{position:relative;width:150px;height:150px;flex:none}
.sbA::before{content:"";position:absolute;width:78%;height:64%;right:-10%;bottom:-12%;border-radius:50%;background:var(--sun);z-index:0}
.sbA img{position:relative;z-index:1;width:150px;height:150px;border-radius:22px;object-fit:cover;display:block;background:var(--card)}

/* --- variante B : carré arrondi, ellipse jaune décalée en bas à gauche --- */
.sbB{position:relative;width:150px;height:150px;flex:none}
.sbB::before{content:"";position:absolute;width:78%;height:64%;left:-10%;bottom:-12%;border-radius:50%;background:var(--sun);z-index:0}
.sbB img{position:relative;z-index:1;width:150px;height:150px;border-radius:22px;object-fit:cover;display:block;background:var(--card)}

/* --- variante C : carré net (coins droits), ellipse jaune bas-droite, plus grande --- */
.sbC{position:relative;width:150px;height:150px;flex:none}
.sbC::before{content:"";position:absolute;width:86%;height:70%;right:-14%;bottom:-16%;border-radius:50%;background:var(--sun);z-index:0}
.sbC img{position:relative;z-index:1;width:150px;height:150px;border-radius:6px;object-fit:cover;display:block;background:var(--card)}

/* --- variante D : cercle (forme actuelle), mais cercle jaune décalé bas-droite (pas centré) --- */
.sbD{position:relative;width:150px;height:150px;flex:none}
.sbD::before{content:"";position:absolute;width:72%;aspect-ratio:1;right:-6%;bottom:-8%;border-radius:50%;background:var(--sun);z-index:0}
.sbD img{position:relative;z-index:1;width:150px;height:150px;border-radius:50%;object-fit:cover;display:block;background:var(--card)}

/* --- variante E : carré arrondi, petite touche jaune juste au coin (discret, pas une ellipse pleine) --- */
.sbE{position:relative;width:150px;height:150px;flex:none}
.sbE::before{content:"";position:absolute;width:46%;height:40%;right:-8%;bottom:-8%;border-radius:50%;background:var(--sun);z-index:0}
.sbE img{position:relative;z-index:1;width:150px;height:150px;border-radius:22px;object-fit:cover;display:block;background:var(--card)}

/* --- variante F : carré arrondi légèrement penché, ombre jaune décalée --- */
.sbF{position:relative;width:150px;height:150px;flex:none}
.sbF::before{content:"";position:absolute;width:80%;height:66%;right:-12%;bottom:-10%;border-radius:44% 56% 50% 50%/54% 48% 52% 46%;background:var(--sun);z-index:0}
.sbF img{position:relative;z-index:1;width:150px;height:150px;border-radius:20px;object-fit:cover;display:block;background:var(--card);transform:rotate(-2deg)}
</style>
</head>
<body>
<div class="wrap">
  <h1 style="margin-bottom:6px">Sandbox — hero volontaire</h1>
  <p class="lede" style="margin-bottom:32px">Page de test, non liée dans le menu. Photo de substitution (logo MAVKA) — seule la forme/l'effet compte ici.</p>

  <div class="sb-grid">
    <div class="sb-card">
      <div class="sb-label">A — carré arrondi, ombre bas-droite</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbA"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">B — carré arrondi, ombre bas-gauche</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbB"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">C — carré net (coins droits), ombre plus large</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbC"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">D — cercle gardé, jaune décalé (pas centré)</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbD"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">E — carré arrondi, touche jaune discrète au coin</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbE"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">F — carré arrondi légèrement incliné + ombre organique</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sbF"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
