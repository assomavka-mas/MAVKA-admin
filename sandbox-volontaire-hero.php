<?php
// Pas liée dans le menu. Round 3 : B et C écartées — comparaison D' (cercle gardé + ombre
// organique, pas un cercle parfait) contre F (carré incliné + ombre organique). Photo réelle
// (sandbox-photo-temp.png), à supprimer avec ce fichier une fois le choix final porté dans
// intervenant.php.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sandbox — hero volontaire (round 3)</title>
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

/* D' — cercle gardé (forme actuelle), mais l'ombre jaune décalée en bas-droite est organique
   (pas un cercle parfait) plutôt qu'un anneau centré. */
.sbD{position:relative}
.sbD::before{content:"";position:absolute;width:76%;height:66%;right:-10%;bottom:-10%;border-radius:58% 42% 48% 52%/46% 54% 44% 56%;background:var(--sun);z-index:0}
.sbD img{position:relative;z-index:1;width:100%;height:100%;border-radius:50%;object-fit:cover;display:block}

/* F — carré arrondi légèrement incliné + ombre organique (inchangée depuis le round précédent) */
.sbF{position:relative}
.sbF::before{content:"";position:absolute;width:80%;height:66%;right:-12%;bottom:-10%;border-radius:44% 56% 50% 50%/54% 48% 52% 46%;background:var(--sun);z-index:0}
.sbF img{position:relative;z-index:1;width:100%;height:100%;border-radius:20px;object-fit:cover;display:block;transform:rotate(-2deg)}
</style>
</head>
<body>
<div class="wrap">
  <h1 style="margin-bottom:6px">Sandbox — hero volontaire (round 3)</h1>
  <p class="lede" style="margin-bottom:32px">D′ (cercle + ombre organique) contre F (carré incliné + ombre organique).</p>

  <div class="sb-grid">
    <div class="sb-card">
      <div class="sb-label">D′ — cercle gardé, ombre organique</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbD"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>

    <div class="sb-card">
      <div class="sb-label">F — carré incliné, ombre organique</div>
      <div class="sb-hero"><div class="sb-photo-row">
        <div class="sb-photo sbF"><img src="/assets/site-img/sandbox-photo-temp.png" alt=""></div>
        <div><div class="sb-name">Nataliia Veremeienko</div><div class="sb-sub">Enseignante en musique · Saxophoniste</div></div>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
