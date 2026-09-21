<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

$site_intervenants = site_intervenants_actifs();
// Individuel exclu partout sur l'accueil (Agenda et Activités) : réservé aux pages volontaire
// (site_activites_intervenant(), sans ce filtre) — un cours individuel n'a pas sa place dans une
// grille publique partagée.
$site_agenda_teaser = site_enrichir_avec_photo_intervenant(site_activites_a_venir(6, ['Individuel'], true));
$site_agenda_toutes = site_enrichir_avec_photo_intervenant(site_activites_a_venir(null, ['Individuel']));
$site_activites_culture = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Culture', ['Individuel']));
$site_activites_education = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Éducation', ['Individuel']));
$site_activites_bienetre = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Bien-être', ['Individuel']));
$site_activites_evenementiel = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Événementiel', ['Individuel']));
$site_activites_initiatives = site_enrichir_avec_photo_intervenant(site_activites_par_categorie('Initiatives', ['Individuel']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="MAVKA — association loi 1901 en Charente. Ateliers de culture, éducation, bien-être et développement personnel dans sept communes autour d'Angoulême.">
<title>MAVKA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<!-- PT Serif (titres) + Nunito Sans (texte) : choix fixé après comparaison sur la page réelle —
     toutes deux vérifiées pour le cyrillique (nécessaire pour la bascule UK), contrairement
     aux anciennes Bricolage Grotesque/Figtree. Fraunces reste chargée pour le thème "Lin". -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(__DIR__ . '/assets/site.css') ?: time() ?>">
<link rel="stylesheet" href="/assets/event-card.css?v=<?= @filemtime(__DIR__ . '/assets/event-card.css') ?: time() ?>">
</head>
<body>

<?= render_apercu_banner() ?>
<header>
  <div class="wrap bar">
    <a class="brand" href="#accueil"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""><b>MAVKA</b></a>
    <nav id="nav" aria-label="Navigation principale">
      <a href="#agenda" data-route="agenda">Agenda</a>
      <a href="#activites" data-route="activites">Activités</a>
      <a href="#equipe" data-route="equipe">L'équipe</a>
      <a href="#intervenants" data-route="intervenants">Devenir intervenant</a>
      <a href="#collectivites" data-route="collectivites">Collectivités</a>
    </nav>
    <a class="btn btn-primary btn-sm" href="#contact">Contact</a>
    <button class="lang" id="langBtn" type="button" aria-label="Змінити мову">УКР</button>
    <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="nav">Menu</button>
  </div>
</header>

<main><svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 555 846"><image href="/assets/site-img/img-05-10df6582b6.webp" width="555" height="846"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 404 867"><image href="/assets/site-img/img-07-b2cfcc755b.webp" width="404" height="867"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>

<!-- ============ ACCUEIL ============ -->
<div class="page" id="page-accueil">
  <section class="hero">
    <div class="wrap grid">
      <div class="hero-copy">
        <h1>Un espace pour apprendre, créer, partager et transmettre</h1>
        <p class="lede">MAVKA réunit habitants et bénévoles autour d'activités culturelles, éducatives et de bien-être. Les ateliers se construisent progressivement avec les intervenants, les participants et les partenaires locaux.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#activites">Découvrir les activités</a>
          <a class="btn btn-ghost" href="#intervenants">Rejoindre MAVKA</a>
        </div>
      </div>
      <div class="hero-art"><img src="/assets/site-img/img-03-1d459c08a4.webp" alt="Mavka, la mascotte de l'association, saluant de la main"></div>
    </div>
  </section>

  <div class="wrap">
    <div class="stats-strip">
      <div class="stat stat--place">
        <b><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-7.5-7-12a7 7 0 0 1 14 0c0 4.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/></svg> Grand Angoulême</b>
        <p>Un projet associatif qui se développe progressivement sur le territoire.</p>
      </div>
      <div class="stat">
        <b>2026</b>
        <p>Année de création de MAVKA.</p>
      </div>
      <div class="stat">
        <b><?= count($site_intervenants) ?></b>
        <p>Bénévoles engagés dans le développement de MAVKA.</p>
      </div>
    </div>
  </div>

  <section>
    <div class="wrap dir-grid">
      <div>
        <div class="head" style="margin-bottom:24px">
          <span class="eyebrow">Nos directions</span>
          <h2>Ce que vous pouvez découvrir chez MAVKA</h2>
        </div>
        <div class="dirs">
          <div class="dir">
            <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-4.5 0-8 3.6-8 8 0 3 2 4 4 4h1a2 2 0 0 1 2 2c0 1.5 1 2 2 2 4.5 0 7-3 7-8 0-4.4-3.5-8-8-8z"/><circle cx="8.5" cy="10" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="15.5" cy="10" r="1"/></svg></div>
            <div><h3><a href="#activites-culture">Culture</a></h3><p>Découvrir et partager les cultures à travers les arts, les traditions et les savoir-faire : Petrykivka, artisanat, calligraphie chinoise et rencontres interculturelles.</p></div>
          </div>
          <div class="dir">
            <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V6l10-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="16" r="2.5"/></svg></div>
            <div><h3><a href="#activites-education">Éducation</a></h3><p>Apprendre et transmettre à tout âge : musique, langues, création numérique, impression 3D et nouveaux ateliers proposés par nos intervenants.</p></div>
          </div>
          <div class="dir">
            <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg></div>
            <div><h3><a href="#activites-bienetre">Bien-être</a></h3><p>Bouger, respirer, retrouver son équilibre et prendre du temps pour soi grâce à des pratiques accessibles et adaptées.</p></div>
          </div>
          <div class="dir">
            <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V11"/><path d="M12 11c0-4 3-6 7-6 0 4-3 6-7 6z"/><path d="M12 14c0-3-2.5-5-6-5 0 3 2.5 5 6 5z"/></svg></div>
            <div><h3><a href="#activites-initiatives">Initiatives</a></h3><p>Vous avez une compétence, une passion ou une idée à partager ? MAVKA vous accompagne pour la tester, construire un atelier et la faire évoluer progressivement.</p></div>
          </div>
        </div>
      </div>
      <div class="dir-art"><img src="/assets/site-img/img-06-3bc40d08fc.webp" alt="Mavka assise, en train de lire"></div>
    </div>
  </section>

  <section class="agenda-teaser">
    <div class="wrap">
      <div class="head">
        <h2>Les activités se construisent avec vous</h2>
        <p class="lede">Les préinscriptions nous permettent de connaître les besoins, de constituer les groupes et d'organiser les activités dans les meilleures conditions. Votre intérêt nous aide également à rechercher les lieux et les conditions nécessaires à leur mise en place avec nos partenaires locaux.</p>
      </div>
      <?= render_events_grid($site_agenda_teaser, 'three compact') ?>
      <p style="margin-top:22px"><a class="btn btn-ghost" href="#agenda">Voir tout l'agenda</a></p>
    </div>
  </section>

  <section>
    <div class="wrap team-preview">
      <div>
        <span class="eyebrow">L'équipe</span>
        <h2 style="margin-top:8px">Des savoir-faire à découvrir et à partager</h2>
        <p class="lede" style="margin-top:10px">Artistes, musiciens, enseignants, praticiens et créateurs apportent à MAVKA leurs expériences, leurs compétences et l'envie de les transmettre.</p>
        <p style="margin-top:16px"><a class="btn btn-ghost" href="#equipe">Rencontrer l'équipe</a></p>
      </div>
      <div class="faces"><?php foreach (array_slice($site_intervenants, 0, 6) as $iv):
        $photoUrl = ($iv['photo'] && $iv['dossier']) ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']) : '/assets/site-img/img-01-017fac3c9d.webp';
      ?><img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($iv['nom']) ?>"><?php endforeach; ?></div>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="cta-card">
        <h3>Et si vous passiez de participant à acteur ?</h3>
        <p>MAVKA ne propose pas seulement des activités. L'association permet aussi à celles et ceux qui ont une compétence, une expérience ou une idée de la partager avec les autres.</p>
        <p>Vous pouvez commencer simplement : rencontrer l'équipe, proposer une idée, tester un atelier et construire progressivement votre projet avec MAVKA.</p>
        <div class="actions">
          <a class="btn btn-ghost" href="#intervenants">Découvrir le Parcours MAVKA →</a>
          <a class="btn btn-primary" href="#contact">Nous écrire →</a>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ============ AGENDA ============ -->
<div class="page" id="page-agenda">
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Agenda</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Toutes les prochaines dates</h1>
        <p class="lede">Retrouvez ici les ateliers, rencontres et événements proposés par MAVKA et ses partenaires.</p>
      </div>
      <?= render_events_grid($site_agenda_toutes, 'three') ?>
    </div>
  </section>
  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:16px">
          <span class="eyebrow">Et aussi...</span>
          <h2>Vous ne trouvez pas encore ce que vous cherchez ?</h2>
        </div>
        <p class="lede">Certaines activités sont encore en préparation. Dites-nous ce qui vous intéresse et dans quelle commune : vos demandes nous aident à constituer les groupes et à organiser les prochains ateliers.</p>
        <div class="actions" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
          <a class="btn btn-primary" href="#activites">Découvrir les activités</a>
          <a class="btn btn-ghost" href="#contact">Nous faire part de votre intérêt</a>
        </div>
      </div>
      <div class="dir-art"><svg class="mascot tall" viewBox="0 0 555 846" style="aspect-ratio:555/846"><use href="#m-jump"/></svg></div>
    </div>
  </section>
</div>

<!-- ============ ACTIVITES ============ -->
<div class="page" id="page-activites">
  <section style="padding-bottom:0">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Nos activités</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Activités</h1>
        <p class="lede">Découvrez les activités proposées par MAVKA, regroupées par domaine.</p>
      </div>
    </div>
  </section>

  <nav class="activites-jump" aria-label="Aller à un domaine">
    <div class="wrap">
      <a href="#activites-culture">Culture</a>
      <a href="#activites-education">Éducation</a>
      <a href="#activites-bienetre">Bien-être</a>
      <a href="#activites-evenementiel">Événementiel</a>
      <a href="#activites-initiatives">Initiatives</a>
    </div>
  </nav>

  <section class="sand" id="activites-culture">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Culture</span><h2>Créer de ses mains, découvrir une tradition</h2></div>
      <?= render_events_grid($site_activites_culture, 'three') ?>
    </div>
  </section>

  <section id="activites-education">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Éducation</span><h2>Apprendre un savoir-faire concret</h2></div>
      <?= render_events_grid($site_activites_education, 'three') ?>
    </div>
  </section>

  <section class="sand" id="activites-bienetre">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Bien-être</span><h2>Prendre soin de soi, simplement</h2></div>
      <?= render_events_grid($site_activites_bienetre, 'three') ?>
    </div>
  </section>

  <section id="activites-evenementiel">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Événementiel</span><h2>Fêtes, festivals et rendez-vous ponctuels</h2></div>
      <?= render_events_grid($site_activites_evenementiel, 'three') ?>
    </div>
  </section>

  <section class="sand" id="activites-initiatives">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Initiatives</span><h2>Tester une idée, un pas à la fois</h2></div>
      <?= render_events_grid($site_activites_initiatives, 'three') ?>
    </div>
  </section>

  <section>
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:16px"><span class="eyebrow">Le Parcours MAVKA</span><h2>Construire son propre projet</h2></div>
        <p class="lede">Cette direction s'adresse aux personnes qui souhaitent partager un savoir-faire, développer une idée ou faire leurs premiers pas autour d'un projet.</p>
        <p class="lede" style="margin-top:12px">Le Parcours MAVKA permet d'avancer progressivement, de l'idée à une première activité concrète, avec l'accompagnement de Larysa Mas et l'appui de l'association.</p>
        <p style="margin-top:18px"><a class="btn btn-primary" href="#intervenants">Découvrir le Parcours</a></p>
      </div>
      <div class="dir-art"><svg class="mascot tall" viewBox="0 0 404 867" style="aspect-ratio:404/867"><use href="#m-point"/></svg></div>
    </div>
  </section>
</div>

<!-- ============ INTERVENANTS ============ -->
<div class="page" id="page-intervenants">
  <section class="hero">
    <div class="wrap grid">
      <div class="hero-copy">
        <span class="eyebrow">Le Parcours MAVKA</span>
        <h1 style="font-size:clamp(2rem,4.4vw,3.4rem)">Vous avez un savoir-faire. Donnons-lui vie ensemble.</h1>
        <p class="lede">Le Parcours s'adresse aux enseignants, artistes, musiciens, professeurs de yoga et praticiens qui traversent une transition professionnelle, souvent après une arrivée en France. Quatre étapes, à votre rythme, dans un cadre sécurisant.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#contact">Demander une première rencontre</a>
          <a class="btn btn-ghost" href="#steps">Voir les quatre étapes</a>
        </div>
      </div>
      <div class="hero-art"><img src="/assets/site-img/img-07-b2cfcc755b.webp" alt="Mavka pointant vers le haut, debout, main sur la hanche"></div>
    </div>
  </section>

  <section id="steps">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Le Parcours MAVKA</span>
        <h2>De l'idée au premier pas concret</h2>
        <p class="lede">Vous avez une passion, une compétence, un savoir-faire ou simplement une idée ? Le Parcours MAVKA vous permet d'avancer progressivement, d'expérimenter et de trouver la forme qui vous correspond.</p>
      </div>

      <img class="parcours-visuel" src="/assets/site-img/parcours-etapes.webp?v=<?= @filemtime(__DIR__ . '/assets/site-img/parcours-etapes.webp') ?: time() ?>" alt="Ta passion, ton nouveau départ : les 4 étapes du Parcours MAVKA sous forme d'escalier — Immersion, Engagement, Expérimentation, Collaboration durable">
      <p class="parcours-visuel__note">Il n'y a pas de parcours imposé : chacun avance à son rythme et selon son projet.</p>

      <div class="steps" style="margin-top:32px">
        <div class="step"><h3>Immersion &amp; découverte</h3><p>Vous découvrez MAVKA, ses activités, son équipe et sa manière de fonctionner. Vous échangez avec nous sur vos envies, vos compétences ou votre idée, sans devoir avoir déjà un projet défini.</p></div>
        <div class="step"><h3>Engagement</h3><p>Si l'approche vous parle, vous pouvez participer à la vie de MAVKA, rejoindre l'équipe bénévole ou commencer à construire votre idée avec nous. Chacun avance selon sa situation et ses disponibilités.</p></div>
        <div class="step"><h3>Expérimentation</h3><p><strong>Ici, l'idée devient concrète.</strong> Vous préparez et testez une première activité. Vous vérifiez le format, l'intérêt du public et ce qui fonctionne. Ensemble, nous décidons ensuite s'il est utile de poursuivre ou d'adapter l'activité.</p></div>
        <div class="step"><h3>Collaboration durable</h3><p>Plusieurs chemins sont possibles.</p><p style="color:var(--ink-3); font-size:.85rem; margin-top:4px">Bénévolat · Projet · Prestation professionnelle</p></div>
      </div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:20px"><span class="eyebrow">Ce que MAVKA apporte</span><h2>Un tremplin, pas un contrat de travail</h2></div>
        <ul class="list">
          <li><span>Un cadre associatif clair et assuré pour expérimenter une première activité dans le cadre de MAVKA.</span></li>
          <li><span>Un appui pour rechercher un lieu, un public ou des partenaires, selon les besoins de l'activité et les possibilités de MAVKA.</span></li>
          <li><span>La prise en charge ou le remboursement de certains frais nécessaires, lorsqu'ils ont été prévus et validés avec MAVKA.</span></li>
          <li><span>Un accompagnement pour préparer, tester et faire évoluer votre activité ou votre projet, à votre rythme.</span></li>
          <li><span>La valorisation de l'expérience acquise au sein de MAVKA et des compétences développées au cours de votre parcours.</span></li>
          <li><span>Une équipe de bénévoles et d'intervenants aux parcours variés, avec qui échanger, partager des expériences et construire des projets.</span></li>
        </ul>
      </div>
      <div>
        <div class="head" style="margin-bottom:12px"><span class="eyebrow">Ce que nous attendons</span><h2>Une collaboration qui reste humaine</h2></div>
        <p class="lede" style="margin-bottom:18px">MAVKA encourage les bénévoles et les intervenants à participer à la vie de l'association : rencontres, événements, échanges ou projets communs.</p>
        <p class="lede" style="margin-bottom:18px">Chacun participe selon son rôle, ses disponibilités et le cadre de sa collaboration.</p>
        <details><summary>Est-ce un contrat de travail ?</summary><p>Non. La collaboration avec MAVKA repose sur un partenariat et une activité indépendante, avec des accords définis par écrit.</p></details>
        <details><summary>La participation aux événements est-elle obligatoire ?</summary><p>Oui, au moins une fois tous les trois mois. Cela peut être un forum, une fête ou un atelier gratuit.</p></details>
        <details><summary>Et si je ne suis pas disponible à un moment donné ?</summary><p>Le dialogue est toujours possible. La participation est organisée à l'avance en tenant compte des possibilités réelles de chacun.</p></details>
      </div>
    </div>
  </section>

  <section>
    <div class="wrap dir-grid">
      <div>
        <h3>Prêt à faire le premier pas ?</h3>
        <p style="margin-top:14px; color:var(--ink-2)">Nous ne cherchons pas seulement des intervenants. Nous accueillons aussi des personnes qui souhaitent partager un savoir-faire, tester une idée ou développer progressivement un projet avec MAVKA.</p>
        <div class="actions" style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px">
          <a class="btn btn-primary" href="#contact">Prendre rendez-vous</a>
        </div>
      </div>
      <div class="dir-art"><img src="/assets/site-img/mavka-meditation.webp?v=<?= @filemtime(__DIR__ . '/assets/site-img/mavka-meditation.webp') ?: time() ?>" alt="Mavka assise en tailleur, sereine, les yeux fermés"></div>
    </div>
  </section>
</div>

<!-- ============ COLLECTIVITES ============ -->
<div class="page" id="page-collectivites">
  <section class="hero">
    <div class="wrap grid">
      <div class="hero-copy">
        <span class="eyebrow">Communes et collectivités</span>
        <h1 style="font-size:clamp(2rem,4.4vw,3.4rem)">Un partenaire local qui commence petit et construit avec vous.</h1>
        <p class="lede">MAVKA ne propose pas un modèle figé. Nous commençons par une rencontre, testons un atelier pilote, puis ajustons avec vous selon les besoins réels de vos habitants.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#contact">Organiser un premier échange</a>
          <a class="btn btn-ghost" href="#docs">Consulter nos documents</a>
        </div>
      </div>
      <div class="hero-art"><img src="/assets/site-img/img-04-3b8dc488ec.webp" alt="Mavka accroupie, une loupe à la main, observant une pousse"></div>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="head"><span class="eyebrow">Notre approche</span><h2>Simple, progressif, sans lourdeur</h2></div>
      <div class="pillars">
        <div class="pillar"><h3>1. Une rencontre</h3><p>Présentation de l'association, identification des besoins locaux, réflexion sur un premier format d'action.</p></div>
        <div class="pillar"><h3>2. Un atelier pilote</h3><p>Format court (1 à 2 heures), groupe réduit, approche participative. Une salle communale ponctuelle suffit.</p></div>
        <div class="pillar"><h3>3. Une construction commune</h3><p>Échanges réguliers sur les besoins du territoire et ajustement progressif des actions, avec la commune.</p></div>
      </div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:20px"><span class="eyebrow">Nos axes d'action</span><h2>Trois choses que MAVKA apporte à un territoire</h2></div>
        <ul class="list">
          <li><span><b>Lien social et interculturel.</b> Des espaces de rencontre et de dialogue entre habitants, anciens et nouveaux.</span></li>
          <li><span><b>Apprentissage et transmission.</b> Valoriser des compétences qui existent déjà sur le territoire et encourager le partage de savoirs.</span></li>
          <li><span><b>Parcours et reconversion.</b> Un cadre pour que des professionnels en transition expérimentent, apprennent et évoluent.</span></li>
        </ul>
      </div>
      <div>
        <div class="head" style="margin-bottom:20px"><span class="eyebrow">Où nous en sommes</span><h2>Une association en phase de lancement</h2></div>
        <p class="lede">MAVKA est une association loi 1901 déclarée (RNA, JOAFE), à gouvernance collégiale (trois fondateurs), à but non lucratif. Nous constituons notre équipe d'intervenants et développons des formats pilotes à petite échelle, avec un suivi régulier des actions et une communication transparente avec nos partenaires. Nous sommes accompagnés par le dispositif Guid'Asso.</p>
      </div>
    </div>
  </section>

  <section id="docs">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Documents et cadre</span>
        <h2>Tout est en ordre, et consultable</h2>
        <p class="lede">Pour garantir un fonctionnement clair, voici les documents que nous mettons à disposition des collectivités.</p>
      </div>
      <ul class="docs">
        <li><a href="/assets/docs/statuts-mavka.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Statuts de l'association</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/reglement-interieur.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Règlement intérieur</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/recepisse-prefecture.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Récépissé de déclaration en préfecture</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/publication-joafe.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Publication au JOAFE</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/charte-benevolat.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Charte du bénévolat</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/contrat-cadre-prestation.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Contrat-cadre de prestation d'activités</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/projet-associatif.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Projet associatif</span><span class="st">PDF</span></a></li>
        <li><a href="/assets/docs/attestation-assurance-maif.pdf" target="_blank" rel="noopener"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Attestation d'assurance responsabilité civile (MAIF)</span><span class="st">PDF</span></a></li>
        <li><a href="#confidentialite"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Politique de protection des données (RGPD)</span><span class="st">Page</span></a></li>
        <li class="doc-soon"><svg class="doc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><span>Accompagnement Guid'Asso</span><span class="st soon">En cours</span></li>
      </ul>
    </div>
  </section>

  <section class="sand tight">
    <div class="wrap">
      <div class="head" style="margin-bottom:24px"><span class="eyebrow">Ils travaillent déjà avec nous</span><h2>Partenaires et communautés</h2></div>
      <div class="logos" style="gap:36px">
        <a href="https://www.garat.fr/" target="_blank" rel="noopener"><img src="/assets/site-img/img-08-3aa13012b1.webp" alt="Commune de Garat" style="height:44px"></a>
        <a href="https://soyaux.fr/" target="_blank" rel="noopener"><img src="/assets/site-img/img-09-0214dc1e39.webp" alt="Ville de Soyaux" style="height:44px"></a>
        <a href="https://cscsflep.com/" target="_blank" rel="noopener"><img src="/assets/site-img/img-10-0b0315c840.webp" alt="FLEP, centre socio-culturel et sportif de Soyaux" style="height:44px"></a>
        <a href="https://www.fcol16.org/" target="_blank" rel="noopener"><img src="/assets/site-img/img-11-6a6681c6fb.webp" alt="FCOL, fédération charentaise des œuvres laïques" style="height:44px"></a>
        <a href="https://www.facebook.com/people/Comit%C3%A9-des-F%C3%AAtes-et-danimations-de-Garat/61557262989400/" target="_blank" rel="noopener"><img src="/assets/site-img/img-23-3526f012ca.webp" alt="Comité des fêtes et d'animations de Garat" style="height:44px"></a>
        <a href="https://www.helloasso.com/associations/mavka" target="_blank" rel="noopener"><img src="/assets/site-img/img-24-18553ed39a.webp" alt="HelloAsso" style="height:32px"></a>
      </div>
    </div>
  </section>
</div>

<!-- ============ EQUIPE ============ -->
<div class="page" id="page-equipe">
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">L'équipe</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Les ateliers, ce sont d'abord des personnes.</h1>
        <p class="lede">Chaque personne apporte à MAVKA son expérience, ses compétences et son envie de transmettre. Découvrez les bénévoles et intervenants qui construisent progressivement les activités de l'association.</p>
      </div>
      <div class="team"><?php foreach ($site_intervenants as $iv) { echo render_person_card($iv); } ?></div>
    </div>
  </section>
  <section class="sand">
    <div class="wrap dir-grid">
      <div>
        <div class="head" style="margin-bottom:16px"><span class="eyebrow">Pourquoi MAVKA ?</span><h2>Un espace pour partager et faire évoluer ses compétences</h2></div>
        <p class="lede">MAVKA permet aux bénévoles et intervenants de participer à la vie associative, de partager leurs savoir-faire et d'expérimenter progressivement de nouvelles activités. Pour ceux qui souhaitent aller plus loin, le Parcours MAVKA accompagne le développement d'un projet dans un cadre structuré.</p>
        <div class="actions" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
          <a class="btn btn-primary" href="#intervenants">Découvrir le Parcours MAVKA →</a>
          <a class="btn btn-ghost" href="#contact">Prendre rendez-vous</a>
        </div>
      </div>
      <div class="dir-art"><img src="/assets/site-img/mavka-couronne.webp?v=<?= @filemtime(__DIR__ . '/assets/site-img/mavka-couronne.webp') ?: time() ?>" alt="Mavka assise, tressant une couronne de fleurs violettes"></div>
    </div>
  </section>
</div>

<!-- ============ CONTACT ============ -->
<div class="page" id="page-contact">
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Contact</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Une question, une idée, un projet ?</h1>
        <p class="lede">Pour vous informer sur un atelier, proposer une idée, rejoindre l'équipe ou construire un partenariat. Ou simplement échanger.</p>
      </div>
      <div class="contact">
        <div class="contact-info">
          <dl>
            <div><dt>Téléphone</dt><dd><a href="tel:+33656682153">+33 6 56 68 21 53</a></dd></div>
            <div><dt>Horaires</dt><dd>Lundi au samedi, 10h à 17h</dd></div>
            <div><dt>E-mail</dt><dd><a href="mailto:asso.mavka@gmail.com">asso.mavka@gmail.com</a></dd></div>
            <div><dt>Territoire</dt><dd>Sept communes autour d'Angoulême, Charente (16)</dd></div>
          </dl>
          <div class="social" aria-label="Réseaux sociaux">
            <a href="#" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H6v4h3v9h4v-9h3l1-4h-4V9c0-.6.4-1 1-1z"/></svg></a>
            <a href="#" aria-label="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
            <a href="#" aria-label="YouTube"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22 8.2c-.2-1.4-1-2.3-2.4-2.5C17.6 5.4 12 5.4 12 5.4s-5.6 0-7.6.3C3 5.9 2.2 6.8 2 8.2 1.7 10 1.7 12 1.7 12s0 2 .3 3.8c.2 1.4 1 2.3 2.4 2.5 2 .3 7.6.3 7.6.3s5.6 0 7.6-.3c1.4-.2 2.2-1.1 2.4-2.5.3-1.8.3-3.8.3-3.8s0-2-.3-3.8zM10 15V9l5.2 3L10 15z"/></svg></a>
          </div>
          <svg class="mascot tall" viewBox="0 0 310 769" style="aspect-ratio:310/769"><use href="#m-stand"/></svg>
        </div>
        <form id="contactForm">
          <label>Prénom et nom<input type="text" name="name" autocomplete="name" required></label>
          <label>E-mail<input type="email" name="email" autocomplete="email" required></label>
          <label>Je vous écris pour
            <select name="topic">
              <option>Participer à un atelier</option>
              <option>Proposer un atelier ou rejoindre l'équipe</option>
              <option>Un partenariat avec une commune</option>
              <option>Autre chose</option>
            </select>
          </label>
          <label>Message<textarea name="message" required></textarea></label>
          <input type="text" name="site_web" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px">
          <label class="consent"><input type="checkbox" required><span>J'accepte d'être contacté(e) par l'association MAVKA concernant ma demande.</span></label>
          <button class="btn btn-primary" type="submit">Envoyer</button>
          <p class="note" id="contactNote">Vos données sont utilisées uniquement pour répondre à votre demande et ne sont pas transmises à des tiers. Vous pouvez exercer vos droits d'accès, de rectification et de suppression en écrivant à asso.mavka@gmail.com.</p>
        </form>
      </div>
    </div>
  </section>
</div>

<!-- ============ CONFIDENTIALITE (RGPD) ============ -->
<div class="page" id="page-confidentialite">
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Vie privée</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Politique de protection des données personnelles (RGPD)</h1>
        <p class="lede">MAVKA accorde une grande importance au respect de la vie privée et à la protection des données personnelles. Cette politique explique quelles données peuvent être collectées, pourquoi elles sont utilisées, à qui elles peuvent être transmises, pendant combien de temps elles sont conservées et quels sont les droits des personnes concernées. Elle s'applique aux traitements réalisés dans le cadre des activités, projets, événements, préinscriptions, relations avec les bénévoles, intervenants, prestataires, partenaires et utilisateurs des services numériques de MAVKA, conformément au Règlement général sur la protection des données (RGPD) et à la loi française « Informatique et Libertés ».</p>
      </div>
      <div class="legal-doc">
        <h2>1. Responsable du traitement</h2>
        <p>Association MAVKA — association loi 1901, n° RNA W161010493.<br>
        Siège social : 10 lotissement Les Hauts de Neuillac, 16410 Garat<br>
        E-mail : <a href="mailto:contact@mavka16.fr">contact@mavka16.fr</a><br>
        Site internet : <a href="https://mavka16.fr">https://mavka16.fr</a><br>
        Représentante légale : Larysa MAS, présidente.</p>
        <p>Pour toute question concernant la protection des données personnelles ou l'exercice de vos droits, vous pouvez contacter MAVKA à l'adresse <a href="mailto:contact@mavka16.fr">contact@mavka16.fr</a>.</p>

        <h2>2. Données susceptibles d'être collectées</h2>
        <p>Les données collectées dépendent de votre relation avec MAVKA et de l'activité concernée. MAVKA applique le principe de minimisation et cherche à ne collecter que les informations nécessaires à la finalité poursuivie.</p>

        <h3>2.1 Identification et contact</h3>
        <ul><li>nom et prénom ;</li><li>adresse e-mail ;</li><li>numéro de téléphone ;</li><li>informations nécessaires à l'identification d'une personne lorsque cette identification est nécessaire.</li></ul>

        <h3>2.2 Préinscriptions, inscriptions et participation</h3>
        <ul>
          <li>activité ou événement concerné ;</li>
          <li>préinscription ou inscription ;</li>
          <li>disponibilités ;</li>
          <li>âge ou tranche d'âge lorsque cette information est nécessaire ;</li>
          <li>informations relatives au représentant légal pour un mineur ;</li>
          <li>historique utile à l'organisation et au suivi de la participation ;</li>
          <li>informations pratiques strictement nécessaires à l'activité.</li>
        </ul>
        <p>Une préinscription ne signifie pas nécessairement qu'une activité sera organisée.</p>

        <h3>2.3 Bénévoles et intervenants</h3>
        <ul>
          <li>coordonnées ;</li>
          <li>présentation et parcours ;</li>
          <li>expériences, compétences et savoir-faire ;</li>
          <li>activités proposées ou réalisées ;</li>
          <li>disponibilités ;</li>
          <li>informations relatives aux projets développés avec MAVKA ;</li>
          <li>échanges avec l'association ;</li>
          <li>documents nécessaires à certaines missions.</li>
        </ul>
        <p>Certaines informations peuvent être présentées publiquement avec l'accord de la personne concernée. D'autres restent internes à MAVKA.</p>

        <h3>2.4 Qualifications et justificatifs</h3>
        <p>Lorsque la nature d'une activité ou d'une mission le nécessite, MAVKA peut traiter des informations relatives aux diplômes, certifications, qualifications, formations, autorisations professionnelles, assurances ou autres justificatifs nécessaires à la mission. La déclaration d'une qualification et sa vérification par MAVKA sont distinguées. Les justificatifs sont accessibles uniquement aux personnes qui en ont besoin dans le cadre de leurs responsabilités ; lorsque la conservation d'une copie n'est pas nécessaire, MAVKA peut se limiter à enregistrer le résultat de la vérification, sa date et la personne qui l'a effectuée.</p>

        <h3>2.5 Prestataires professionnels</h3>
        <p>Dans le cadre des relations avec les prestataires indépendants, MAVKA peut notamment traiter : identité et coordonnées professionnelles, SIREN/SIRET, informations relatives à l'activité professionnelle, coordonnées bancaires nécessaires au paiement, factures et informations comptables, assurances, qualifications ou autorisations nécessaires, Fiches de mission, contrats et documents administratifs nécessaires à la relation professionnelle.</p>

        <h3>2.6 Données relatives à la sécurité et à la santé</h3>
        <p>Dans certaines activités, une information particulière peut être nécessaire pour assurer la sécurité de la personne (par exemple une allergie ou une précaution importante). Ces informations peuvent constituer des données concernant la santé et bénéficient d'une protection renforcée : MAVKA ne les demande que lorsqu'elles sont réellement nécessaires à l'activité concernée et en limite l'accès aux personnes qui doivent en avoir connaissance. Lorsque le traitement repose sur le consentement de la personne concernée ou de son représentant légal, ce consentement est recueilli de manière spécifique et peut être retiré dans les conditions prévues par le RGPD. MAVKA ne constitue pas de dossier médical.</p>

        <h3>2.7 Espace bénévole et outils numériques</h3>
        <p>Lorsqu'un espace personnel ou un outil numérique est mis à disposition, MAVKA peut traiter notamment : les informations du profil, les activités proposées ou associées à la personne, les confirmations de participation, les informations nécessaires à l'organisation des activités, les droits et niveaux d'accès, les informations nécessaires à la sécurité et au fonctionnement des comptes, les documents administratifs transmis, et certaines informations internes relatives au développement d'une activité ou d'un projet. Les informations identifiées comme internes ou privées ne sont pas publiées sur les supports publics de MAVKA.</p>

        <h3>2.8 Communication et demandes de contact</h3>
        <ul><li>messages transmis par les formulaires ;</li><li>échanges par e-mail ;</li><li>demandes adressées à l'association ;</li><li>informations nécessaires au suivi de ces échanges.</li></ul>

        <h3>2.9 Photographies et vidéos</h3>
        <p>Des photographies ou vidéos peuvent être réalisées lors de certaines activités ou événements. L'utilisation d'images permettant d'identifier une personne est réalisée conformément aux règles applicables, à la présente politique et, lorsque nécessaire, à une Autorisation de droit à l'image distincte. Les supports autorisés peuvent notamment concerner le site de MAVKA, ses réseaux sociaux, ses supports imprimés, ses documents destinés aux partenaires ou financeurs et, lorsque la personne l'a expressément autorisé, la presse et les médias.</p>

        <h2>3. Mineurs</h2>
        <p>Certaines activités de MAVKA peuvent accueillir des enfants ou adolescents. Les données concernant un mineur sont limitées aux informations nécessaires à son inscription, à sa participation, à sa sécurité et à l'organisation de l'activité. Les informations nécessaires concernant le parent ou représentant légal peuvent également être collectées ; lorsque le consentement du représentant légal est nécessaire, MAVKA le recueille dans les conditions applicables. Une attention particulière est portée à la protection des données et des images des mineurs.</p>

        <h2>4. Pourquoi MAVKA utilise-t-elle ces données ?</h2>
        <table class="legal-table">
          <thead><tr><th>Finalité</th><th>Base juridique principale</th></tr></thead>
          <tbody>
            <tr><td>Répondre à une demande de contact</td><td>Mesures précontractuelles ou intérêt légitime selon la nature de la demande</td></tr>
            <tr><td>Gérer une préinscription ou une inscription</td><td>Exécution de mesures prises à la demande de la personne ou exécution de la relation concernée</td></tr>
            <tr><td>Organiser et suivre une activité</td><td>Exécution de la relation concernée et/ou intérêt légitime de MAVKA à organiser ses activités</td></tr>
            <tr><td>Communiquer les informations pratiques relatives à une activité</td><td>Exécution de la relation concernée</td></tr>
            <tr><td>Gérer les relations avec les bénévoles et intervenants</td><td>Intérêt légitime de MAVKA à organiser ses activités et engagements associatifs</td></tr>
            <tr><td>Gérer les profils, accès et espaces personnels</td><td>Intérêt légitime de MAVKA à organiser et sécuriser ses outils et activités</td></tr>
            <tr><td>Vérifier une qualification, une assurance ou une autorisation</td><td>Obligation légale lorsqu'elle existe, ou intérêt légitime lorsqu'une vérification est nécessaire et proportionnée</td></tr>
            <tr><td>Gérer les prestataires, contrats, missions, factures et paiements</td><td>Exécution du contrat et obligations légales</td></tr>
            <tr><td>Respecter les obligations comptables, fiscales ou administratives</td><td>Obligation légale</td></tr>
            <tr><td>Utiliser une photographie ou vidéo lorsque le consentement est requis</td><td>Consentement</td></tr>
            <tr><td>Traiter certaines données sensibles lorsque le consentement constitue la base appropriée</td><td>Consentement explicite</td></tr>
            <tr><td>Produire des statistiques pour comprendre les besoins et développer les activités</td><td>Intérêt légitime — résultats agrégés ou anonymisés lorsque cela est possible</td></tr>
          </tbody>
        </table>
        <p>Le choix de la base juridique dépend du traitement concerné : le consentement n'est donc pas utilisé automatiquement pour tous les traitements. MAVKA ne vend ni ne loue les données personnelles à des tiers à des fins commerciales.</p>

        <h2>5. Durées de conservation</h2>
        <p>Les données ne sont pas conservées indéfiniment. Elles sont conservées pendant la durée nécessaire à la finalité pour laquelle elles ont été collectées, puis supprimées ou, lorsque cela est nécessaire, archivées pendant la durée permettant à MAVKA de respecter ses obligations légales ou de défendre ses droits. À titre indicatif :</p>
        <table class="legal-table">
          <thead><tr><th>Type de données</th><th>Durée ou critère de conservation</th></tr></thead>
          <tbody>
            <tr><td>Demandes de contact</td><td>Jusqu'à 2 ans après le dernier échange, sauf nécessité particulière</td></tr>
            <tr><td>Préinscriptions qui ne donnent pas lieu à une activité</td><td>Jusqu'à la clôture ou à l'abandon de l'activité concernée, puis suppression dans un délai raisonnable</td></tr>
            <tr><td>Données nécessaires à la participation à une activité</td><td>Pendant la durée nécessaire à l'organisation et au suivi de l'activité, puis conservation limitée lorsqu'elle reste justifiée</td></tr>
            <tr><td>Données des bénévoles et intervenants actifs</td><td>Pendant la durée de la collaboration, puis conservation limitée des éléments nécessaires au suivi ou à la défense des droits de MAVKA</td></tr>
            <tr><td>Profil public d'un bénévole ou intervenant</td><td>Pendant la collaboration, ou jusqu'à sa dépublication lorsqu'elle intervient plus tôt</td></tr>
            <tr><td>Données internes relatives à un projet personnel</td><td>Pendant la durée de l'accompagnement, puis suppression lorsqu'elles ne sont plus nécessaires</td></tr>
            <tr><td>Qualifications et justificatifs</td><td>Pendant la durée nécessaire à leur vérification et au suivi de l'activité concernée ; une trace de la vérification peut être conservée lorsque cela est justifié</td></tr>
            <tr><td>Données des prestataires et contrats</td><td>Pendant la relation contractuelle, puis selon les durées légales ou les besoins de preuve applicables</td></tr>
            <tr><td>Factures et documents comptables</td><td>Pendant la durée légale applicable</td></tr>
            <tr><td>Données particulières de sécurité ou de santé</td><td>Pendant la durée strictement nécessaire à l'activité concernée, sauf obligation particulière</td></tr>
            <tr><td>Données relatives aux accès numériques</td><td>Pendant la durée de l'accès, puis pendant une durée limitée lorsqu'une trace reste nécessaire pour la sécurité</td></tr>
            <tr><td>Photographies et vidéos</td><td>Selon la durée et les supports prévus par l'autorisation applicable, et sous réserve du retrait du consentement lorsqu'il constitue la base du traitement</td></tr>
          </tbody>
        </table>

        <h2>6. Destinataires des données</h2>
        <p>Les données peuvent être accessibles, selon les besoins :</p>
        <ul>
          <li>aux dirigeants ou personnes habilitées de MAVKA ;</li>
          <li>aux bénévoles ou intervenants concernés par une activité, uniquement pour les informations nécessaires à leur mission ;</li>
          <li>aux prestataires professionnels concernés ;</li>
          <li>aux partenaires lorsque la transmission est nécessaire à une activité ou à un projet et qu'elle repose sur une base juridique appropriée ;</li>
          <li>aux prestataires techniques utilisés par MAVKA pour l'hébergement, les formulaires, les inscriptions, les paiements, les communications ou d'autres services nécessaires ;</li>
          <li>aux administrations, autorités ou organismes lorsqu'une obligation légale impose cette transmission.</li>
        </ul>
        <p>Les droits d'accès sont adaptés aux fonctions et aux besoins des personnes concernées. MAVKA ne donne pas un accès général à l'ensemble de ses données à tous ses bénévoles ou intervenants.</p>

        <h2>7. Prestataires techniques et transferts hors Union européenne</h2>
        <p>MAVKA peut utiliser des services fournis par des prestataires techniques pour l'hébergement, les communications, les formulaires, les paiements ou d'autres fonctions nécessaires à ses activités. Certains de ces services peuvent entraîner un traitement de données en dehors de l'Union européenne ou de l'Espace économique européen. Lorsque cela est le cas, MAVKA veille à ce que le transfert repose sur un mécanisme prévu par le RGPD, notamment une décision d'adéquation ou des garanties appropriées lorsque celles-ci sont nécessaires. Les informations sur les transferts applicables peuvent être demandées à MAVKA à l'adresse <a href="mailto:contact@mavka16.fr">contact@mavka16.fr</a>.</p>

        <h2>8. Vos droits</h2>
        <p>Selon la base juridique et la nature du traitement, vous pouvez disposer notamment des droits suivants :</p>
        <ul>
          <li>droit d'accès à vos données ;</li>
          <li>droit de rectification ;</li>
          <li>droit à l'effacement lorsque les conditions sont réunies ;</li>
          <li>droit à la limitation du traitement ;</li>
          <li>droit d'opposition lorsque ce droit est applicable ;</li>
          <li>droit à la portabilité lorsque les conditions sont réunies ;</li>
          <li>droit de retirer votre consentement à tout moment lorsque le traitement repose sur celui-ci.</li>
        </ul>
        <p>Le retrait du consentement ne remet pas en cause la licéité des traitements réalisés avant son retrait. Pour exercer vos droits : <a href="mailto:contact@mavka16.fr">contact@mavka16.fr</a>. MAVKA peut demander les informations raisonnablement nécessaires pour vérifier l'identité de la personne lorsque cela est nécessaire, et répond aux demandes dans les délais prévus par le RGPD (en principe un mois).</p>
        <p>Si vous estimez, après avoir contacté MAVKA, que vos droits ne sont pas respectés, vous pouvez introduire une réclamation auprès de la Commission nationale de l'informatique et des libertés (CNIL) : <a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a></p>

        <h2>9. Sécurité et confidentialité</h2>
        <p>MAVKA met en place des mesures techniques et organisationnelles adaptées à ses moyens, à la nature des données et aux risques identifiés, pouvant notamment comprendre : des accès personnels, des droits d'accès adaptés aux responsabilités, l'utilisation de mots de passe, la limitation de l'accès aux documents sensibles, la suppression ou la désactivation des accès devenus inutiles, la protection des outils utilisés, la sauvegarde des informations nécessaires, la sensibilisation des personnes ayant accès aux données, et le recours à des prestataires présentant des garanties appropriées.</p>
        <p>Les bénévoles, intervenants, dirigeants et prestataires ayant accès à des données personnelles doivent respecter leur confidentialité et ne les utiliser que dans le cadre de leurs missions.</p>

        <h2>10. Cookies et autres traceurs</h2>
        <p>Le site de MAVKA peut utiliser des cookies ou autres traceurs. Les traceurs strictement nécessaires au fonctionnement du site peuvent être utilisés sans consentement lorsqu'ils remplissent les conditions prévues par la réglementation. Les traceurs nécessitant un consentement ne sont utilisés qu'après le choix de l'utilisateur : lorsque cela est nécessaire, un outil de gestion des préférences permet d'accepter, de refuser ou de modifier les choix concernant les traceurs. Les informations détaillées relatives aux cookies et traceurs effectivement utilisés peuvent être précisées dans une politique ou un module d'information dédié sur le site.</p>

        <h2>11. Évolution de la présente politique</h2>
        <p>La présente politique peut évoluer afin de tenir compte des évolutions des activités de MAVKA, des outils utilisés, des obligations légales ou réglementaires, et des recommandations des autorités compétentes. Lorsqu'une modification importante concerne la manière dont les données sont utilisées, les personnes concernées sont informées lorsque cela est nécessaire. La version en vigueur est disponible sur le site officiel de MAVKA : <a href="https://mavka16.fr">https://mavka16.fr</a></p>
        <p class="note" style="margin-top:20px">Date de mise à jour : 28 septembre 2026</p>
      </div>
    </div>
  </section>
</div>

</main>

<footer>
  <div class="wrap">
    <div class="row">
      <div>
        <a class="brand" href="#accueil"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""><b>MAVKA</b></a>
        <p style="margin-top:12px;max-width:26em">Notre mascotte : Mavka, esprit protecteur de la forêt dans le folklore ukrainien.</p>
      </div>
      <div class="cols">
        <div class="col"><b>Découvrir</b><a href="#agenda">Agenda</a><a href="#activites">Activités</a><a href="#equipe">L'équipe</a></div>
        <div class="col"><b>Rejoindre</b><a href="#intervenants">Devenir intervenant</a><a href="#collectivites">Collectivités</a><a href="#contact">Contact</a><a href="/admin/login.php">Espace bénévole</a></div>
        <div class="col"><b>Nous joindre</b><a href="tel:+33656682153">+33 6 56 68 21 53</a><a href="mailto:asso.mavka@gmail.com">asso.mavka@gmail.com</a><span>Lundi au samedi, 10h à 17h</span></div>
      </div>
    </div>
    <div class="legal">
      <span>© 2026 MAVKA · mavka16.fr</span>
      <a href="#confidentialite">Mentions légales et protection des données</a>
    </div>
  </div>
</footer>

<script>
(function(){
  const routes=['accueil','agenda','activites','intervenants','collectivites','equipe','contact','confidentialite'];
  const alias={ateliers:'agenda'};
  const nav=document.getElementById('nav'), btn=document.getElementById('menuBtn');
  const titles={accueil:'MAVKA',agenda:'Agenda · MAVKA',activites:'Activités · MAVKA',intervenants:'Devenir intervenant · MAVKA',collectivites:'Collectivités · MAVKA',equipe:"L'équipe · MAVKA",contact:'Contact · MAVKA',confidentialite:'Confidentialité (RGPD) · MAVKA'};
  function go(target){
    let h=alias[target]||target||'accueil', sub=null;
    if(!routes.includes(h)){
      const el=document.getElementById(h); const page=el&&el.closest('.page');
      if(page){h=page.id.replace('page-','');sub=el;} else h='accueil';
    }
    routes.forEach(r=>document.getElementById('page-'+r).classList.toggle('active',r===h));
    document.querySelectorAll('nav a').forEach(a=>{ if(a.dataset.route===h)a.setAttribute('aria-current','page'); else a.removeAttribute('aria-current'); });
    nav.classList.remove('open'); btn.setAttribute('aria-expanded','false');
    document.title=titles[h]||'MAVKA';
    try{history.replaceState(null,'','#'+target);}catch(e){}
    if(sub){ sub.scrollIntoView({block:'start'}); } else window.scrollTo(0,0);
  }
  document.addEventListener('click',e=>{
    const a=e.target.closest('a[href^="#"]'); if(!a) return;
    e.preventDefault(); go(a.getAttribute('href').slice(1));
  });
  window.addEventListener('hashchange',()=>go(location.hash.slice(1)));
  go((location.hash||'#accueil').slice(1));
  btn.addEventListener('click',()=>{const o=nav.classList.toggle('open');btn.setAttribute('aria-expanded',String(o));});
})();
// ---- FR / UK switch ----
(function(){
  const UK={"🔍 Mode aperçu — vous voyez aussi les brouillons et les activités pas encore finalisées. Le public ne les voit pas encore.": "🔍 Режим попереднього перегляду — ви також бачите чернетки та ще не завершені активності. Загал їх поки не бачить.", "Navigation principale": "Головна навігація", "Ateliers": "Майстерні", "Devenir intervenant": "Стати волонтером", "Collectivités": "Громади", "L'équipe": "Команда", "Contact": "Контакти", "Voir les ateliers": "Переглянути майстерні", "Menu": "Меню", "Association loi 1901 · Charente": "Асоціація за законом 1901 року · Шаранта", "Des ateliers près de chez vous, portés par des gens qui savent faire.": "Майстерні поруч із вами, які проводять люди, що справді вміють це робити.", "MAVKA aide des personnes talentueuses, souvent nouvellement arrivées en France, à transmettre ce qu'elles savent : musique, arts, langues, bien-être. Dans sept communes autour d'Angoulême.": "MAVKA допомагає талановитим людям, часто нещодавно прибулим до Франції, ділитися тим, що вони вміють: музика, мистецтво, мови, добробут. У семи громадах навколо Ангулема.", "Voir les prochains ateliers": "Переглянути найближчі майстерні", "Proposer un atelier": "Запропонувати майстерню", "Mavka, la mascotte de l'association, saluant de la main": "Мавка, маскот асоціації, махає рукою", "7 communes": "7 громад", "11 intervenants bénévoles": "11 волонтерів-викладачів", "Association déclarée (RNA, JOAFE)": "Офіційно зареєстрована асоціація (RNA, JOAFE)", "Assurée MAIF": "Застрахована MAIF", "Partenaires": "Партнери", "Commune de Garat": "Громада Гара", "Ville de Soyaux": "Місто Суайо", "Par où commencer": "З чого почати", "MAVKA s'adresse à trois types de personnes. Laquelle êtes-vous ?": "MAVKA звертається до трьох типів людей. Хто з них ви?", "Habitant": "Мешканець", "Je veux participer à un atelier": "Я хочу відвідати майстерню", "Musique, peinture, calligraphie, gymnastique douce, art-thérapie. Des groupes à taille humaine, près de chez vous, souvent gratuits sur préinscription.": "Музика, живопис, каліграфія, м'яка гімнастика, арттерапія. Невеликі групи поруч із вами, часто безкоштовні за попередньою реєстрацією.", "Voir les ateliers →": "Переглянути майстерні →", "Enseignant, artiste, praticien": "Викладач, митець, практик", "Je veux transmettre ce que je sais": "Я хочу передавати свої знання", "Vous avez un savoir-faire et vous cherchez un cadre pour le partager. Le Parcours MAVKA vous accompagne en quatre étapes, de la première rencontre à un cours régulier.": "У вас є навички, і ви шукаєте простір, щоб ними ділитися. Шлях MAVKA супроводжує вас у чотири етапи — від першої зустрічі до регулярних занять.", "Découvrir le Parcours →": "Дізнатися про Шлях MAVKA →", "Commune, collectivité": "Громада, орган влади", "Je représente une collectivité": "Я представляю громаду", "Un partenaire simple et progressif : une rencontre, un atelier pilote, puis une construction commune adaptée aux besoins de vos habitants.": "Простий і поступовий партнер: зустріч, пілотна майстерня, а далі — спільна робота, що враховує потреби ваших мешканців.", "Travailler avec MAVKA →": "Співпрацювати з MAVKA →", "Agenda": "Розклад", "Prochains rendez-vous": "Найближчі події", "La préinscription est gratuite et sans engagement. Elle nous permet de savoir combien de personnes attendre.": "Попередня реєстрація безкоштовна і ні до чого не зобов'язує. Вона дозволяє нам знати, скільки людей очікувати.", "oct": "жов", "sept": "вер", "18h00": "18:00", "14h00": "14:00", "Bien-être · Art-thérapie": "Добробут · Арттерапія", "Art-Thérapie Évolutive, séance d'initiation": "Еволюційна арттерапія — вступне заняття", "William Aubert · Salle de temps libre, 16410 Garat": "Вільям Обер · Salle de temps libre, 16410 Гара", "Une première expérience créative ouverte à tous, dans un cadre bienveillant. Aucun talent artistique n'est nécessaire.": "Перший творчий досвід, відкритий для всіх, у доброзичливій атмосфері. Жодних художніх навичок не потрібно.", "Préinscription gratuite": "Безкоштовна попередня реєстрація", "Événement · Tout public": "Подія · Для всіх", "Festival du jeu de Garat": "Фестиваль настільних ігор у Гара", "Salle de l'Atrium, 16410 Garat · Entrée libre": "Salle de l'Atrium, 16410 Гара · Вхід вільний", "Jeux de société, jeux géants en extérieur, espace tout-petits, jeux vidéo et démonstrations d'impression 3D. Un « passeport des aventuriers » pour les plus audacieux, et une buvette.": "Настільні ігри, гігантські ігри на вулиці, зона для малюків, відеоігри та демонстрації 3D-друку. «Паспорт мандрівника» для найсміливіших і буфет.", "En savoir plus": "Дізнатися більше", "jeu. 9h30": "чт. 9:30", "hebdo": "щотижня", "Bien-être · Gymnastique": "Добробут · Гімнастика", "Gymnastique articulaire, le jeudi matin": "Суглобова гімнастика щочетверга вранці", "Hanna Sokha · Salle de l'Atrium, 16410 Garat": "Ганна Соха · Salle de l'Atrium, 16410 Гара", "Intéressé(e) ? Cette préinscription gratuite nous permet de compter les personnes motivées et d'étudier avec la mairie la mise en place des séances.": "Зацікавились? Ця безкоштовна попередня реєстрація дозволяє нам порахувати зацікавлених і разом із мерією вирішити питання проведення занять.", "Je suis intéressé(e)": "Мене це цікавить", "Régulier": "Регулярно", "Grand Angoulême": "Великий Ангулем", "Éducation · Musique": "Освіта · Музика", "Violon, piano, chant et formation musicale": "Скрипка, фортепіано, спів і музична теорія", "Snizhana Zhuravlova · enfants et adultes": "Сніжана Журавльова · діти та дорослі", "Apprendre, reprendre un instrument ou jouer avec d'autres. Débutants ou musiciens confirmés.": "Навчитися грати, повернутися до інструмента або грати разом з іншими. Для початківців і досвідчених музикантів.", "Voir sa page": "Переглянути її сторінку", "Voir tous les ateliers et les activités régulières →": "Переглянути всі майстерні та регулярні заняття →", "Ce que nous proposons": "Що ми пропонуємо", "Activités": "Активності", "Culture": "Культура", "Peinture décorative ukrainienne de Petrykivka, calligraphie chinoise, origami, couronnes et objets faits main. Créer de ses mains et découvrir une tradition venue d'ailleurs.": "Петриківський розпис, китайська каліграфія, орігамі, вінки та вироби ручної роботи. Створювати власними руками й відкривати для себе традиції з інших країн.", "Éducation": "Освіта", "Musique (violon, piano, chant, formation musicale) avec trois intervenantes. Bientôt : anglais du quotidien et impression 3D.": "Музика (скрипка, фортепіано, спів, музична теорія) із трьома викладачками. Незабаром: розмовна англійська та 3D-друк.", "Bien-être": "Добробут", "Gymnastique douce, yoga, mobilité, gestion du stress, espaces de parole. Des outils simples pour prendre soin de soi, en prévention, sans remplacer un suivi médical.": "М'яка гімнастика, йога, розвиток рухливості, управління стресом, простори для розмов. Прості інструменти турботи про себе — для профілактики, а не заміни медичного супроводу.", "Initiatives": "Ініціативи", "Tester une idée, un pas à la fois": "Перевірити ідею, крок за кроком", "Le Parcours MAVKA et l'art-thérapie : chacun construit son propre projet, accompagné pas à pas, de la découverte à l'autonomie.": "Шлях MAVKA та арттерапія: кожен будує свій власний проєкт, крок за кроком — від знайомства до самостійності.", "Mavka assise, en train de lire": "Мавка сидить і читає", "Portrait de Mavka": "Портрет Мавки", "Pourquoi « Mavka » ?": "Чому «Мавка»?", "Dans le folklore ukrainien, la Mavka est l'esprit protecteur de la forêt.": "В українському фольклорі мавка — дух-охоронець лісу.", "Nous avons choisi ce nom parce que MAVKA est née de l'expérience de personnes immigrées, et parce qu'une forêt est un bon modèle : chacun y pousse à son rythme, et tout le monde s'y entraide. La petite pousse sur sa tête, c'est vous.": "Ми обрали цю назву, бо MAVKA народилася з досвіду людей-мігрантів, а ліс — гарна модель: кожен росте у своєму темпі, і всі допомагають одне одному. Маленький паросток на її голові — це ви.", "Onze bénévoles, onze savoir-faire": "Одинадцять волонтерів, одинадцять умінь", "Musiciennes, peintres, calligraphe, professeur de gymnastique, art-thérapeute. Des personnes qui ont une pratique réelle et l'envie de la partager.": "Музикантки, художниці, каліграф, викладач гімнастики, арттерапевт. Люди з реальним досвідом і бажанням ним ділитися.", "Rencontrer l'équipe": "Познайомитися з командою", "Une question, une idée, un projet ?": "Питання, ідея, проєкт?", "Nous répondons du lundi au samedi, de 10h à 17h. Un appel ou un message suffit pour commencer.": "Ми відповідаємо з понеділка по суботу, з 10:00 до 17:00. Досить одного дзвінка чи повідомлення, щоб почати.", "Nous écrire": "Написати нам", "Ateliers et événements": "Майстерні та події", "Des ateliers à taille humaine, près de chez vous": "Невеликі майстерні поруч із вами", "Aucun niveau requis. La plupart des premières séances sont gratuites sur préinscription. Les activités régulières sont ensuite fixées avec l'intervenant et la commune.": "Жодного рівня підготовки не потрібно. Більшість перших занять безкоштовні за попередньою реєстрацією. Регулярні заняття потім узгоджуються з викладачем і громадою.", "Les activités se construisent avec vous": "Активності будуються разом з вами", "Et aussi...": "І також...", "Ce qui existe déjà, ce qui arrive bientôt": "Що вже є, що скоро з'явиться", "Violon, piano, chant, formation musicale": "Скрипка, фортепіано, спів, музична теорія", "Snizhana Zhuravlova · enfants et adultes, débutants ou confirmés": "Сніжана Журавльова · діти та дорослі, початківці й досвідчені", "Apprendre, reprendre un instrument ou jouer avec d'autres. Cours individuels et musique d'ensemble.": "Навчитися грати, повернутися до інструмента або грати з іншими. Індивідуальні заняття та ансамблева гра.", "Voir sa page et se préinscrire": "Переглянути її сторінку та зареєструватися", "Sur demande": "За запитом", "Peinture de Petrykivka, calligraphie chinoise, créations artisanales": "Петриківський розпис, китайська каліграфія, вироби ручної роботи", "Des ateliers d'une à deux heures pour créer de ses mains et découvrir une tradition venue d'ailleurs.": "Майстерні на одну-дві години, щоб створювати власними руками й відкривати традиції з інших країн.", "Demander une date": "Запросити дату", "Yoga, gymnastique douce, mobilité, gestion du stress": "Йога, м'яка гімнастика, розвиток рухливості, управління стресом", "Hanna Sokha et l'équipe Bien-être": "Ганна Соха та команда «Добробут»", "Des outils simples pour prendre soin de soi, dans un cadre bienveillant. Prévention et bien-être, sans remplacer un suivi médical.": "Прості інструменти турботи про себе в доброзичливій атмосфері. Профілактика та добробут, без заміни медичного супроводу.", "Bientôt": "Скоро", "En préparation": "У підготовці", "Anglais du quotidien, impression 3D": "Розмовна англійська, 3D-друк", "William Aubert et de nouveaux intervenants": "Вільям Обер та нові волонтери-викладачі", "Deux formats en construction. Laissez vos coordonnées pour être prévenu au lancement.": "Два формати в розробці. Залиште свої контакти, щоб дізнатися про запуск першими.", "Être prévenu": "Повідомити мене", "Comment ça marche": "Як це працює", "Trois choses à savoir avant de venir": "Три речі, які варто знати перед візитом", "La préinscription est gratuite.": "Попередня реєстрація безкоштовна.", "Elle se fait en un clic sur HelloAsso et ne vous engage à rien. Elle nous aide à prévoir la salle et le matériel.": "Вона займає один клік на HelloAsso і ні до чого не зобов'язує. Вона допомагає нам підготувати приміщення й матеріали.", "Aucun talent particulier n'est nécessaire.": "Жодних особливих талантів не потрібно.", "Les ateliers sont pensés pour des débutants. Enfants et adultes sont les bienvenus, sauf mention contraire.": "Майстерні розраховані на початківців. Діти та дорослі вітаються, якщо не вказано інше.", "Les séances ont lieu dans des salles communales.": "Заняття проходять у громадських приміщеннях.", "Garat, Soyaux et Grand Angoulême aujourd'hui, d'autres communes au fur et à mesure.": "Сьогодні це Гара, Суайо та Великий Ангулем, з часом додаватимуться інші громади.", "Le Parcours MAVKA": "Шлях MAVKA", "Le Parcours s'adresse aux enseignants, artistes, musiciens, professeurs de yoga et praticiens qui traversent une transition professionnelle, souvent après une arrivée en France. Quatre étapes, à votre rythme, dans un cadre sécurisant.": "Шлях MAVKA створений для викладачів, митців, музикантів, вчителів йоги та практиків, які переживають професійний перехід — часто після переїзду до Франції. Чотири етапи у вашому темпі, у безпечній атмосфері.", "Demander une première rencontre": "Запросити першу зустріч", "Voir les quatre étapes": "Переглянути чотири етапи", "Mavka pointant vers le haut": "Мавка вказує вгору", "Engagement": "Залучення", "Collaboration durable": "Стала співпраця", "Ce que MAVKA apporte": "Що дає MAVKA", "Un tremplin, pas un contrat de travail": "Трамплін, а не трудовий договір", "Ce que nous attendons": "Що ми очікуємо", "Prêt à faire le premier pas ?": "Готові зробити перший крок?", "Prendre rendez-vous": "Призначити зустріч", "Communes et collectivités": "Громади та органи влади", "Un partenaire local qui commence petit et construit avec vous.": "Місцевий партнер, який починає з малого й розвивається разом із вами.", "MAVKA ne propose pas un modèle figé. Nous commençons par une rencontre, testons un atelier pilote, puis ajustons avec vous selon les besoins réels de vos habitants.": "MAVKA не пропонує застиглу модель. Ми починаємо зі зустрічі, тестуємо пілотну майстерню, а потім адаптуємо підхід разом із вами, враховуючи реальні потреби мешканців.", "Organiser un premier échange": "Організувати першу зустріч", "Consulter nos documents": "Переглянути наші документи", "Mavka accroupie, une loupe à la main, observant une pousse": "Мавка присіла з лупою в руці, розглядаючи паросток", "Notre approche": "Наш підхід", "Simple, progressif, sans lourdeur": "Простий, поступовий, без зайвої бюрократії", "1. Une rencontre": "1. Зустріч", "Présentation de l'association, identification des besoins locaux, réflexion sur un premier format d'action.": "Презентація асоціації, визначення місцевих потреб, обговорення першого формату дій.", "2. Un atelier pilote": "2. Пілотна майстерня", "Format court (1 à 2 heures), groupe réduit, approche participative. Une salle communale ponctuelle suffit.": "Короткий формат (1–2 години), невелика група, партисипативний підхід. Достатньо одноразово наданого громадського приміщення.", "3. Une construction commune": "3. Спільна розбудова", "Échanges réguliers sur les besoins du territoire et ajustement progressif des actions, avec la commune.": "Регулярний обмін думками про потреби території та поступове коригування дій разом із громадою.", "Nos axes d'action": "Наші напрями діяльності", "Trois choses que MAVKA apporte à un territoire": "Три речі, які MAVKA дає території", "Lien social et interculturel.": "Соціальні та міжкультурні зв'язки.", "Des espaces de rencontre et de dialogue entre habitants, anciens et nouveaux.": "Простори для зустрічей і діалогу між мешканцями — давніми й новими.", "Apprentissage et transmission.": "Навчання та передавання знань.", "Valoriser des compétences qui existent déjà sur le territoire et encourager le partage de savoirs.": "Цінувати навички, які вже є на території, і заохочувати обмін знаннями.", "Parcours et reconversion.": "Професійний шлях і перекваліфікація.", "Un cadre pour que des professionnels en transition expérimentent, apprennent et évoluent.": "Простір, у якому фахівці в процесі переходу можуть пробувати, вчитися й розвиватися.", "Où nous en sommes": "Де ми зараз", "Une association en phase de lancement": "Асоціація на етапі запуску", "MAVKA est une association loi 1901 déclarée (RNA, JOAFE), à gouvernance collégiale (trois fondateurs), à but non lucratif. Nous constituons notre équipe d'intervenants et développons des formats pilotes à petite échelle, avec un suivi régulier des actions et une communication transparente avec nos partenaires. Nous sommes accompagnés par le dispositif Guid'Asso.": "MAVKA — зареєстрована асоціація за законом 1901 року (RNA, JOAFE), з колегіальним управлінням (три засновники), некомерційна. Ми формуємо команду волонтерів-викладачів і розвиваємо невеликі пілотні формати з регулярним моніторингом дій і прозорою комунікацією з партнерами. Нас супроводжує програма Guid'Asso.", "Documents et cadre": "Документи та регламент", "Tout est en ordre, et consultable": "Усе в порядку і доступне для ознайомлення", "Pour garantir un fonctionnement clair, voici les documents que nous mettons à disposition des collectivités.": "Для прозорої роботи ми надаємо громадам такі документи.", "Statuts de l'association": "Статут асоціації", "Disponible": "Доступно", "Règlement intérieur": "Внутрішній регламент", "Récépissé de déclaration en préfecture": "Довідка про реєстрацію в префектурі", "Publication au JOAFE": "Публікація в JOAFE", "Charte du bénévolat": "Хартія волонтерства", "Modèles de conventions et d'engagement": "Зразки угод і зобов'язань", "Projet associatif": "Проєкт асоціації", "Attestation d'assurance responsabilité civile (MAIF)": "Довідка про страхування цивільної відповідальності (MAIF)", "Accompagnement Guid'Asso": "Супровід Guid'Asso", "En cours": "У процесі", "Documents transmis sur simple demande à": "Документи надаються за простим запитом на", "Ils travaillent déjà avec nous": "Вони вже співпрацюють з нами", "Partenaires et communautés": "Партнери та спільноти", "FLEP, centre socio-culturel et sportif de Soyaux": "FLEP, соціокультурний і спортивний центр Суайо", "FCOL, fédération charentaise des œuvres laïques": "FCOL, Шарантська федерація світських організацій", "Comité des fêtes et d'animations de Garat": "Комітет свят і заходів Гара", "Onze bénévoles qui ont une pratique réelle et l'envie de la partager": "Одинадцять волонтерів із реальним досвідом і бажанням ним ділитися", "MAVKA soutient des professionnels talentueux, enseignants, artistes, musiciens, praticiens, qui traversent une période de transition. Nous les aidons à retrouver leur voix, leur public et leur confiance, tout en enrichissant la vie culturelle du territoire.": "MAVKA підтримує талановитих фахівців — викладачів, митців, музикантів, практиків, які переживають період переходу. Ми допомагаємо їм знову знайти свій голос, аудиторію та впевненість, водночас збагачуючи культурне життя території.", "Présidente": "Президентка", "Développement personnel, accompagnement des intervenants, Parcours MAVKA": "Особистий розвиток, супровід волонтерів-викладачів, Шлях MAVKA", "Bénévole": "Волонтер", "Art-thérapie évolutive, impression 3D": "Еволюційна арттерапія, 3D-друк", "Calligraphie chinoise, origami, langue chinoise": "Китайська каліграфія, орігамі, китайська мова", "Peinture décorative ukrainienne de Petrykivka": "Петриківський розпис", "Violon, piano, chant, formation musicale, musique d'ensemble": "Скрипка, фортепіано, спів, музична теорія, ансамблева гра", "Musique": "Музика", "Créations artisanales, couronnes décoratives, objets faits main": "Ремесла, декоративні вінки, вироби ручної роботи", "Gymnastique articulaire, bien-être": "Суглобова гімнастика, добробут", "Membre de l'équipe": "Член команди", "Vous aimeriez rejoindre l'équipe ?": "Хочете приєднатися до команди?", "Le Parcours MAVKA accompagne chaque nouvel intervenant en quatre étapes, de la première rencontre au cours régulier.": "Шлях MAVKA супроводжує кожного нового волонтера-викладача у чотири етапи — від першої зустрічі до регулярних занять.", "Découvrir le Parcours": "Дізнатися про Шлях MAVKA", "Pour vous informer sur un atelier, proposer une idée, rejoindre l'équipe ou construire un partenariat. Ou simplement échanger.": "Щоб дізнатися про майстерню, запропонувати ідею, приєднатися до команди чи побудувати партнерство. Або просто поспілкуватися.", "Téléphone": "Телефон", "Horaires": "Графік роботи", "Lundi au samedi, 10h à 17h": "З понеділка по суботу, з 10:00 до 17:00", "E-mail": "Електронна пошта", "Territoire": "Територія", "Sept communes autour d'Angoulême, Charente (16)": "Сім громад навколо Ангулема, департамент Шаранта (16)", "Réseaux sociaux": "Соціальні мережі", "Prénom et nom": "Ім'я та прізвище", "Je vous écris pour": "Я пишу вам щодо", "Participer à un atelier": "Участь у майстерні", "Proposer un atelier ou rejoindre l'équipe": "Запропонувати майстерню або приєднатися до команди", "Un partenariat avec une commune": "Партнерство з громадою", "Autre chose": "Щось інше", "Message": "Повідомлення", "J'accepte d'être contacté(e) par l'association MAVKA concernant ma demande.": "Я погоджуюсь, що асоціація MAVKA може зв'язатися зі мною щодо мого запиту.", "Envoyer": "Надіслати", "Vos données sont utilisées uniquement pour répondre à votre demande et ne sont pas transmises à des tiers. Vous pouvez exercer vos droits d'accès, de rectification et de suppression en écrivant à asso.mavka@gmail.com.": "Ваші дані використовуються лише для відповіді на ваш запит і не передаються третім особам. Ви можете скористатися правом доступу, виправлення та видалення даних, написавши на asso.mavka@gmail.com.", "Association loi 1901, Charente. Culture, éducation, bien-être et développement personnel, dans sept communes autour d'Angoulême.": "Асоціація за законом 1901 року, Шаранта. Культура, освіта, добробут та особистий розвиток у семи громадах навколо Ангулема.", "Découvrir": "Розділи", "Pourquoi « Mavka »": "Чому «Мавка»", "Rejoindre": "Приєднатися", "Nous joindre": "Зв'язатися з нами", "Mentions légales et protection des données": "Правова інформація та захист даних", "Message envoyé": "Повідомлення надіслано", "Un espace pour apprendre, créer, partager et transmettre": "Простір, щоб навчатися, творити, ділитися та передавати", "MAVKA réunit habitants et bénévoles autour d'activités culturelles, éducatives et de bien-être. Les ateliers se construisent progressivement avec les intervenants, les participants et les partenaires locaux.": "MAVKA об'єднує мешканців і волонтерів навколо культурних, освітніх активностей та активностей з добробуту. Майстерні поступово будуються разом із волонтерами-викладачами, учасниками та місцевими партнерами.", "Découvrir les activités": "Переглянути активності", "Rejoindre MAVKA": "Приєднатися до MAVKA", "Trois rendez-vous à venir. La préinscription est gratuite et sans engagement.": "Три найближчі події. Попередня реєстрація безкоштовна і ні до чого не зобов'язує.", "Voir tout l'agenda": "Переглянути весь розклад", "Comment participer": "Як взяти участь", "Trois étapes, et c'est tout": "Три кроки, і все", "Choisissez une date": "Оберіть дату", "Dans l'agenda, chaque atelier indique la commune, la salle, l'horaire et pour qui il est fait.": "У розкладі кожна майстерня вказує громаду, приміщення, час і для кого вона призначена.", "Préinscrivez-vous gratuitement": "Зареєструйтеся заздалегідь безкоштовно", "Un clic sur HelloAsso. Cela ne vous engage à rien, cela nous aide à prévoir la salle et le matériel.": "Один клік на HelloAsso. Це ні до чого не зобов'язує, але допомагає нам підготувати приміщення й матеріали.", "Venez comme vous êtes": "Приходьте такими, як є", "Aucun niveau requis, aucun matériel à apporter sauf mention contraire. Enfants et adultes bienvenus.": "Жодного рівня підготовки не потрібно, нічого приносити не треба, якщо не вказано інше. Діти та дорослі вітаються.", "En images": "У фотографіях", "À quoi ressemble un atelier MAVKA": "Як виглядає майстерня MAVKA", "Photo à ajouter": "Фото буде додано", "Photo d'illustration, à remplacer": "Ілюстративне фото, буде замінено", "Calligraphie et origami": "Каліграфія та орігамі", "Enfants et parents au Festival du jeu, Garat": "Діти й батьки на Фестивалі настільних ігор, Гара", "Atelier de peinture Petrykivka": "Майстерня петриківського розпису", "Cours de violon, Grand Angoulême": "Заняття зі скрипки, Великий Ангулем", "Calligraphie chinoise et origami": "Китайська каліграфія та орігамі", "Gymnastique douce, salle de l'Atrium": "М'яка гімнастика, Salle de l'Atrium", "Habitant, famille": "Мешканець, родина", "Je cherche une activité": "Я шукаю заняття", "Musique, peinture, calligraphie, gymnastique douce, art-thérapie. Pour les enfants, les adultes, ou les deux ensemble.": "Музика, живопис, каліграфія, м'яка гімнастика, арттерапія. Для дітей, дорослих або для всієї родини разом.", "Voir les activités →": "Переглянути активності →", "Vous avez un savoir-faire et vous cherchez un cadre pour le partager. Le Parcours MAVKA vous accompagne en quatre étapes.": "У вас є навички, і ви шукаєте простір, щоб ними ділитися. Шлях MAVKA супроводжує вас у чотири етапи.", "Un partenaire simple et progressif : une rencontre, un atelier pilote, puis une construction commune avec vos habitants.": "Простий і поступовий партнер: зустріч, пілотна майстерня, а далі — спільна робота з вашими мешканцями.", "Nos activités": "Наші активності", "Tout voir, avec les infos pratiques →": "Переглянути все, з практичною інформацією →", "Des savoir-faire à découvrir et à partager": "Навички, які варто відкрити для себе й поділитися ними", "Artistes, musiciens, enseignants, praticiens et créateurs apportent à MAVKA leurs expériences, leurs compétences et l'envie de les transmettre.": "Митці, музиканти, викладачі, практики та творці приносять у MAVKA свій досвід, навички та бажання ділитися ними.", "Musiciennes, peintres, calligraphe, professeure de gymnastique, art-thérapeute. Onze bénévoles, chacun avec un vrai métier ou un vrai savoir-faire derrière lui.": "Музикантки, художниці, каліграф, викладачка гімнастики, арттерапевт. Одинадцять волонтерів, кожен із реальною професією чи навичкою за плечима.", "MAVKA est née de l'expérience de personnes arrivées en France avec un métier, un art ou un savoir-faire, et de l'envie de le partager avec leurs voisins. Une forêt est un bon modèle : chacun y pousse à son rythme, et tout le monde s'y entraide. La petite pousse sur sa tête, c'est vous.": "MAVKA народилася з досвіду людей, які приїхали до Франції зі своєю професією, мистецтвом чи навичкою і бажанням поділитися ним із сусідами. Ліс — гарна модель: кожен росте у своєму темпі, і всі допомагають одне одному. Маленький паросток на її голові — це ви.", "Toutes les prochaines dates": "Усі найближчі дати", "Retrouvez ici les ateliers, rencontres et événements proposés par MAVKA et ses partenaires.": "Тут зібрані майстерні, зустрічі та події, які пропонує MAVKA і її партнери.", "Vous ne trouvez pas encore ce que vous cherchez ?": "Ще не знайшли те, що шукаєте?", "Certaines activités sont encore en préparation. Dites-nous ce qui vous intéresse et dans quelle commune : vos demandes nous aident à constituer les groupes et à organiser les prochains ateliers.": "Деякі активності ще готуються. Скажіть нам, що вас цікавить і в якій громаді — ваші запити допомагають нам формувати групи та організовувати наступні майстерні.", "Nous faire part de votre intérêt": "Розкажіть нам про вашу зацікавленість", "Découvrez les activités proposées par MAVKA, regroupées par domaine.": "Ознайомтесь з активностями MAVKA, згрупованими за напрямом.", "Événementiel": "Події", "Pour qui": "Для кого", "Enfants et adultes": "Діти та дорослі", "L'âge minimum est indiqué sur chaque atelier.": "Мінімальний вік вказано для кожної майстерні.", "Combien": "Скільки коштує", "Gratuit pour commencer": "Безкоштовно на старті", "Les premières séances sont gratuites sur préinscription. Les cours réguliers ont un tarif fixé avec l'intervenant.": "Перші заняття безкоштовні за попередньою реєстрацією. Вартість регулярних занять узгоджується з викладачем.", "Où": "Де", "Garat, Soyaux, Grand Angoulême": "Гара, Суайо, Великий Ангулем", "Dans des salles communales, d'autres communes au fur et à mesure.": "У громадських приміщеннях, з часом додаватимуться інші громади.", "Quoi apporter": "Що взяти з собою", "Rien": "Нічого", "Le matériel est fourni, sauf mention contraire sur l'atelier.": "Матеріали надаються, якщо для майстерні не вказано інше.", "Créer de ses mains, découvrir une tradition": "Творити власними руками, відкривати традиції", "1 à 2 h": "1–2 год", "Culture · Enfants et adultes": "Культура · Діти та дорослі", "Peinture de Petrykivka": "Петриківський розпис", "La peinture décorative ukrainienne aux motifs floraux, transmise de génération en génération.": "Український декоративний розпис із квітковими мотивами, що передається з покоління в покоління.", "Une initiation à l'écriture au pinceau, au pliage et aux premiers mots de chinois.": "Знайомство з пензликовим письмом, складанням паперу та першими словами китайською.", "Culture · Familles": "Культура · Родини", "Créations artisanales": "Вироби ручної роботи", "Couronnes décoratives et objets faits main, à emporter chez soi.": "Декоративні вінки та предмети ручної роботи, які можна забрати додому.", "Apprendre un savoir-faire concret": "Опанувати конкретну навичку", "Ateliers de musique": "Музичні майстерні", "Découvrir un instrument, progresser à son rythme ou simplement partager le plaisir de jouer.": "Відкрити для себе інструмент, розвиватися у своєму темпі або просто розділити радість гри.", "Prendre soin de soi, simplement": "Просто подбати про себе", "Bien-être · Adultes": "Добробут · Дорослі", "Gymnastique articulaire": "Суглобова гімнастика", "Hanna Sokha · Salle de l'Atrium": "Ганна Соха · Salle de l'Atrium", "Mobilité et gymnastique douce le jeudi matin. Préinscription gratuite pour ouvrir le créneau avec la mairie.": "Розвиток рухливості та м'яка гімнастика щочетверга вранці. Безкоштовна попередня реєстрація, щоб відкрити цей час разом із мерією.", "Adultes": "Дорослі", "Yoga, mobilité, gestion du stress, espaces de parole": "Йога, розвиток рухливості, управління стресом, простори для розмов", "L'équipe Bien-être": "Команда «Добробут»", "Des outils simples pour retrouver son équilibre. Prévention et bien-être, sans remplacer un suivi médical.": "Прості інструменти, щоб віднайти рівновагу. Профілактика та добробут, без заміни медичного супроводу.", "William Aubert · Salle de temps libre, Garat": "Вільям Обер · Salle de temps libre, Гара", "Une première expérience créative ouverte à tous. Aucun talent artistique n'est nécessaire.": "Перший творчий досвід, відкритий для всіх. Жодних художніх навичок не потрібно.", "Construire son propre projet": "Побудувати власний проєкт", "Cette direction s'adresse aux personnes qui souhaitent partager un savoir-faire, développer une idée ou faire leurs premiers pas autour d'un projet.": "Цей напрям — для тих, хто хоче поділитися своїми навичками, розвинути ідею або зробити перші кроки до власного проєкту.", "Le Parcours MAVKA permet d'avancer progressivement, de l'idée à une première activité concrète, avec l'accompagnement de Larysa Mas et l'appui de l'association.": "Шлях MAVKA дозволяє просуватися поступово — від ідеї до першої реальної активності — за супроводу Лариси Мас і підтримки асоціації.", "Les ateliers, ce sont d'abord des personnes.": "Майстерні — це насамперед люди.", "Chaque personne apporte à MAVKA son expérience, ses compétences et son envie de transmettre. Découvrez les bénévoles et intervenants qui construisent progressivement les activités de l'association.": "Кожна людина привносить у MAVKA свій досвід, навички та бажання ділитися ними. Знайомтесь із волонтерами й волонтерами-викладачами, які поступово розбудовують активності асоціації.", "Voir sa page →": "Переглянути сторінку →", "Bien-être · Éducation": "Добробут · Освіта", "Éducation · Grand Angoulême": "Освіта · Великий Ангулем", "Bien-être · Garat": "Добробут · Гара", "Pourquoi MAVKA ?": "Чому MAVKA?", "Un espace pour partager et faire évoluer ses compétences": "Простір, щоб ділитися навичками й розвивати їх", "Découvrir le Parcours MAVKA →": "Дізнатися про Шлях MAVKA →", "MAVKA permet aux bénévoles et intervenants de participer à la vie associative, de partager leurs savoir-faire et d'expérimenter progressivement de nouvelles activités. Pour ceux qui souhaitent aller plus loin, le Parcours MAVKA accompagne le développement d'un projet dans un cadre structuré.": "MAVKA дозволяє волонтерам і волонтерам-викладачам брати участь у житті асоціації, ділитися своїми навичками та поступово випробовувати нові активності. Для тих, хто хоче піти далі, Шлях MAVKA супроводжує розвиток проєкту в структурованих умовах.", "Gouvernance": "Управління", "Une association collégiale": "Асоціація з колегіальним управлінням", "MAVKA est une association loi 1901 à gouvernance collégiale, fondée par trois personnes et présidée par Larysa Mas. Elle est déclarée, assurée, et accompagnée par le dispositif Guid'Asso.": "MAVKA — асоціація за законом 1901 року з колегіальним управлінням, заснована трьома людьми, президентка — Лариса Мас. Вона зареєстрована, застрахована та отримує супровід програми Guid'Asso.", "Une première rencontre informelle suffit pour commencer. Nous voulons d'abord écouter vos idées.": "Достатньо однієї неформальної зустрічі, щоб почати. Спершу ми хочемо вислухати ваші ідеї.", "Passer en français": "Перейти на французьку", "Style du site": "Стиль сайту", "Vif": "Яскравий", "Forêt": "Ліс", "Lin": "Льон", "Switch language": "Змінити мову", "Les préinscriptions nous permettent de connaître les besoins, de constituer les groupes et d'organiser les activités dans les meilleures conditions. Votre intérêt nous aide également à rechercher les lieux et les conditions nécessaires à leur mise en place avec nos partenaires locaux.": "Попередня реєстрація дозволяє нам зрозуміти потреби, сформувати групи та організувати активності в найкращих умовах. Ваша зацікавленість також допомагає нам шукати приміщення та умови для їх проведення разом із місцевими партнерами.", "Bénévoles engagés dans le développement de MAVKA.": "Волонтери, залучені до розвитку MAVKA.", "Les premières activités sont en préparation. Découvrez les propositions et": "Перші активності готуються. Ознайомтесь із пропозиціями та", "indiquez-nous": "повідомте нам", "celles qui vous intéressent.": "які з них вас цікавлять.", "Nos directions": "Наші напрями", "Ce que vous pouvez découvrir chez MAVKA": "Що ви можете знайти в MAVKA", "Expérimentation": "Експериментування", "Vous préparez et testez une première activité. Vous vérifiez le format, l'intérêt du public et ce qui fonctionne. Ensemble, nous décidons ensuite s'il est utile de poursuivre ou d'adapter l'activité.": "Ви готуєте й тестуєте першу активність. Перевіряєте формат, зацікавленість аудиторії та що працює. Далі ми разом вирішуємо, чи варто продовжувати або адаптувати активність.", "Un cadre associatif clair et assuré pour expérimenter une première activité dans le cadre de MAVKA.": "Чіткі та застраховані асоціативні умови для випробування першої активності в рамках MAVKA.", "Un appui pour rechercher un lieu, un public ou des partenaires, selon les besoins de l'activité et les possibilités de MAVKA.": "Підтримка в пошуку приміщення, аудиторії чи партнерів — залежно від потреб активності та можливостей MAVKA.", "La prise en charge ou le remboursement de certains frais nécessaires, lorsqu'ils ont été prévus et validés avec MAVKA.": "Покриття або компенсація деяких необхідних витрат, якщо вони заздалегідь узгоджені й затверджені з MAVKA.", "Un accompagnement pour préparer, tester et faire évoluer votre activité ou votre projet, à votre rythme.": "Супровід у підготовці, тестуванні та розвитку вашої активності чи проєкту — у вашому темпі.", "La valorisation de l'expérience acquise au sein de MAVKA et des compétences développées au cours de votre parcours.": "Визнання досвіду, здобутого в MAVKA, і навичок, розвинутих упродовж вашого шляху.", "Une équipe de bénévoles et d'intervenants aux parcours variés, avec qui échanger, partager des expériences et construire des projets.": "Команда волонтерів і волонтерів-викладачів із різним досвідом, з якими можна обмінюватися думками, ділитися досвідом і будувати спільні проєкти.", "Une collaboration qui reste humaine": "Співпраця, що залишається людяною", "MAVKA encourage les bénévoles et les intervenants à participer à la vie de l'association : rencontres, événements, échanges ou projets communs.": "MAVKA заохочує волонтерів і волонтерів-викладачів брати участь у житті асоціації: зустрічі, події, обміни досвідом чи спільні проєкти.", "Chacun participe selon son rôle, ses disponibilités et le cadre de sa collaboration.": "Кожен бере участь відповідно до своєї ролі, можливостей і формату співпраці.", "Nous ne cherchons pas seulement des intervenants. Nous accueillons aussi des personnes qui souhaitent partager un savoir-faire, tester une idée ou développer progressivement un projet avec MAVKA.": "Ми шукаємо не лише волонтерів-викладачів. Ми також приймаємо людей, які хочуть поділитися своїми навичками, випробувати ідею або поступово розвинути проєкт разом із MAVKA.", "Est-ce un contrat de travail ?": "Це трудовий договір?", "Non. La collaboration avec MAVKA repose sur un partenariat et une activité indépendante, avec des accords définis par écrit.": "Ні. Співпраця з MAVKA — це партнерство й незалежна діяльність із письмово оформленими домовленостями.", "La participation aux événements est-elle obligatoire ?": "Чи обов'язкова участь у заходах?", "Oui, au moins une fois tous les trois mois. Cela peut être un forum, une fête ou un atelier gratuit.": "Так, щонайменше раз на три місяці. Це може бути форум, свято або безкоштовна майстерня.", "Et si je ne suis pas disponible à un moment donné ?": "А якщо я не можу бути присутнім у якийсь момент?", "Le dialogue est toujours possible. La participation est organisée à l'avance en tenant compte des possibilités réelles de chacun.": "Діалог завжди можливий. Участь плануємо заздалегідь, з урахуванням реальних можливостей кожного.", "De l'idée au premier pas concret": "Від ідеї до першого конкретного кроку", "Vous avez une passion, une compétence, un savoir-faire ou simplement une idée ? Le Parcours MAVKA vous permet d'avancer progressivement, d'expérimenter et de trouver la forme qui vous correspond.": "У вас є пристрасть, навичка, вміння чи просто ідея? Шлях MAVKA дозволяє просуватися поступово, експериментувати та знайти форму, яка вам підходить.", "Il n'y a pas de parcours imposé : chacun avance à son rythme et selon son projet.": "Немає нав'язаного шляху: кожен рухається у своєму темпі й відповідно до свого проєкту.", "Immersion": "Занурення", "Immersion & découverte": "Занурення та знайомство", "Vous découvrez MAVKA, ses activités, son équipe et sa manière de fonctionner. Vous échangez avec nous sur vos envies, vos compétences ou votre idée, sans devoir avoir déjà un projet défini.": "Ви знайомитеся з MAVKA, її активностями, командою та тим, як усе влаштовано. Ви обговорюєте з нами свої бажання, навички чи ідею — не обов'язково мати вже готовий проєкт.", "Si l'approche vous parle, vous pouvez participer à la vie de MAVKA, rejoindre l'équipe bénévole ou commencer à construire votre idée avec nous. Chacun avance selon sa situation et ses disponibilités.": "Якщо підхід вам близький, ви можете брати участь у житті MAVKA, приєднатися до волонтерської команди або почати розбудовувати свою ідею разом із нами. Кожен рухається відповідно до своєї ситуації та можливостей.", "Ici, l'idée devient concrète.": "Тут ідея стає реальною.", "Plusieurs chemins sont possibles.": "Можливі різні шляхи.", "Bénévolat · Projet · Prestation professionnelle": "Волонтерство · Проєкт · Професійна діяльність", "Vous avez un savoir-faire. Donnons-lui vie ensemble.": "У вас є навички. Втілімо їх у життя разом.", "Mavka assise en tailleur, sereine, les yeux fermés": "Мавка сидить у позі лотоса, спокійна, з заплющеними очима", "Mavka assise, tressant une couronne de fleurs violettes": "Мавка сидить і плете вінок із фіолетових квітів", "Contrat-cadre de prestation d'activités": "Рамковий договір про надання послуг"};
  const attrs=['alt','placeholder','aria-label','title'];
  const store=[];
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT,{acceptNode:n=>/\S/.test(n.nodeValue)&&!['SCRIPT','STYLE'].includes(n.parentNode.nodeName)?1:2});
  let n; while(n=walker.nextNode()){ store.push({node:n,fr:n.nodeValue}); }
  document.querySelectorAll('*').forEach(el=>attrs.forEach(a=>{ if(el.hasAttribute(a)) store.push({el,attr:a,fr:el.getAttribute(a)}); }));
  const btn=document.getElementById('langBtn');
  function apply(lang){
    store.forEach(s=>{
      const key=s.fr.replace(/\s+/g,' ').trim(); const uk=UK[key];
      const v=(lang==='uk'&&uk)?s.fr.replace(key,uk):s.fr;
      if(s.node) s.node.nodeValue=v; else s.el.setAttribute(s.attr,v);
    });
    document.documentElement.lang=lang; btn.textContent=lang==='uk'?'FR':'УКР';
    btn.setAttribute('aria-label',lang==='uk'?'Passer en français':'Змінити мову');
    try{localStorage.setItem('mavka-lang',lang)}catch(e){}
  }
  let lang='fr'; try{lang=localStorage.getItem('mavka-lang')||'fr'}catch(e){}
  apply(lang);
  btn.addEventListener('click',()=>apply(document.documentElement.lang==='uk'?'fr':'uk'));
})();
// ---- contact form ----
(function(){
  const form=document.getElementById('contactForm');
  if(!form) return;
  const note=document.getElementById('contactNote');
  const noteDefault=note.textContent;
  form.addEventListener('submit', async e=>{
    e.preventDefault();
    const btn=form.querySelector('button[type="submit"]');
    const fd=new FormData(form);
    const payload={
      nom: fd.get('name')||'',
      email: fd.get('email')||'',
      sujet: fd.get('topic')||'',
      message: fd.get('message')||'',
      site_web: fd.get('site_web')||''
    };
    btn.disabled=true; btn.textContent='Envoi…';
    try{
      const res=await fetch('/contact-handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams(payload)
      });
      const data=await res.json();
      if(data.ok){
        btn.textContent='Message envoyé';
        note.textContent='Merci, votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.';
        form.querySelectorAll('input,textarea,select').forEach(f=>f.disabled=true);
      } else {
        btn.disabled=false; btn.textContent='Envoyer';
        note.textContent=(data.error||'Une erreur est survenue.')+' Vous pouvez aussi nous écrire à asso.mavka@gmail.com.';
      }
    } catch(err){
      btn.disabled=false; btn.textContent='Envoyer';
      note.textContent='Impossible d\'envoyer le message pour le moment. Écrivez-nous à asso.mavka@gmail.com.';
    }
  });
})();
</script>
</body>
</html>
