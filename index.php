<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/site_functions.php';

$site_intervenants = site_intervenants_actifs();
$site_agenda_teaser = site_activites_a_venir(3);
$site_agenda_toutes = site_activites_a_venir();
$site_activites_culture = site_activites_par_categorie('Culture');
$site_activites_education = site_activites_par_categorie('Éducation');
$site_activites_bienetre = site_activites_par_categorie('Bien-être');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="MAVKA — association loi 1901 en Charente. Ateliers de culture, éducation, bien-être et développement personnel dans sept communes autour d'Angoulême.">
<title>MAVKA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Figtree:wght@400;500;600&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap">
<link rel="stylesheet" href="/assets/site.css">
</head>
<body>

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
    <div class="styles" id="styles" role="group" aria-label="Style du site">
      <button type="button" data-style="vif" aria-pressed="true"><i style="background:#1FAE93"></i>Vif</button>
      <button type="button" data-style="foret" aria-pressed="false"><i style="background:#5E8C6A"></i>Forêt</button>
      <button type="button" data-style="lin" aria-pressed="false"><i style="background:#B9AE95"></i>Lin</button>
    </div>
    <button class="lang" id="langBtn" type="button" aria-label="Switch language">EN</button>
    <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="nav">Menu</button>
  </div>
</header>

<main><svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="m-stand" viewBox="0 0 310 769"><image href="/assets/site-img/img-02-33b2e93d45.webp" width="310" height="769"/></symbol><symbol id="m-wave" viewBox="0 0 626 722"><image href="/assets/site-img/img-03-1d459c08a4.webp" width="626" height="722"/></symbol><symbol id="m-magnify" viewBox="0 0 552 756"><image href="/assets/site-img/img-04-3b8dc488ec.webp" width="552" height="756"/></symbol><symbol id="m-jump" viewBox="0 0 469 734"><image href="/assets/site-img/img-05-10df6582b6.webp" width="469" height="734"/></symbol><symbol id="m-read" viewBox="0 0 549 767"><image href="/assets/site-img/img-06-3bc40d08fc.webp" width="549" height="767"/></symbol><symbol id="m-point" viewBox="0 0 687 768"><image href="/assets/site-img/img-07-00875b060b.webp" width="687" height="768"/></symbol><symbol id="m-logo" viewBox="0 0 574 587"><image href="/assets/site-img/img-01-017fac3c9d.webp" width="574" height="587"/></symbol></svg>

<!-- ============ ACCUEIL ============ -->
<div class="page" id="page-accueil">
  <section class="hero">
    <div class="wrap grid">
      <div class="hero-copy">
        <span class="eyebrow">Association loi 1901 · Charente</span>
        <h1>Des ateliers pour petits et grands, près de chez vous.</h1>
        <p class="lede">Musique, arts, langues, bien-être. Des groupes à taille humaine dans sept communes autour d'Angoulême, animés par des personnes qui savent faire. La première séance est souvent gratuite.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#agenda">Voir les prochaines dates</a>
          <a class="btn btn-ghost" href="#activites">Découvrir les activités</a>
        </div>
      </div>
      <div class="hero-art"><img src="/assets/site-img/img-03-1d459c08a4.webp" alt="Mavka, la mascotte de l'association, saluant de la main"></div>
    </div>
  </section>

  <div class="trust">
    <div class="wrap row">
      <div class="facts">
        <span><i></i>7 communes</span>
        <span><i></i><?= count($site_intervenants) ?> intervenants bénévoles</span>
        <span><i></i>Association déclarée (RNA, JOAFE)</span>
        <span><i></i>Assurée MAIF</span>
      </div>
      <div class="logos" aria-label="Partenaires">
        <img src="/assets/site-img/img-08-3aa13012b1.webp" alt="Commune de Garat">
        <img src="/assets/site-img/img-09-0214dc1e39.webp" alt="Ville de Soyaux">
        <img src="/assets/site-img/img-10-0b0315c840.webp" alt="FLEP Soyaux">
        <img src="/assets/site-img/img-11-6a6681c6fb.webp" alt="FCOL">
      </div>
    </div>
  </div>

  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Agenda</span>
        <h2>Prochaines dates</h2>
        <p class="lede">Trois rendez-vous à venir. La préinscription est gratuite et sans engagement.</p>
      </div>
      <?= render_events_grid($site_agenda_teaser, 'three') ?>
      <p style="margin-top:22px"><a class="btn btn-ghost" href="#agenda">Voir tout l'agenda</a></p>
    </div>
  </section>

  <section class="sand">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Comment participer</span>
        <h2>Trois étapes, et c'est tout</h2>
      </div>
      <div class="howto">
        <div class="how"><h3>Choisissez une date</h3><p>Dans l'agenda, chaque atelier indique la commune, la salle, l'horaire et pour qui il est fait.</p></div>
        <div class="how"><h3>Préinscrivez-vous gratuitement</h3><p>Un clic sur HelloAsso. Cela ne vous engage à rien, cela nous aide à prévoir la salle et le matériel.</p></div>
        <div class="how"><h3>Venez comme vous êtes</h3><p>Aucun niveau requis, aucun matériel à apporter sauf mention contraire. Enfants et adultes bienvenus.</p></div>
      </div>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">En images</span>
        <h2>À quoi ressemble un atelier MAVKA</h2>
      </div>
      <div class="photos">
        <figure class="photo"><img src="/assets/site-img/img-12-dfccff8e69.webp" alt=""><figcaption><b>Photo d'illustration, à remplacer</b>Enfants et parents au Festival du jeu, Garat</figcaption></figure>
        <figure class="photo"><img src="/assets/site-img/img-14-c7c993d250.webp" alt=""><figcaption><b>Photo d'illustration, à remplacer</b>Atelier de peinture Petrykivka</figcaption></figure>
        <figure class="photo"><img src="/assets/site-img/img-15-1b7f74c811.webp" alt=""><figcaption><b>Photo d'illustration, à remplacer</b>Cours de violon, Grand Angoulême</figcaption></figure>
        <figure class="photo"><img src="/assets/site-img/img-16-e387b6953f.webp" alt=""><figcaption><b>Photo d'illustration, à remplacer</b>Calligraphie et origami</figcaption></figure>
        <figure class="photo"><img src="/assets/site-img/img-13-d6e281d921.webp" alt=""><figcaption><b>Photo d'illustration, à remplacer</b>Gymnastique douce, salle de l'Atrium</figcaption></figure>
      </div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Par où commencer</span>
        <h2>MAVKA s'adresse à trois types de personnes. Laquelle êtes-vous ?</h2>
      </div>
      <div class="doors">
        <a class="door" href="#activites">
          <span class="who">Habitant, famille</span>
          <h3>Je cherche une activité</h3>
          <p>Musique, peinture, calligraphie, gymnastique douce, art-thérapie. Pour les enfants, les adultes, ou les deux ensemble.</p>
          <span class="go">Voir les activités →</span>
        </a>
        <a class="door" href="#intervenants">
          <span class="who">Enseignant, artiste, praticien</span>
          <h3>Je veux transmettre ce que je sais</h3>
          <p>Vous avez un savoir-faire et vous cherchez un cadre pour le partager. Le Parcours MAVKA vous accompagne en quatre étapes.</p>
          <span class="go">Découvrir le Parcours →</span>
        </a>
        <a class="door" href="#collectivites">
          <span class="who">Commune, collectivité</span>
          <h3>Je représente une collectivité</h3>
          <p>Un partenaire simple et progressif : une rencontre, un atelier pilote, puis une construction commune avec vos habitants.</p>
          <span class="go">Travailler avec MAVKA →</span>
        </a>
      </div>
    </div>
  </section>

  <section>
    <div class="wrap dir-grid">
      <div>
        <div class="head" style="margin-bottom:24px">
          <span class="eyebrow">Nos activités</span>
          <h2>Quatre directions, une même idée : apprendre les uns des autres.</h2>
        </div>
        <div class="dirs"><div class="dir"><div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-4.5 0-8 3.6-8 8 0 3 2 4 4 4h1a2 2 0 0 1 2 2c0 1.5 1 2 2 2 4.5 0 7-3 7-8 0-4.4-3.5-8-8-8z"/><circle cx="8.5" cy="10" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="15.5" cy="10" r="1"/></svg></div><div><h3>Culture</h3><p>Peinture décorative ukrainienne de Petrykivka, calligraphie chinoise, origami, couronnes et objets faits main. Créer de ses mains et découvrir une tradition venue d'ailleurs.</p></div></div><div class="dir"><div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V6l10-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="16" r="2.5"/></svg></div><div><h3>Éducation</h3><p>Musique (violon, piano, chant, formation musicale) avec trois intervenantes. Bientôt : anglais du quotidien et impression 3D.</p></div></div><div class="dir"><div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg></div><div><h3>Bien-être</h3><p>Gymnastique douce, yoga, mobilité, gestion du stress, espaces de parole. Des outils simples pour prendre soin de soi, en prévention, sans remplacer un suivi médical.</p></div></div><div class="dir"><div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V11"/><path d="M12 11c0-4 3-6 7-6 0 4-3 6-7 6z"/><path d="M12 14c0-3-2.5-5-6-5 0 3 2.5 5 6 5z"/></svg></div><div><h3>Développement personnel</h3><p>Le Parcours MAVKA et l'art-thérapie : chacun construit son propre projet, accompagné pas à pas, de la découverte à l'autonomie.</p></div></div></div>
        <p style="margin-top:22px"><a href="#activites">Tout voir, avec les infos pratiques →</a></p>
      </div>
      <div class="dir-art"><img src="/assets/site-img/img-06-3bc40d08fc.webp" alt="Mavka assise, en train de lire"></div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap team-preview">
      <div>
        <span class="eyebrow">L'équipe</span>
        <h2 style="margin-top:8px">Des ateliers animés par des gens qui ont vraiment une pratique</h2>
        <p class="lede" style="margin-top:10px">Musiciennes, peintres, calligraphe, professeure de gymnastique, art-thérapeute. <?= count($site_intervenants) ?> bénévoles, chacun avec un vrai métier ou un vrai savoir-faire derrière lui.</p>
        <p style="margin-top:16px"><a class="btn btn-ghost" href="#equipe">Rencontrer l'équipe</a></p>
      </div>
      <div class="faces"><?php foreach (array_slice($site_intervenants, 0, 6) as $iv):
        $photoUrl = ($iv['photo'] && $iv['dossier']) ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']) : '/assets/site-img/img-01-017fac3c9d.webp';
      ?><img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($iv['nom']) ?>"><?php endforeach; ?></div>
    </div>
  </section>

  <section class="violet tight">
    <div class="wrap story">
      <img src="/assets/site-img/img-01-017fac3c9d.webp" alt="Portrait de Mavka">
      <div>
        <span class="eyebrow" style="color:var(--violet);background:var(--violet-tint)">Pourquoi « Mavka » ?</span>
        <h3 style="margin:12px 0 10px">Dans le folklore ukrainien, la Mavka est l'esprit protecteur de la forêt.</h3>
        <p>MAVKA est née de l'expérience de personnes arrivées en France avec un métier, un art ou un savoir-faire, et de l'envie de le partager avec leurs voisins. Une forêt est un bon modèle : chacun y pousse à son rythme, et tout le monde s'y entraide. La petite pousse sur sa tête, c'est vous.</p>
      </div>
    </div>
  </section>

  <section class="tight">
    <div class="wrap cta-band">
      <div>
        <h3>Une question, une idée, un projet ?</h3>
        <p>Nous répondons du lundi au samedi, de 10h à 17h. Un appel ou un message suffit pour commencer.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#contact">Nous écrire</a>
          <a class="btn btn-ghost" href="tel:+33656682153">+33 6 56 68 21 53</a>
        </div>
      </div>
      <svg class="mascot" viewBox="0 0 310 769" style="aspect-ratio:310/769"><use href="#m-stand"/></svg>
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
        <p class="lede">Chaque rendez-vous indique la commune, la salle et pour qui il est fait. La préinscription est gratuite et sans engagement.</p>
      </div>
      <?= render_events_grid($site_agenda_toutes) ?>
    </div>
  </section>
  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:16px">
          <span class="eyebrow">Activités régulières</span>
          <h2>Pas de date qui vous convient ?</h2>
        </div>
        <p class="lede">Plusieurs activités se font sur demande ou en cours réguliers : musique, arts, bien-être. Dites-nous ce qui vous intéresse et dans quelle commune, nous organisons une date dès qu'un petit groupe est réuni.</p>
        <div class="actions" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
          <a class="btn btn-primary" href="#activites">Voir les activités</a>
          <a class="btn btn-ghost" href="#contact">Demander une date</a>
        </div>
      </div>
      <div class="dir-art"><svg class="mascot tall" viewBox="0 0 469 734" style="aspect-ratio:469/734"><use href="#m-jump"/></svg></div>
    </div>
  </section>
</div>

<!-- ============ ACTIVITES ============ -->
<div class="page" id="page-activites">
  <section>
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Nos activités</span>
        <h1 style="font-size:clamp(2rem,4vw,3.2rem)">Quatre directions, une même idée : apprendre les uns des autres.</h1>
        <p class="lede">Toutes les activités sont ouvertes aux débutants. La plupart accueillent les enfants comme les adultes, et certaines sont pensées pour les deux ensemble.</p>
      </div>
      <div class="facts-grid">
        <div class="fact"><span class="k">Pour qui</span><b>Enfants et adultes</b><span>L'âge minimum est indiqué sur chaque atelier.</span></div>
        <div class="fact"><span class="k">Combien</span><b>Gratuit pour commencer</b><span>Les premières séances sont gratuites sur préinscription. Les cours réguliers ont un tarif fixé avec l'intervenant.</span></div>
        <div class="fact"><span class="k">Où</span><b>Garat, Soyaux, Grand Angoulême</b><span>Dans des salles communales, d'autres communes au fur et à mesure.</span></div>
        <div class="fact"><span class="k">Quoi apporter</span><b>Rien</b><span>Le matériel est fourni, sauf mention contraire sur l'atelier.</span></div>
      </div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Culture</span><h2>Créer de ses mains, découvrir une tradition</h2></div>
      <?= render_events_grid($site_activites_culture) ?>
    </div>
  </section>

  <section>
    <div class="wrap">
      <div class="head"><span class="eyebrow">Éducation</span><h2>Apprendre un savoir-faire concret</h2></div>
      <?= render_events_grid($site_activites_education) ?>
    </div>
  </section>

  <section class="sand">
    <div class="wrap">
      <div class="head"><span class="eyebrow">Bien-être</span><h2>Prendre soin de soi, simplement</h2></div>
      <?= render_events_grid($site_activites_bienetre) ?>
    </div>
  </section>

  <section>
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:16px"><span class="eyebrow">Développement personnel</span><h2>Construire son propre projet</h2></div>
        <p class="lede">Cette direction s'adresse aux personnes qui veulent transmettre : le Parcours MAVKA accompagne chacun de l'idée au cours régulier, avec l'appui de Larysa Mas, présidente de l'association, et des approches comme l'art-thérapie.</p>
        <p style="margin-top:18px"><a class="btn btn-primary" href="#intervenants">Découvrir le Parcours</a></p>
      </div>
      <div class="dir-art"><svg class="mascot tall" viewBox="0 0 687 768" style="aspect-ratio:687/768"><use href="#m-point"/></svg></div>
    </div>
  </section>
</div>

<!-- ============ INTERVENANTS ============ -->
<div class="page" id="page-intervenants">
  <section class="hero">
    <div class="wrap grid">
      <div class="hero-copy">
        <span class="eyebrow">Le Parcours MAVKA</span>
        <h1 style="font-size:clamp(2rem,4.4vw,3.4rem)">Vous avez un savoir-faire. Nous vous aidons à en faire un cours.</h1>
        <p class="lede">Le Parcours s'adresse aux enseignants, artistes, musiciens, professeurs de yoga et praticiens qui traversent une transition professionnelle, souvent après une arrivée en France. Quatre étapes, à votre rythme, dans un cadre sécurisant.</p>
        <div class="actions">
          <a class="btn btn-primary" href="#contact">Demander une première rencontre</a>
          <a class="btn btn-ghost" href="#steps">Voir les quatre étapes</a>
        </div>
      </div>
      <div class="hero-art"><img src="/assets/site-img/img-07-00875b060b.webp" alt="Mavka pointant vers le haut"></div>
    </div>
  </section>

  <section id="steps">
    <div class="wrap">
      <div class="head">
        <span class="eyebrow">Quatre étapes</span>
        <h2>De la première rencontre à un cours régulier</h2>
        <p class="lede">Tout commence par une conversation informelle. Nous voulons comprendre vos idées, vos besoins et où vous voulez aller. Ensuite seulement, on structure.</p>
      </div>
      <div class="steps">
        <div class="step"><h3>Immersion et découverte</h3><p>Vous participez à la vie de l'association : forums, fêtes, ateliers, soutien aux autres intervenants. C'est le moment de sentir si nos valeurs et notre rythme vous correspondent.</p></div>
        <div class="step"><h3>Engagement</h3><p>Si l'approche vous parle, vous signez la Charte du bénévole et rejoignez officiellement l'équipe. Votre profil apparaît sur le site. Vos frais (déplacements, matériel) sont remboursés.</p></div>
        <div class="step"><h3>Séances d'essai</h3><p>Vous animez une ou deux séances test. Vous vérifiez le format, le sujet et l'intérêt des participants, puis vous décidez si vous continuez.</p></div>
        <div class="step"><h3>Collaboration durable</h3><p>Si votre cours trouve son public, vous devenez intervenant rémunéré. Les modalités sont fixées par écrit et vous lancez un cours régulier au sein de MAVKA.</p></div>
      </div>
    </div>
  </section>

  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:20px"><span class="eyebrow">Ce que MAVKA apporte</span><h2>Un tremplin, pas un contrat de travail</h2></div>
        <ul class="list">
          <li><span>Un cadre juridique et assuré pour vos premières séances (association déclarée, responsabilité civile MAIF).</span></li>
          <li><span>Des salles communales et un premier public, grâce à nos partenariats avec les mairies.</span></li>
          <li><span>Le remboursement de vos frais engagés dès l'étape 2.</span></li>
          <li><span>Des lettres de recommandation pour la suite de votre parcours professionnel.</span></li>
          <li><span>L'accompagnement de Larysa Mas, présidente de MAVKA, pour construire votre projet pas à pas.</span></li>
          <li><span>Une équipe de onze personnes qui sont passées par là.</span></li>
        </ul>
      </div>
      <div>
        <div class="head" style="margin-bottom:12px"><span class="eyebrow">Ce que nous attendons</span><h2>Une seule chose : rester impliqué</h2></div>
        <p class="lede" style="margin-bottom:18px">Les intervenants participent à la vie de l'association au moins une fois par trimestre : un forum, une fête, un atelier gratuit. C'est ce qui fait de MAVKA une communauté et non un simple planning de cours.</p>
        <details><summary>Est-ce un contrat de travail ?</summary><p>Non. La collaboration avec MAVKA repose sur un partenariat et une activité indépendante, avec des accords définis par écrit.</p></details>
        <details><summary>La participation aux événements est-elle obligatoire ?</summary><p>Oui, au moins une fois tous les trois mois. Cela peut être un forum, une fête ou un atelier gratuit.</p></details>
        <details><summary>Et si je ne suis pas disponible à un moment donné ?</summary><p>Le dialogue est toujours possible. La participation est organisée à l'avance en tenant compte des possibilités réelles de chacun.</p></details>
      </div>
    </div>
  </section>

  <section class="tight">
    <div class="wrap cta-band">
      <div>
        <h3>Prêt à faire le premier pas ?</h3>
        <p>Nous ne cherchons pas seulement des intervenants. Nous cherchons des partenaires dont nous aiderons à concrétiser les idées.</p>
        <div class="actions"><a class="btn btn-primary" href="#contact">Prendre rendez-vous</a></div>
      </div>
      <svg class="mascot" viewBox="0 0 469 734" style="aspect-ratio:469/734"><use href="#m-jump"/></svg>
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
        <li>Statuts de l'association <span class="st">Disponible</span></li>
        <li>Règlement intérieur <span class="st">Disponible</span></li>
        <li>Récépissé de déclaration en préfecture <span class="st">Disponible</span></li>
        <li>Publication au JOAFE <span class="st">Disponible</span></li>
        <li>Charte du bénévolat <span class="st">Disponible</span></li>
        <li>Modèles de conventions et d'engagement <span class="st">Disponible</span></li>
        <li>Projet associatif <span class="st">Disponible</span></li>
        <li>Attestation d'assurance responsabilité civile (MAIF) <span class="st">Disponible</span></li>
        <li>Accompagnement Guid'Asso <span class="st soon">En cours</span></li>
      </ul>
      <p class="note" style="margin-top:14px">Documents transmis sur simple demande à <a href="mailto:asso.mavka@gmail.com">asso.mavka@gmail.com</a>.</p>
    </div>
  </section>

  <section class="sand tight">
    <div class="wrap">
      <div class="head" style="margin-bottom:24px"><span class="eyebrow">Ils travaillent déjà avec nous</span><h2>Partenaires et communautés</h2></div>
      <div class="logos" style="gap:36px">
        <img src="/assets/site-img/img-08-3aa13012b1.webp" alt="Commune de Garat" style="height:44px">
        <img src="/assets/site-img/img-09-0214dc1e39.webp" alt="Ville de Soyaux" style="height:44px">
        <img src="/assets/site-img/img-10-0b0315c840.webp" alt="FLEP, centre socio-culturel et sportif de Soyaux" style="height:44px">
        <img src="/assets/site-img/img-11-6a6681c6fb.webp" alt="FCOL, fédération charentaise des œuvres laïques" style="height:44px">
        <img src="/assets/site-img/img-23-3526f012ca.webp" alt="Comité des fêtes et d'animations de Garat" style="height:44px">
        <img src="/assets/site-img/img-24-18553ed39a.webp" alt="HelloAsso" style="height:32px">
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
        <p class="lede">Chaque atelier MAVKA est animé par quelqu'un qui a un vrai métier ou un vrai savoir-faire derrière lui : musiciennes de formation, peintre de Petrykivka, calligraphe, professeure de gymnastique, art-thérapeute. Beaucoup sont arrivés en France récemment et rebâtissent ici leur pratique et leur public.</p>
      </div>
      <div class="team"><?php foreach ($site_intervenants as $iv) { echo render_person_card($iv); } ?></div>
    </div>
  </section>
  <section class="sand">
    <div class="wrap split">
      <div>
        <div class="head" style="margin-bottom:16px"><span class="eyebrow">Pourquoi des bénévoles ?</span><h2>Un cadre pour redémarrer, pas un emploi déguisé</h2></div>
        <p class="lede">Les intervenants commencent bénévoles, le temps de tester leur format et de rencontrer leur public. Leurs frais sont remboursés. Quand un cours trouve son public, il devient une collaboration régulière et rémunérée, fixée par écrit. C'est le Parcours MAVKA.</p>
        <p style="margin-top:18px"><a class="btn btn-primary" href="#intervenants">Découvrir le Parcours</a></p>
      </div>
      <div>
        <div class="head" style="margin-bottom:16px"><span class="eyebrow">Gouvernance</span><h2>Une association collégiale</h2></div>
        <p class="lede">MAVKA est une association loi 1901 à gouvernance collégiale, fondée par trois personnes et présidée par Larysa Mas. Elle est déclarée, assurée, et accompagnée par le dispositif Guid'Asso.</p>
      </div>
    </div>
  </section>
  <section class="tight">
    <div class="wrap cta-band">
      <div>
        <h3>Vous aimeriez rejoindre l'équipe ?</h3>
        <p>Une première rencontre informelle suffit pour commencer. Nous voulons d'abord écouter vos idées.</p>
        <div class="actions"><a class="btn btn-primary" href="#contact">Prendre rendez-vous</a></div>
      </div>
      <svg class="mascot" viewBox="0 0 310 769" style="aspect-ratio:310/769"><use href="#m-stand"/></svg>
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

</main>

<footer>
  <div class="wrap">
    <div class="row">
      <div>
        <a class="brand" href="#accueil"><img src="/assets/site-img/img-01-017fac3c9d.webp" alt=""><b>MAVKA</b></a>
        <p style="margin-top:12px;max-width:26em">Association loi 1901, Charente. Culture, éducation, bien-être et développement personnel, dans sept communes autour d'Angoulême.</p>
      </div>
      <div class="cols">
        <div class="col"><b>Découvrir</b><a href="#agenda">Agenda</a><a href="#activites">Activités</a><a href="#equipe">L'équipe</a></div>
        <div class="col"><b>Rejoindre</b><a href="#intervenants">Devenir intervenant</a><a href="#collectivites">Collectivités</a><a href="#contact">Contact</a></div>
        <div class="col"><b>Nous joindre</b><a href="tel:+33656682153">+33 6 56 68 21 53</a><a href="mailto:asso.mavka@gmail.com">asso.mavka@gmail.com</a><span>Lundi au samedi, 10h à 17h</span></div>
      </div>
    </div>
    <div class="legal">
      <span>© 2026 MAVKA · mavka16.fr</span>
      <a href="https://mavka16.fr/rgpd/">Mentions légales et protection des données</a>
    </div>
  </div>
</footer>

<script>
(function(){
  const routes=['accueil','agenda','activites','intervenants','collectivites','equipe','contact'];
  const alias={ateliers:'agenda'};
  const nav=document.getElementById('nav'), btn=document.getElementById('menuBtn');
  const titles={accueil:'MAVKA',agenda:'Agenda · MAVKA',activites:'Activités · MAVKA',intervenants:'Devenir intervenant · MAVKA',collectivites:'Collectivités · MAVKA',equipe:"L'équipe · MAVKA",contact:'Contact · MAVKA'};
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
// ---- style switch ----
(function(){
  const box=document.getElementById('styles');
  function set(s){ if(s==='vif') document.documentElement.removeAttribute('data-style'); else document.documentElement.setAttribute('data-style',s);
    box.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.style===s)));
    try{localStorage.setItem('mavka-style',s)}catch(e){} }
  let s='vif'; try{s=localStorage.getItem('mavka-style')||'vif'}catch(e){}
  set(s);
  box.addEventListener('click',e=>{const b=e.target.closest('button[data-style]'); if(b) set(b.dataset.style);});
})();
// ---- FR / EN switch ----
(function(){
  const EN={"Navigation principale": "Main navigation", "Ateliers": "Workshops", "Devenir intervenant": "Become a facilitator", "Collectivités": "Local authorities", "L'équipe": "The team", "Contact": "Contact", "Voir les ateliers": "See the workshops", "Menu": "Menu", "Association loi 1901 · Charente": "Registered non-profit · Charente, France", "Des ateliers près de chez vous, portés par des gens qui savent faire.": "Workshops near you, run by people who really know their craft.", "MAVKA aide des personnes talentueuses, souvent nouvellement arrivées en France, à transmettre ce qu'elles savent : musique, arts, langues, bien-être. Dans sept communes autour d'Angoulême.": "MAVKA helps talented people, many of them newly arrived in France, pass on what they know: music, arts, languages, wellbeing. In seven towns around Angoulême.", "Voir les prochains ateliers": "See upcoming workshops", "Proposer un atelier": "Propose a workshop", "Mavka, la mascotte de l'association, saluant de la main": "Mavka, the association's mascot, waving", "7 communes": "7 towns", "11 intervenants bénévoles": "11 volunteer facilitators", "Association déclarée (RNA, JOAFE)": "Officially registered non-profit", "Assurée MAIF": "Insured (MAIF)", "Partenaires": "Partners", "Commune de Garat": "Town of Garat", "Ville de Soyaux": "Town of Soyaux", "Par où commencer": "Where to start", "MAVKA s'adresse à trois types de personnes. Laquelle êtes-vous ?": "MAVKA is for three kinds of people. Which one are you?", "Habitant": "Resident", "Je veux participer à un atelier": "I want to join a workshop", "Musique, peinture, calligraphie, gymnastique douce, art-thérapie. Des groupes à taille humaine, près de chez vous, souvent gratuits sur préinscription.": "Music, painting, calligraphy, gentle gymnastics, art therapy. Small groups, close to home, often free with pre-registration.", "Voir les ateliers →": "See the workshops →", "Enseignant, artiste, praticien": "Teacher, artist, practitioner", "Je veux transmettre ce que je sais": "I want to share what I know", "Vous avez un savoir-faire et vous cherchez un cadre pour le partager. Le Parcours MAVKA vous accompagne en quatre étapes, de la première rencontre à un cours régulier.": "You have a skill and you're looking for a safe setting to share it. The MAVKA Path takes you through four steps, from a first conversation to a regular class.", "Découvrir le Parcours →": "Discover the Path →", "Commune, collectivité": "Town, local authority", "Je représente une collectivité": "I represent a local authority", "Un partenaire simple et progressif : une rencontre, un atelier pilote, puis une construction commune adaptée aux besoins de vos habitants.": "A simple, step-by-step partner: one meeting, one pilot workshop, then something built together around your residents' needs.", "Travailler avec MAVKA →": "Work with MAVKA →", "Agenda": "Calendar", "Prochains rendez-vous": "Coming up", "La préinscription est gratuite et sans engagement. Elle nous permet de savoir combien de personnes attendre.": "Pre-registration is free and non-binding. It simply tells us how many people to expect.", "oct": "Oct", "sept": "Sept", "18h00": "6:00 pm", "14h00": "2:00 pm", "Bien-être · Art-thérapie": "Wellbeing · Art therapy", "Art-Thérapie Évolutive, séance d'initiation": "Evolutionary Art Therapy, taster session", "William Aubert · Salle de temps libre, 16410 Garat": "William Aubert · Salle de temps libre, 16410 Garat", "Une première expérience créative ouverte à tous, dans un cadre bienveillant. Aucun talent artistique n'est nécessaire.": "A first creative experience open to everyone, in a supportive setting. No artistic talent needed.", "Préinscription gratuite": "Free pre-registration", "Événement · Tout public": "Event · All ages", "Festival du jeu de Garat": "Garat Games Festival", "Salle de l'Atrium, 16410 Garat · Entrée libre": "Salle de l'Atrium, 16410 Garat · Free entry", "Jeux de société, jeux géants en extérieur, espace tout-petits, jeux vidéo et démonstrations d'impression 3D. Un « passeport des aventuriers » pour les plus audacieux, et une buvette.": "Board games, giant outdoor games, a toddlers' corner, video games and 3D-printing demos. An \"adventurer's passport\" for the bold, plus snacks and drinks.", "En savoir plus": "Learn more", "jeu. 9h30": "Thu 9:30", "hebdo": "weekly", "Bien-être · Gymnastique": "Wellbeing · Gymnastics", "Gymnastique articulaire, le jeudi matin": "Joint mobility gymnastics, Thursday mornings", "Hanna Sokha · Salle de l'Atrium, 16410 Garat": "Hanna Sokha · Salle de l'Atrium, 16410 Garat", "Intéressé(e) ? Cette préinscription gratuite nous permet de compter les personnes motivées et d'étudier avec la mairie la mise en place des séances.": "Interested? This free pre-registration lets us count who's keen and work out with the town hall whether the sessions can go ahead.", "Je suis intéressé(e)": "I'm interested", "Régulier": "Ongoing", "Grand Angoulême": "Greater Angoulême", "Éducation · Musique": "Education · Music", "Violon, piano, chant et formation musicale": "Violin, piano, singing and music theory", "Snizhana Zhuravlova · enfants et adultes": "Snizhana Zhuravlova · children and adults", "Apprendre, reprendre un instrument ou jouer avec d'autres. Débutants ou musiciens confirmés.": "Learn, pick an instrument back up, or play with others. Beginners and experienced musicians alike.", "Voir sa page": "See her page", "Voir tous les ateliers et les activités régulières →": "See all workshops and ongoing activities →", "Ce que nous proposons": "What we offer", "Quatre directions, une même idée : apprendre les uns des autres.": "Four directions, one idea: learning from each other.", "Culture": "Culture", "Peinture décorative ukrainienne de Petrykivka, calligraphie chinoise, origami, couronnes et objets faits main. Créer de ses mains et découvrir une tradition venue d'ailleurs.": "Ukrainian Petrykivka decorative painting, Chinese calligraphy, origami, handmade wreaths and objects. Make things with your hands and discover a tradition from elsewhere.", "Éducation": "Education", "Musique (violon, piano, chant, formation musicale) avec trois intervenantes. Bientôt : anglais du quotidien et impression 3D.": "Music (violin, piano, singing, music theory) with three teachers. Coming soon: everyday English and 3D printing.", "Bien-être": "Wellbeing", "Gymnastique douce, yoga, mobilité, gestion du stress, espaces de parole. Des outils simples pour prendre soin de soi, en prévention, sans remplacer un suivi médical.": "Gentle gymnastics, yoga, mobility, stress management, talking circles. Simple tools to look after yourself. Preventive, and never a substitute for medical care.", "Développement personnel": "Personal development", "Le Parcours MAVKA et l'art-thérapie : chacun construit son propre projet, accompagné pas à pas, de la découverte à l'autonomie.": "The MAVKA Path and art therapy: everyone builds their own project, supported step by step, from discovery to independence.", "Mavka assise, en train de lire": "Mavka sitting and reading", "Portrait de Mavka": "Portrait of Mavka", "Pourquoi « Mavka » ?": "Why \"Mavka\"?", "Dans le folklore ukrainien, la Mavka est l'esprit protecteur de la forêt.": "In Ukrainian folklore, the Mavka is the protective spirit of the forest.", "Nous avons choisi ce nom parce que MAVKA est née de l'expérience de personnes immigrées, et parce qu'une forêt est un bon modèle : chacun y pousse à son rythme, et tout le monde s'y entraide. La petite pousse sur sa tête, c'est vous.": "We chose the name because MAVKA grew out of the experience of immigrants, and because a forest is a good model: everyone grows at their own pace, and everyone helps each other. The little sprout on her head? That's you.", "Onze bénévoles, onze savoir-faire": "Eleven volunteers, eleven crafts", "Musiciennes, peintres, calligraphe, professeur de gymnastique, art-thérapeute. Des personnes qui ont une pratique réelle et l'envie de la partager.": "Musicians, painters, a calligrapher, a gymnastics teacher, an art therapist. People with a real practice and a wish to share it.", "Rencontrer l'équipe": "Meet the team", "Une question, une idée, un projet ?": "A question, an idea, a project?", "Nous répondons du lundi au samedi, de 10h à 17h. Un appel ou un message suffit pour commencer.": "We answer Monday to Saturday, 10 am to 5 pm. A call or a message is all it takes to start.", "Nous écrire": "Write to us", "Ateliers et événements": "Workshops and events", "Des ateliers à taille humaine, près de chez vous": "Small-group workshops, close to home", "Aucun niveau requis. La plupart des premières séances sont gratuites sur préinscription. Les activités régulières sont ensuite fixées avec l'intervenant et la commune.": "No experience needed. Most first sessions are free with pre-registration. Regular activities are then set up with the facilitator and the town.", "Prochaines dates": "Next dates", "Activités régulières": "Ongoing activities", "Ce qui existe déjà, ce qui arrive bientôt": "What's already running, what's coming soon", "Violon, piano, chant, formation musicale": "Violin, piano, singing, music theory", "Snizhana Zhuravlova · enfants et adultes, débutants ou confirmés": "Snizhana Zhuravlova · children and adults, beginners or experienced", "Apprendre, reprendre un instrument ou jouer avec d'autres. Cours individuels et musique d'ensemble.": "Learn, pick an instrument back up, or play with others. Individual lessons and ensemble playing.", "Voir sa page et se préinscrire": "See her page and pre-register", "Sur demande": "On request", "Peinture de Petrykivka, calligraphie chinoise, créations artisanales": "Petrykivka painting, Chinese calligraphy, handmade crafts", "Des ateliers d'une à deux heures pour créer de ses mains et découvrir une tradition venue d'ailleurs.": "One- to two-hour workshops to make things with your hands and discover a tradition from elsewhere.", "Demander une date": "Ask for a date", "Yoga, gymnastique douce, mobilité, gestion du stress": "Yoga, gentle gymnastics, mobility, stress management", "Hanna Sokha et l'équipe Bien-être": "Hanna Sokha and the Wellbeing team", "Des outils simples pour prendre soin de soi, dans un cadre bienveillant. Prévention et bien-être, sans remplacer un suivi médical.": "Simple tools to look after yourself, in a supportive setting. Prevention and wellbeing, never a substitute for medical care.", "Bientôt": "Soon", "En préparation": "In preparation", "Anglais du quotidien, impression 3D": "Everyday English, 3D printing", "William Aubert et de nouveaux intervenants": "William Aubert and new facilitators", "Deux formats en construction. Laissez vos coordonnées pour être prévenu au lancement.": "Two formats in the works. Leave your details to be told when they launch.", "Être prévenu": "Keep me posted", "Comment ça marche": "How it works", "Trois choses à savoir avant de venir": "Three things to know before you come", "La préinscription est gratuite.": "Pre-registration is free.", "Elle se fait en un clic sur HelloAsso et ne vous engage à rien. Elle nous aide à prévoir la salle et le matériel.": "It takes one click on HelloAsso and commits you to nothing. It helps us plan the room and the materials.", "Aucun talent particulier n'est nécessaire.": "No special talent needed.", "Les ateliers sont pensés pour des débutants. Enfants et adultes sont les bienvenus, sauf mention contraire.": "Workshops are designed for beginners. Children and adults are welcome unless stated otherwise.", "Les séances ont lieu dans des salles communales.": "Sessions take place in town halls and community rooms.", "Garat, Soyaux et Grand Angoulême aujourd'hui, d'autres communes au fur et à mesure.": "Garat, Soyaux and Greater Angoulême today, more towns as we grow.", "Le Parcours MAVKA": "The MAVKA Path", "Vous avez un savoir-faire. Nous vous aidons à en faire un cours.": "You have a skill. We help you turn it into a class.", "Le Parcours s'adresse aux enseignants, artistes, musiciens, professeurs de yoga et praticiens qui traversent une transition professionnelle, souvent après une arrivée en France. Quatre étapes, à votre rythme, dans un cadre sécurisant.": "The Path is for teachers, artists, musicians, yoga teachers and practitioners going through a career transition, often after arriving in France. Four steps, at your pace, in a safe setting.", "Demander une première rencontre": "Ask for a first meeting", "Voir les quatre étapes": "See the four steps", "Mavka pointant vers le haut": "Mavka pointing upwards", "Quatre étapes": "Four steps", "De la première rencontre à un cours régulier": "From a first meeting to a regular class", "Tout commence par une conversation informelle. Nous voulons comprendre vos idées, vos besoins et où vous voulez aller. Ensuite seulement, on structure.": "It all starts with an informal conversation. We want to understand your ideas, your needs and where you want to go. Only then do we put structure around it.", "Immersion et découverte": "Immersion and discovery", "Vous participez à la vie de l'association : forums, fêtes, ateliers, soutien aux autres intervenants. C'est le moment de sentir si nos valeurs et notre rythme vous correspondent.": "You take part in the life of the association: forums, parties, workshops, supporting other facilitators. This is when you find out whether our values and pace suit you.", "Engagement": "Commitment", "Si l'approche vous parle, vous signez la Charte du bénévole et rejoignez officiellement l'équipe. Votre profil apparaît sur le site. Vos frais (déplacements, matériel) sont remboursés.": "If the approach speaks to you, you sign the Volunteer Charter and officially join the team. Your profile goes on the site. Your expenses (travel, materials) are reimbursed.", "Séances d'essai": "Trial sessions", "Vous animez une ou deux séances test. Vous vérifiez le format, le sujet et l'intérêt des participants, puis vous décidez si vous continuez.": "You run one or two trial sessions. You test the format, the topic and participants' interest, then decide whether to carry on.", "Collaboration durable": "Lasting collaboration", "Si votre cours trouve son public, vous devenez intervenant rémunéré. Les modalités sont fixées par écrit et vous lancez un cours régulier au sein de MAVKA.": "If your class finds its audience, you become a paid facilitator. Terms are set in writing and you launch a regular class within MAVKA.", "Ce que MAVKA apporte": "What MAVKA provides", "Un tremplin, pas un contrat de travail": "A springboard, not an employment contract", "Un cadre juridique et assuré pour vos premières séances (association déclarée, responsabilité civile MAIF).": "A legal, insured framework for your first sessions (registered association, MAIF liability insurance).", "Des salles communales et un premier public, grâce à nos partenariats avec les mairies.": "Community rooms and a first audience, thanks to our partnerships with town halls.", "Le remboursement de vos frais engagés dès l'étape 2.": "Reimbursement of your expenses from step 2 onwards.", "Des lettres de recommandation pour la suite de votre parcours professionnel.": "Letters of recommendation for the next stage of your career.", "L'accompagnement de Larysa Mas, présidente de MAVKA, pour construire votre projet pas à pas.": "Guidance from Larysa Mas, president of MAVKA, to build your project step by step.", "Une équipe de onze personnes qui sont passées par là.": "A team of eleven people who have been there.", "Ce que nous attendons": "What we ask", "Une seule chose : rester impliqué": "Just one thing: stay involved", "Les intervenants participent à la vie de l'association au moins une fois par trimestre : un forum, une fête, un atelier gratuit. C'est ce qui fait de MAVKA une communauté et non un simple planning de cours.": "Facilitators take part in the life of the association at least once a quarter: a forum, a party, a free workshop. That's what makes MAVKA a community rather than a timetable.", "Est-ce un contrat de travail ?": "Is this an employment contract?", "Non. La collaboration avec MAVKA repose sur un partenariat et une activité indépendante, avec des accords définis par écrit.": "No. Working with MAVKA is a partnership based on independent activity, with written agreements.", "La participation aux événements est-elle obligatoire ?": "Is taking part in events compulsory?", "Oui, au moins une fois tous les trois mois. Cela peut être un forum, une fête ou un atelier gratuit.": "Yes, at least once every three months. It can be a forum, a party or a free workshop.", "Et si je ne suis pas disponible à un moment donné ?": "What if I'm not available at some point?", "Le dialogue est toujours possible. La participation est organisée à l'avance en tenant compte des possibilités réelles de chacun.": "There's always room to talk. Participation is planned ahead, around what each person can realistically do.", "Prêt à faire le premier pas ?": "Ready to take the first step?", "Nous ne cherchons pas seulement des intervenants. Nous cherchons des partenaires dont nous aiderons à concrétiser les idées.": "We're not just looking for facilitators. We're looking for partners whose ideas we'll help bring to life.", "Prendre rendez-vous": "Book a meeting", "Communes et collectivités": "Towns and local authorities", "Un partenaire local qui commence petit et construit avec vous.": "A local partner that starts small and builds with you.", "MAVKA ne propose pas un modèle figé. Nous commençons par une rencontre, testons un atelier pilote, puis ajustons avec vous selon les besoins réels de vos habitants.": "MAVKA doesn't offer a fixed model. We start with a meeting, test a pilot workshop, then adjust with you around your residents' real needs.", "Organiser un premier échange": "Set up a first conversation", "Consulter nos documents": "See our documents", "Mavka accroupie, une loupe à la main, observant une pousse": "Mavka crouching with a magnifying glass, looking at a sprout", "Notre approche": "Our approach", "Simple, progressif, sans lourdeur": "Simple, gradual, no red tape", "1. Une rencontre": "1. A meeting", "Présentation de l'association, identification des besoins locaux, réflexion sur un premier format d'action.": "We introduce the association, identify local needs and think through a first format of action.", "2. Un atelier pilote": "2. A pilot workshop", "Format court (1 à 2 heures), groupe réduit, approche participative. Une salle communale ponctuelle suffit.": "Short format (1 to 2 hours), small group, participatory approach. A community room for one session is enough.", "3. Une construction commune": "3. Building together", "Échanges réguliers sur les besoins du territoire et ajustement progressif des actions, avec la commune.": "Regular conversations about local needs and gradual adjustment of activities, together with the town.", "Nos axes d'action": "Our focus", "Trois choses que MAVKA apporte à un territoire": "Three things MAVKA brings to a community", "Lien social et interculturel.": "Social and intercultural ties.", "Des espaces de rencontre et de dialogue entre habitants, anciens et nouveaux.": "Places for residents, long-standing and new, to meet and talk.", "Apprentissage et transmission.": "Learning and passing on skills.", "Valoriser des compétences qui existent déjà sur le territoire et encourager le partage de savoirs.": "Making the most of skills that already exist locally and encouraging people to share them.", "Parcours et reconversion.": "Career paths and retraining.", "Un cadre pour que des professionnels en transition expérimentent, apprennent et évoluent.": "A setting where professionals in transition can experiment, learn and grow.", "Où nous en sommes": "Where we are", "Une association en phase de lancement": "An association in its launch phase", "MAVKA est une association loi 1901 déclarée (RNA, JOAFE), à gouvernance collégiale (trois fondateurs), à but non lucratif. Nous constituons notre équipe d'intervenants et développons des formats pilotes à petite échelle, avec un suivi régulier des actions et une communication transparente avec nos partenaires. Nous sommes accompagnés par le dispositif Guid'Asso.": "MAVKA is a registered French non-profit (loi 1901, RNA, JOAFE) with collective governance (three founders). We are building our team of facilitators and developing small-scale pilot formats, with regular follow-up and transparent communication with partners. We are supported by the Guid'Asso programme.", "Documents et cadre": "Documents and framework", "Tout est en ordre, et consultable": "Everything in order, and available", "Pour garantir un fonctionnement clair, voici les documents que nous mettons à disposition des collectivités.": "To keep things clear, here are the documents we make available to local authorities.", "Statuts de l'association": "Articles of association", "Disponible": "Available", "Règlement intérieur": "Internal rules", "Récépissé de déclaration en préfecture": "Prefecture registration receipt", "Publication au JOAFE": "Official journal publication (JOAFE)", "Charte du bénévolat": "Volunteer charter", "Modèles de conventions et d'engagement": "Agreement and commitment templates", "Projet associatif": "Association project", "Attestation d'assurance responsabilité civile (MAIF)": "Liability insurance certificate (MAIF)", "Accompagnement Guid'Asso": "Guid'Asso support programme", "En cours": "In progress", "Documents transmis sur simple demande à": "Documents sent on request to", "Ils travaillent déjà avec nous": "Already working with us", "Partenaires et communautés": "Partners and communities", "FLEP, centre socio-culturel et sportif de Soyaux": "FLEP, Soyaux community and sports centre", "FCOL, fédération charentaise des œuvres laïques": "FCOL, Charente federation of secular organisations", "Comité des fêtes et d'animations de Garat": "Garat festivities committee", "Onze bénévoles qui ont une pratique réelle et l'envie de la partager": "Eleven volunteers with a real practice and the wish to share it", "MAVKA soutient des professionnels talentueux, enseignants, artistes, musiciens, praticiens, qui traversent une période de transition. Nous les aidons à retrouver leur voix, leur public et leur confiance, tout en enrichissant la vie culturelle du territoire.": "MAVKA supports talented professionals, teachers, artists, musicians and practitioners, going through a period of transition. We help them find their voice, their audience and their confidence again, while enriching local cultural life.", "Présidente": "President", "Développement personnel, accompagnement des intervenants, Parcours MAVKA": "Personal development, facilitator support, the MAVKA Path", "Bénévole": "Volunteer", "Art-thérapie évolutive, impression 3D": "Evolutionary art therapy, 3D printing", "Calligraphie chinoise, origami, langue chinoise": "Chinese calligraphy, origami, Chinese language", "Peinture décorative ukrainienne de Petrykivka": "Ukrainian Petrykivka decorative painting", "Violon, piano, chant, formation musicale, musique d'ensemble": "Violin, piano, singing, music theory, ensemble playing", "Musique": "Music", "Créations artisanales, couronnes décoratives, objets faits main": "Handmade crafts, decorative wreaths, handmade objects", "Gymnastique articulaire, bien-être": "Joint mobility gymnastics, wellbeing", "Membre de l'équipe": "Team member", "Vous aimeriez rejoindre l'équipe ?": "Would you like to join the team?", "Le Parcours MAVKA accompagne chaque nouvel intervenant en quatre étapes, de la première rencontre au cours régulier.": "The MAVKA Path supports every new facilitator in four steps, from a first meeting to a regular class.", "Découvrir le Parcours": "Discover the Path", "Pour vous informer sur un atelier, proposer une idée, rejoindre l'équipe ou construire un partenariat. Ou simplement échanger.": "To ask about a workshop, suggest an idea, join the team or build a partnership. Or just to talk.", "Téléphone": "Phone", "Horaires": "Hours", "Lundi au samedi, 10h à 17h": "Monday to Saturday, 10 am to 5 pm", "E-mail": "Email", "Territoire": "Area", "Sept communes autour d'Angoulême, Charente (16)": "Seven towns around Angoulême, Charente (16), France", "Réseaux sociaux": "Social media", "Prénom et nom": "First and last name", "Je vous écris pour": "I'm writing about", "Participer à un atelier": "Joining a workshop", "Proposer un atelier ou rejoindre l'équipe": "Proposing a workshop or joining the team", "Un partenariat avec une commune": "A partnership with a town", "Autre chose": "Something else", "Message": "Message", "J'accepte d'être contacté(e) par l'association MAVKA concernant ma demande.": "I agree to be contacted by MAVKA about my request.", "Envoyer": "Send", "Vos données sont utilisées uniquement pour répondre à votre demande et ne sont pas transmises à des tiers. Vous pouvez exercer vos droits d'accès, de rectification et de suppression en écrivant à asso.mavka@gmail.com.": "Your data is used only to answer your request and is never shared with third parties. You can exercise your rights of access, correction and deletion by writing to asso.mavka@gmail.com.", "Association loi 1901, Charente. Culture, éducation, bien-être et développement personnel, dans sept communes autour d'Angoulême.": "Registered non-profit, Charente, France. Culture, education, wellbeing and personal development in seven towns around Angoulême.", "Découvrir": "Discover", "Pourquoi « Mavka »": "Why \"Mavka\"", "Rejoindre": "Join", "Nous joindre": "Reach us", "Mentions légales et protection des données": "Legal notice and data protection", "Message envoyé": "Message sent", "Activités": "Activities", "Des ateliers pour petits et grands, près de chez vous.": "Workshops for kids and grown-ups, close to home.", "Musique, arts, langues, bien-être. Des groupes à taille humaine dans sept communes autour d'Angoulême, animés par des personnes qui savent faire. La première séance est souvent gratuite.": "Music, arts, languages, wellbeing. Small groups in seven towns around Angoulême, run by people who really know their craft. The first session is often free.", "Voir les prochaines dates": "See upcoming dates", "Découvrir les activités": "Explore the activities", "Trois rendez-vous à venir. La préinscription est gratuite et sans engagement.": "Three upcoming dates. Pre-registration is free and non-binding.", "Voir tout l'agenda": "See the full calendar", "Comment participer": "How to take part", "Trois étapes, et c'est tout": "Three steps, that's all", "Choisissez une date": "Pick a date", "Dans l'agenda, chaque atelier indique la commune, la salle, l'horaire et pour qui il est fait.": "In the calendar, every workshop shows the town, the room, the time and who it's for.", "Préinscrivez-vous gratuitement": "Pre-register for free", "Un clic sur HelloAsso. Cela ne vous engage à rien, cela nous aide à prévoir la salle et le matériel.": "One click on HelloAsso. It commits you to nothing; it helps us plan the room and materials.", "Venez comme vous êtes": "Come as you are", "Aucun niveau requis, aucun matériel à apporter sauf mention contraire. Enfants et adultes bienvenus.": "No experience needed, nothing to bring unless stated. Children and adults welcome.", "En images": "In pictures", "À quoi ressemble un atelier MAVKA": "What a MAVKA workshop looks like", "Photo à ajouter": "Photo to add", "Photo d'illustration, à remplacer": "Stock photo, to be replaced", "Calligraphie et origami": "Calligraphy and origami", "Enfants et parents au Festival du jeu, Garat": "Kids and parents at the Games Festival, Garat", "Atelier de peinture Petrykivka": "Petrykivka painting workshop", "Cours de violon, Grand Angoulême": "Violin lesson, Greater Angoulême", "Calligraphie chinoise et origami": "Chinese calligraphy and origami", "Gymnastique douce, salle de l'Atrium": "Gentle gymnastics, Salle de l'Atrium", "Habitant, famille": "Resident, family", "Je cherche une activité": "I'm looking for an activity", "Musique, peinture, calligraphie, gymnastique douce, art-thérapie. Pour les enfants, les adultes, ou les deux ensemble.": "Music, painting, calligraphy, gentle gymnastics, art therapy. For children, adults, or both together.", "Voir les activités →": "See the activities →", "Vous avez un savoir-faire et vous cherchez un cadre pour le partager. Le Parcours MAVKA vous accompagne en quatre étapes.": "You have a skill and you're looking for a safe setting to share it. The MAVKA Path takes you through four steps.", "Un partenaire simple et progressif : une rencontre, un atelier pilote, puis une construction commune avec vos habitants.": "A simple, step-by-step partner: one meeting, one pilot workshop, then something built together with your residents.", "Nos activités": "Our activities", "Tout voir, avec les infos pratiques →": "See everything, with practical details →", "Des ateliers animés par des gens qui ont vraiment une pratique": "Workshops run by people with a real practice", "Musiciennes, peintres, calligraphe, professeure de gymnastique, art-thérapeute. Onze bénévoles, chacun avec un vrai métier ou un vrai savoir-faire derrière lui.": "Musicians, painters, a calligrapher, a gymnastics teacher, an art therapist. Eleven volunteers, each with a real profession or craft behind them.", "MAVKA est née de l'expérience de personnes arrivées en France avec un métier, un art ou un savoir-faire, et de l'envie de le partager avec leurs voisins. Une forêt est un bon modèle : chacun y pousse à son rythme, et tout le monde s'y entraide. La petite pousse sur sa tête, c'est vous.": "MAVKA grew out of the experience of people who arrived in France with a profession, an art or a craft, and the wish to share it with their neighbours. A forest is a good model: everyone grows at their own pace, and everyone helps each other. The little sprout on her head? That's you.", "Toutes les prochaines dates": "All upcoming dates", "Chaque rendez-vous indique la commune, la salle et pour qui il est fait. La préinscription est gratuite et sans engagement.": "Every date shows the town, the room and who it's for. Pre-registration is free and non-binding.", "Pas de date qui vous convient ?": "No date that suits you?", "Plusieurs activités se font sur demande ou en cours réguliers : musique, arts, bien-être. Dites-nous ce qui vous intéresse et dans quelle commune, nous organisons une date dès qu'un petit groupe est réuni.": "Several activities run on request or as regular classes: music, arts, wellbeing. Tell us what interests you and in which town, and we'll set a date as soon as a small group comes together.", "Voir les activités": "See the activities", "Toutes les activités sont ouvertes aux débutants. La plupart accueillent les enfants comme les adultes, et certaines sont pensées pour les deux ensemble.": "All activities are open to beginners. Most welcome children as well as adults, and some are designed for both together.", "Pour qui": "Who for", "Enfants et adultes": "Children and adults", "L'âge minimum est indiqué sur chaque atelier.": "The minimum age is shown on each workshop.", "Combien": "How much", "Gratuit pour commencer": "Free to start", "Les premières séances sont gratuites sur préinscription. Les cours réguliers ont un tarif fixé avec l'intervenant.": "First sessions are free with pre-registration. Regular classes have a fee set with the facilitator.", "Où": "Where", "Garat, Soyaux, Grand Angoulême": "Garat, Soyaux, Greater Angoulême", "Dans des salles communales, d'autres communes au fur et à mesure.": "In community rooms, with more towns as we grow.", "Quoi apporter": "What to bring", "Rien": "Nothing", "Le matériel est fourni, sauf mention contraire sur l'atelier.": "Materials are provided unless the workshop says otherwise.", "Créer de ses mains, découvrir une tradition": "Make things by hand, discover a tradition", "1 à 2 h": "1 to 2 h", "Culture · Enfants et adultes": "Culture · Children and adults", "Peinture de Petrykivka": "Petrykivka painting", "La peinture décorative ukrainienne aux motifs floraux, transmise de génération en génération.": "Ukrainian decorative painting with floral motifs, passed down through generations.", "Une initiation à l'écriture au pinceau, au pliage et aux premiers mots de chinois.": "An introduction to brush writing, paper folding and first words of Chinese.", "Culture · Familles": "Culture · Families", "Créations artisanales": "Handmade crafts", "Couronnes décoratives et objets faits main, à emporter chez soi.": "Decorative wreaths and handmade objects to take home.", "Apprendre un savoir-faire concret": "Learn a practical skill", "Ateliers de musique": "Music workshops", "Découvrir un instrument, progresser à son rythme ou simplement partager le plaisir de jouer.": "Discover an instrument, progress at your own pace, or simply share the joy of playing.", "Prendre soin de soi, simplement": "Looking after yourself, simply", "Bien-être · Adultes": "Wellbeing · Adults", "Gymnastique articulaire": "Joint mobility gymnastics", "Hanna Sokha · Salle de l'Atrium": "Hanna Sokha · Salle de l'Atrium", "Mobilité et gymnastique douce le jeudi matin. Préinscription gratuite pour ouvrir le créneau avec la mairie.": "Mobility and gentle gymnastics on Thursday mornings. Free pre-registration to open the slot with the town hall.", "Adultes": "Adults", "Yoga, mobilité, gestion du stress, espaces de parole": "Yoga, mobility, stress management, talking circles", "L'équipe Bien-être": "The Wellbeing team", "Des outils simples pour retrouver son équilibre. Prévention et bien-être, sans remplacer un suivi médical.": "Simple tools to find your balance again. Prevention and wellbeing, never a substitute for medical care.", "William Aubert · Salle de temps libre, Garat": "William Aubert · Salle de temps libre, Garat", "Une première expérience créative ouverte à tous. Aucun talent artistique n'est nécessaire.": "A first creative experience open to everyone. No artistic talent needed.", "Construire son propre projet": "Build your own project", "Cette direction s'adresse aux personnes qui veulent transmettre : le Parcours MAVKA accompagne chacun de l'idée au cours régulier, avec l'appui de Larysa Mas, présidente de l'association, et des approches comme l'art-thérapie.": "This direction is for people who want to teach: the MAVKA Path supports each person from idea to regular class, with the guidance of Larysa Mas, president of the association, and approaches such as art therapy.", "Les ateliers, ce sont d'abord des personnes.": "Workshops are people first.", "Chaque atelier MAVKA est animé par quelqu'un qui a un vrai métier ou un vrai savoir-faire derrière lui : musiciennes de formation, peintre de Petrykivka, calligraphe, professeure de gymnastique, art-thérapeute. Beaucoup sont arrivés en France récemment et rebâtissent ici leur pratique et leur public.": "Every MAVKA workshop is run by someone with a real profession or craft behind them: trained musicians, a Petrykivka painter, a calligrapher, a gymnastics teacher, an art therapist. Many arrived in France recently and are rebuilding their practice and their audience here.", "Voir sa page →": "See their page →", "Bien-être · Éducation": "Wellbeing · Education", "Éducation · Grand Angoulême": "Education · Greater Angoulême", "Bien-être · Garat": "Wellbeing · Garat", "Pourquoi des bénévoles ?": "Why volunteers?", "Un cadre pour redémarrer, pas un emploi déguisé": "A framework for a fresh start, not a disguised job", "Les intervenants commencent bénévoles, le temps de tester leur format et de rencontrer leur public. Leurs frais sont remboursés. Quand un cours trouve son public, il devient une collaboration régulière et rémunérée, fixée par écrit. C'est le Parcours MAVKA.": "Facilitators start as volunteers while they test their format and meet their audience. Their expenses are reimbursed. When a class finds its audience, it becomes a regular, paid collaboration, set out in writing. That's the MAVKA Path.", "Gouvernance": "Governance", "Une association collégiale": "A collectively run association", "MAVKA est une association loi 1901 à gouvernance collégiale, fondée par trois personnes et présidée par Larysa Mas. Elle est déclarée, assurée, et accompagnée par le dispositif Guid'Asso.": "MAVKA is a registered French non-profit with collective governance, founded by three people and chaired by Larysa Mas. It is registered, insured, and supported by the Guid'Asso programme.", "Une première rencontre informelle suffit pour commencer. Nous voulons d'abord écouter vos idées.": "An informal first meeting is all it takes to start. We want to hear your ideas first.", "Passer en français": "Switch to French", "Style du site": "Site style", "Vif": "Bright", "Forêt": "Forest", "Lin": "Linen", "Switch language": "Switch language"};
  const attrs=['alt','placeholder','aria-label','title'];
  const store=[];
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT,{acceptNode:n=>/\S/.test(n.nodeValue)&&!['SCRIPT','STYLE'].includes(n.parentNode.nodeName)?1:2});
  let n; while(n=walker.nextNode()){ store.push({node:n,fr:n.nodeValue}); }
  document.querySelectorAll('*').forEach(el=>attrs.forEach(a=>{ if(el.hasAttribute(a)) store.push({el,attr:a,fr:el.getAttribute(a)}); }));
  const btn=document.getElementById('langBtn');
  function apply(lang){
    store.forEach(s=>{
      const key=s.fr.replace(/\s+/g,' ').trim(); const en=EN[key];
      const v=(lang==='en'&&en)?s.fr.replace(key,en):s.fr;
      if(s.node) s.node.nodeValue=v; else s.el.setAttribute(s.attr,v);
    });
    document.documentElement.lang=lang; btn.textContent=lang==='en'?'FR':'EN';
    btn.setAttribute('aria-label',lang==='en'?'Passer en français':'Switch to English');
    try{localStorage.setItem('mavka-lang',lang)}catch(e){}
  }
  let lang='fr'; try{lang=localStorage.getItem('mavka-lang')||'fr'}catch(e){}
  apply(lang);
  btn.addEventListener('click',()=>apply(document.documentElement.lang==='en'?'fr':'en'));
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
