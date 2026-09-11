<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$a = [
    'titre' => '', 'categorie' => 'Culture', 'categorie_display' => '',
    'format' => '', 'public' => '', 'description' => '',
    'date_debut' => '', 'heure' => '', 'recurrence' => '',
    'lieu' => '', 'nombre_places' => '', 'ville' => '', 'texte_bouton' => 'Préinscription gratuite',
    'lien_inscription' => '', 'photo' => null, 'statut' => 'publie', 'statut_activite' => 'ouvert',
    'visible_accueil' => 1, 'ordre' => 0,
];
$selected_intervenants = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM activites WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Activité introuvable.'); }
    $a = $found;
    $stmt = db()->prepare('SELECT intervenant_id FROM activite_intervenant WHERE activite_id = ?');
    $stmt->execute([$id]);
    $selected_intervenants = array_column($stmt->fetchAll(), 'intervenant_id');
}

$intervenants = db()->query('SELECT * FROM intervenants WHERE actif = 1 ORDER BY nom')->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a['titre'] = trim($_POST['titre'] ?? '');
    $a['categorie'] = trim($_POST['categorie'] ?? '');
    $a['categorie_display'] = trim($_POST['categorie_display'] ?? '');
    $a['format'] = trim($_POST['format'] ?? '') ?: null;
    $a['public'] = trim($_POST['public'] ?? '') ?: null;
    $a['description'] = trim($_POST['description'] ?? '');
    $a['date_debut'] = $_POST['date_debut'] ?: null;
    $a['heure'] = trim($_POST['heure'] ?? '');
    $a['lieu'] = trim($_POST['lieu'] ?? '');
    // Individuel = pas de "nombre de places" (ça ne s'applique qu'aux activités collectives).
    $a['nombre_places'] = ($a['format'] !== 'Individuel' && $_POST['nombre_places'] !== '') ? (int)$_POST['nombre_places'] : null;
    $a['ville'] = trim($_POST['ville'] ?? '');
    $a['texte_bouton'] = trim($_POST['texte_bouton'] ?? '') ?: 'Préinscription gratuite';
    // La récurrence n'a de sens que pour un "Événement régulier" — pour tout autre
    // texte de bouton le champ est masqué côté formulaire, donc on ignore aussi
    // toute valeur envoyée pour ne pas garder une récurrence fantôme en base.
    $a['recurrence'] = $a['texte_bouton'] === 'Événement régulier' ? trim($_POST['recurrence'] ?? '') : '';
    $a['statut'] = $_POST['statut'] === 'brouillon' ? 'brouillon' : 'publie';
    $a['statut_activite'] = in_array($_POST['statut_activite'] ?? '', ['ouvert', 'complet', 'annule', 'termine'])
        ? $_POST['statut_activite'] : 'ouvert';
    $a['visible_accueil'] = isset($_POST['visible_accueil']) ? 1 : 0;
    $a['ordre'] = (int)($_POST['ordre'] ?? 0);
    $posted_intervenants = array_map('intval', $_POST['intervenants'] ?? []);

    // "Voir sa page" renvoie vers la page "Notre équipe" du·de la première personne cochée
    // ci-dessous, plutôt qu'un lien saisi à la main — pas encore de lien pour "Événement régulier"
    // ou "Gratuit" par exemple, seul ce bouton a besoin d'une page volontaire précise.
    if ($a['texte_bouton'] === 'Voir sa page') {
        $premier_intervenant = null;
        foreach ($intervenants as $iv) {
            if (in_array($iv['id'], $posted_intervenants, true)) { $premier_intervenant = $iv; break; }
        }
        $a['lien_inscription'] = $premier_intervenant ? intervenant_page_url((int)$premier_intervenant['id']) : '';
    } else {
        $a['lien_inscription'] = trim($_POST['lien_inscription'] ?? '');
    }

    $new_photo = handle_upload('photo', 'activites');
    if ($new_photo) {
        $a['photo'] = $new_photo;
    }

    if ($a['titre'] === '') {
        $error = 'Le titre est obligatoire.';
    } else {
        if ($id) {
            $stmt = db()->prepare('UPDATE activites SET titre=?, categorie=?, categorie_display=?, format=?, public=?, description=?, date_debut=?, heure=?, recurrence=?, lieu=?, nombre_places=?, ville=?, texte_bouton=?, lien_inscription=?, photo=?, statut=?, statut_activite=?, visible_accueil=?, ordre=? WHERE id=?');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['visible_accueil'], $a['ordre'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO activites (titre, categorie, categorie_display, format, public, description, date_debut, heure, recurrence, lieu, nombre_places, ville, texte_bouton, lien_inscription, photo, statut, statut_activite, visible_accueil, ordre) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['visible_accueil'], $a['ordre']]);
            $id = (int)db()->lastInsertId();
        }

        db()->prepare('DELETE FROM activite_intervenant WHERE activite_id = ?')->execute([$id]);
        $ins = db()->prepare('INSERT INTO activite_intervenant (activite_id, intervenant_id) VALUES (?, ?)');
        foreach ($posted_intervenants as $iid) {
            $ins->execute([$id, $iid]);
        }

        header('Location: /admin/activites.php?ok=1');
        exit;
    }
}

admin_header($id ? 'Modifier l\'activité' : 'Nouvelle activité', $user, 'activites');
?>
<h1><?= $id ? "Modifier l'activité" : 'Nouvelle activité' ?></h1>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<div class="mavka-activite-layout">
<form method="post" enctype="multipart/form-data" class="mavka-form" style="display:contents;">

  <div class="mavka-activite-col mavka-activite-col--card">
  <aside class="mavka-activite-preview">
    <div class="mavka-activite-preview__label">Carte publique — modifiable directement ici</div>

    <!-- Mêmes classes que includes/site_functions.php → render_event_card() (assets/event-card.css) :
         ce qui est assemblé ici est exactement ce qui s'affiche sur le site, avec des champs de
         saisie en plus par-dessus les mêmes éléments — jamais une carte "admin" différente. -->
    <div class="event">
      <label for="f_photo" class="cover ap-editable-cover">
        <img id="pv_photo" alt=""
             <?= !empty($a['photo']) ? 'src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '"' : 'hidden' ?>>
        <span id="pv_badge_categorie" class="corner corner--tl" title="Catégorie"></span>
        <span id="pv_badge_format" class="corner corner--tr" title="Format"></span>
        <span id="pv_badge_public" class="corner corner--br" title="Public"></span>
        <span id="pv_badge_places" class="corner corner--br2" title="Places"></span>
        <span class="ap-editable-cover__pencil"></span>
      </label>
      <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp" hidden>

      <div class="strip">
        <div id="pv_strip_badge" class="strip__badge">
          <span id="pv_strip_date" class="strip__date"></span>
          <span id="pv_strip_time" class="strip__time"></span>
        </div>
        <div class="strip__loc ap-strip__fields">
          <div class="ap-strip__inputs">
            <input type="date" id="f_date_debut" name="date_debut" class="ap-input" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
            <input type="text" id="f_heure" name="heure" class="ap-input ap-input--heure" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
          </div>
          <input type="text" id="f_recurrence" name="recurrence" class="ap-input" placeholder='Récurrence, ex. "Le jeudi"' value="<?= htmlspecialchars($a['recurrence']) ?>" <?= $a['texte_bouton'] === 'Événement régulier' ? '' : 'hidden' ?>>
          <div class="ap-strip__inputs">
            <input type="text" id="f_lieu" name="lieu" class="ap-input ap-input--wide" placeholder="Lieu" value="<?= htmlspecialchars($a['lieu']) ?>">
            <input type="text" id="f_ville" name="ville" class="ap-input" placeholder="Ville" value="<?= htmlspecialchars($a['ville']) ?>">
          </div>
        </div>
      </div>

      <div class="event-row">
        <div class="event-title-row">
          <div id="pv_avatars" class="event-avatars"></div>
          <textarea id="f_titre" name="titre" rows="1" class="ap-input ap-titre" placeholder="Titre de l'activité" required><?= htmlspecialchars($a['titre']) ?></textarea>
        </div>

        <textarea id="f_description" name="description" class="ap-input ap-desc" placeholder="Description de l'activité"><?= htmlspecialchars($a['description']) ?></textarea>

        <?php
          $boutons = ['Préinscription gratuite', 'Préinscription', 'Gratuit', 'Événement régulier', 'En savoir plus', 'Voir sa page'];
          if ($a['texte_bouton'] && !in_array($a['texte_bouton'], $boutons)) {
              array_unshift($boutons, $a['texte_bouton']); // garde l'ancienne valeur personnalisée si elle ne fait pas partie de la liste
          }
        ?>
        <label class="mavka-activite-preview__btn-label">Texte du bouton<sup class="mavka-footnote-ref">2</sup></label>
        <select id="f_texte_bouton" name="texte_bouton" class="btn btn-primary btn-sm ap-btn-select">
          <?php foreach ($boutons as $b): ?>
          <option value="<?= htmlspecialchars($b) ?>" <?= $a['texte_bouton'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </aside>

  <div class="mavka-activite-actions">
    <button type="submit" class="mavka-btn mavka-btn--primary">Enregistrer</button>
    <a href="/admin/activites.php" class="mavka-btn">Annuler</a>
  </div>
  </div>

  <div class="mavka-activite-col mavka-activite-col--details">
  <details class="mavka-form-section mavka-form-section--parametres" open>
    <summary class="mavka-form-section__header" style="cursor:pointer;">
      <h3 class="mavka-form-section__title">⚙️ Détails supplémentaires</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Classement, capacité, inscription — n'apparaissent pas dans la carte ci-contre, modifiable directement.</p>

    <div class="row">
      <div>
        <label>Catégorie</label>
        <select id="f_categorie" name="categorie">
          <?php foreach (['Culture', 'Éducation', 'Bien-être', 'Développement personnel', 'Événementiel'] as $cat): ?>
          <option value="<?= $cat ?>" <?= $a['categorie'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Sous-titre affiché (facultatif)</label>
        <input type="text" id="f_categorie_display" name="categorie_display" placeholder="Nouveau cours" value="<?= htmlspecialchars($a['categorie_display'] ?? '') ?>">
        <p class="mavka-form-section__hint">Plashka en haut à gauche de la photo, pour signaler quelque chose (ex. « Nouveau cours »). Vide = pas de plashka.</p>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Type d'activité</label>
        <select id="f_format" name="format">
          <option value="">—</option>
          <?php foreach (['Collectif', 'Individuel'] as $f): ?>
          <option value="<?= $f ?>" <?= $a['format'] === $f ? 'selected' : '' ?>><?= $f ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Public</label>
        <select id="f_public" name="public">
          <option value="">—</option>
          <?php foreach (['Enfant', 'Familial', 'Adultes'] as $p): ?>
          <option value="<?= $p ?>" <?= $a['public'] === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Statut de l'activité</label>
        <select name="statut_activite">
          <?php $statuts = ['ouvert' => 'Ouvert', 'complet' => 'Complet', 'annule' => 'Annulé', 'termine' => 'Terminé']; ?>
          <?php foreach ($statuts as $val => $label): ?>
          <option value="<?= $val ?>" <?= $a['statut_activite'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Statut de publication</label>
        <select name="statut">
          <option value="publie" <?= $a['statut'] === 'publie' ? 'selected' : '' ?>>Publié</option>
          <option value="brouillon" <?= $a['statut'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
        </select>
      </div>
    </div>

    <div>
      <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
        <input type="checkbox" name="visible_accueil" value="1" style="width:auto;" <?= ($a['visible_accueil'] ?? 1) ? 'checked' : '' ?>>
        Afficher sur la page d'accueil
      </label>
      <p class="mavka-form-section__hint" style="margin-top:4px;">Décoche pour une activité réelle et réservable (vraie carte, vrai bouton), mais visible seulement sur la page du·de la volontaire qui la propose — pas dans la grille de l'accueil. Utile pour une proposition à l'essai (ex. pour sonder l'intérêt avant d'en parler à une mairie).</p>
    </div>

    <div class="row mavka-row--nombres">
      <div id="f_nombre_places_field">
        <label>Nombre de places <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(vide = illimité)</span></label>
        <input type="number" min="0" id="f_nombre_places" class="mavka-input-court" name="nombre_places" value="<?= htmlspecialchars((string)($a['nombre_places'] ?? '')) ?>">
        <p class="mavka-form-section__hint" id="f_nombre_places_hint" style="margin-top:4px; display:none;">Ne s'applique pas à un Type d'activité « Individuel ».</p>
      </div>
      <div>
        <label>Ordre d'affichage <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(0 = premier)</span><sup class="mavka-footnote-ref">1</sup></label>
        <input type="number" class="mavka-input-court" name="ordre" value="<?= (int)$a['ordre'] ?>">
      </div>
    </div>

    <div>
      <div id="f_lien_inscription_wrap">
        <label>Lien d'inscription</label>
        <input type="url" id="f_lien_inscription" name="lien_inscription" placeholder="https://helloasso.com/..." value="<?= htmlspecialchars($a['lien_inscription']) ?>">
      </div>
      <div id="f_lien_equipe_hint" class="mavka-form-section__hint" style="margin:14px 0 0; display:none;">
        <label>Lien d'inscription</label>
        Généré automatiquement — page "Notre équipe" de la première personne cochée ci-dessous.
      </div>
    </div>

    <label>Intervenant·e·s</label>
    <div class="mavka-picklist">
      <?php if (!$intervenants): ?>
      <div style="padding:10px 12px; color:var(--mavka-color-text-muted); font-size:13.5px;">
        Aucun intervenant enregistré — <a href="/admin/intervenants.php">en ajouter un</a>.
      </div>
      <?php endif; ?>
      <?php foreach ($intervenants as $iv):
        $iv_photo_url = ($iv['photo'] && $iv['dossier']) ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']) : '';
      ?>
      <label class="mavka-picklist__item">
        <input type="checkbox" name="intervenants[]" value="<?= $iv['id'] ?>" data-nom="<?= htmlspecialchars($iv['nom']) ?>" data-photo="<?= htmlspecialchars($iv_photo_url) ?>"
          <?= in_array($iv['id'], $selected_intervenants) ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($iv['nom']) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    </div>
  </details>

  <div class="mavka-activite-footnotes">
    <p><sup class="mavka-footnote-ref">1</sup> L'ordre détermine qui apparaît en premier sur le site (0, puis 1, 2...) ; à ordre égal, la date la plus proche passe devant. Aucun calcul automatique — c'est une priorité manuelle.</p>
    <p><sup class="mavka-footnote-ref">2</sup> Critère de choix du texte du bouton :</p>
    <ul>
      <li><strong>Préinscription gratuite</strong> — date libre, sert à constituer une liste pour la mairie (typiquement 2 séances test).</li>
      <li><strong>Préinscription</strong> — date fixée, places limitées : on compte les inscrits (payant ou gratuit selon la description/HelloAsso).</li>
      <li><strong>Gratuit</strong> — entrée libre, grandes salles / événements ouverts, pas de liste à tenir.</li>
      <li><strong>Événement régulier</strong> — pas de date unique (ex. "1er et 3e mercredi du mois") — voir Récurrence.</li>
      <li><strong>En savoir plus</strong> — partenaires / événements ouverts qui intéressent l'association.</li>
      <li><strong>Voir sa page</strong> — renvoie vers la page du volontaire/intervenant·e plutôt que vers une inscription.</li>
    </ul>
  </div>
  </div>

</form>
</div>

<style>
/* @import doit précéder toute autre règle dans une feuille de style, sinon les navigateurs
   l'ignorent silencieusement (aucune erreur, aucune requête réseau) — donc ces deux imports
   viennent en tout premier, avant même les règles de mise en page ci-dessous. */
@import url('https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap');
@import url('/assets/event-card.css?v=<?= @filemtime(__DIR__ . '/../assets/event-card.css') ?: time() ?>');

.mavka-activite-layout { display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
.mavka-input-court { max-width: 90px; }
.mavka-activite-col { display: flex; flex-direction: column; gap: 20px; }
.mavka-activite-col--card { width: 560px; flex-shrink: 0; }
.mavka-activite-col--details { flex: 1 1 380px; max-width: 640px; }
.mavka-activite-col--details .mavka-form-section { margin-bottom: 0; }
.mavka-activite-actions { display: flex; gap: 10px; }
.mavka-footnote-ref { color: var(--mavka-color-danger-text); font-weight: 700; margin-left: 1px; }
.mavka-activite-footnotes {
  font-size: 12.5px; color: var(--mavka-color-text-muted); line-height: 1.6;
  border-top: 1.5px solid var(--mavka-color-cream-soft); padding-top: 14px;
}
.mavka-activite-footnotes p { margin: 0 0 6px; }
.mavka-activite-footnotes ul { margin: 0; padding-left: 20px; }
.mavka-activite-footnotes li { margin-bottom: 3px; }
.mavka-activite-footnotes strong { color: var(--mavka-color-text); }
.mavka-activite-preview__btn-label {
  display: block; font-size: 12.5px; font-weight: 700; color: var(--mavka-color-text-muted); margin: 0 0 6px;
}
.mavka-form-section--parametres { --section-color: var(--mavka-color-purple-dark); }

/* La carte publique réutilise les classes exactes de assets/event-card.css (.event, .cover,
   .corner, .strip, .event-row...) — le fichier partagé avec includes/site_functions.php →
   render_event_card(). Ici on ajoute UNIQUEMENT ce qu'il faut pour rendre certains de ses
   éléments modifiables sur place ; on ne redéfinit jamais leur apparence. */
.mavka-activite-preview { width: 100%; }
.mavka-activite-preview__label { font-weight: 700; font-size: 13.5px; color: var(--mavka-color-text-muted); margin-bottom: 10px; }

.ap-editable-cover { display: block; cursor: pointer; }
.ap-editable-cover img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
/* Le crayon vit au centre, en overlay au survol — pas dans un coin, pour ne pas se battre
   avec les pastilles Catégorie/Format/Public/Places qui occupent déjà les 4 coins. */
.ap-editable-cover__pencil {
  position: absolute; inset: 0; z-index: 2; display: flex; align-items: center; justify-content: center;
  gap: 8px; background: rgba(30,42,58,0); color: #fff; font-size: 13px; font-weight: 700;
  opacity: 0; transition: opacity .15s, background-color .15s;
}
.ap-editable-cover__pencil::before { content: "✎"; font-size: 16px; }
.ap-editable-cover__pencil::after { content: "Changer la photo"; }
.ap-editable-cover:hover .ap-editable-cover__pencil { opacity: 1; background: rgba(30,42,58,.45); }
/* Catégorie/Format/Public/Places restent des <span> vides tant que rien n'est saisi — .corner:empty
   (event-card.css) les masque automatiquement, donc rien à faire ici pour ce cas. */

/* Le pavé carré (grand format, cf. .strip__date/.strip__time) reste en lecture seule — les
   vrais champs de saisie (date/heure/lieu/ville) vivent à côté sur le pâle, en plus petit,
   visiblement éditables, plutôt que de essayer de "repeindre" un <input type=date> natif en
   gros chiffre. .ap-strip__fields prend la place de .strip__loc (texte) côté site — même
   emplacement, mais avec des champs dedans plutôt que du texte figé. */
.mavka-activite-preview .ap-strip__fields { opacity: 1; gap: 6px; }
.mavka-activite-preview .ap-strip__inputs { display: flex; align-items: center; gap: 6px; opacity: .85; font-size: .85rem; }
.mavka-activite-preview .ap-input {
  font: inherit; border: none; border-radius: 6px; background: transparent; padding: 3px 5px; min-width: 0;
}
.mavka-activite-preview .ap-input:hover, .mavka-activite-preview .ap-input:focus { background: rgba(255,255,255,.55); outline: none; }
/* Chaque champ de .ap-strip__inputs doit avoir SA PROPRE règle flex/width ici — sinon celui
   qui n'en a pas hérite de ".mavka-form input{width:100%}" (admin.css), ce qui casse sa
   flex-basis "auto" et écrase les champs voisins (déjà vu avec Lieu/Ville : Ville sans
   modificateur prenait toute la largeur, réduisant Lieu à ~10px). */
.mavka-activite-preview .ap-strip__inputs .ap-input { flex: 1; width: auto; min-width: 0; }
.mavka-activite-preview .ap-strip__inputs .ap-input--heure { width: 64px; flex: none; }
.mavka-activite-preview .ap-strip__inputs .ap-input--wide { flex: 1.6; width: auto; }
.mavka-activite-preview textarea.ap-input:hover, .mavka-activite-preview textarea.ap-input:focus { background: var(--ec-mint, #E6F6F0); }
.mavka-activite-preview .ap-strip__inputs input[type="date"] { flex: 1.1; }
.mavka-activite-preview .ap-input[hidden] { display: none; }

.mavka-activite-preview .ap-titre { width: 100%; resize: none; overflow: hidden; min-height: 0; }
.mavka-activite-preview .ap-desc { width: 100%; min-height: 130px; resize: vertical; }

/* Spécificité (0,1,1) de ".mavka-form select" (admin.css) sinon gagnante sur ".btn-primary"
   pour background/border — préfixée ici pour repasser devant, même bug qu'ailleurs sur cette page. */
.mavka-activite-preview .ap-btn-select {
  appearance: none; width: fit-content; cursor: pointer;
  background: var(--ec-teal, #1FAE93); color: #fff; border-color: var(--ec-teal, #1FAE93);
}
</style>

<script>
(function () {
  function $(id) { return document.getElementById(id); }

  // Le titre est un <textarea> (pas <input>) pour pouvoir s'afficher sur 2 lignes comme sur
  // la vraie carte publique — sa hauteur suit le texte tapé, et Entrée valide plutôt que
  // d'insérer un retour à la ligne (le titre reste une seule ligne logique, juste enroulée).
  var titreInput = $('f_titre');
  function autoGrowTitre() {
    titreInput.style.height = 'auto';
    titreInput.style.height = titreInput.scrollHeight + 'px';
  }
  titreInput.addEventListener('input', autoGrowTitre);
  titreInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') e.preventDefault();
  });
  autoGrowTitre();

  // Récurrence n'a de sens que pour le bouton "Événement régulier" — le champ
  // n'apparaît que dans ce cas, et n'est pas soumis (disabled) sinon pour ne
  // jamais laisser traîner une valeur qui ne correspond plus au bouton choisi.
  var recurrenceInput = $('f_recurrence');
  var boutonSelect = $('f_texte_bouton');

  function applyRecurrenceVisibility() {
    var estRegulier = boutonSelect.value === 'Événement régulier';
    recurrenceInput.hidden = !estRegulier;
    recurrenceInput.disabled = !estRegulier;
  }

  // "Voir sa page" calcule le lien côté serveur à partir de l'intervenant·e coché·e —
  // le champ manuel n'a plus de sens dans ce cas, on le remplace par une explication.
  var lienInput = $('f_lien_inscription');
  var lienWrap = $('f_lien_inscription_wrap');
  var lienHint = $('f_lien_equipe_hint');

  function applyLienInscriptionMode() {
    var versSaPage = boutonSelect.value === 'Voir sa page';
    lienWrap.hidden = versSaPage;
    lienInput.disabled = versSaPage;
    lienHint.style.display = versSaPage ? 'block' : 'none';
  }

  function applyBoutonMode() {
    applyRecurrenceVisibility();
    applyLienInscriptionMode();
  }
  boutonSelect.addEventListener('change', applyBoutonMode);
  applyBoutonMode();

  $('f_photo').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      $('pv_photo').src = ev.target.result;
      $('pv_photo').hidden = false;
    };
    reader.readAsDataURL(file);
  });

  // Plashka provisoire sur la photo — sous-titre affiché uniquement (plus de repli sur la
  // catégorie : la plashka Direction est retirée définitivement de la carte).
  var badgeCategorie = $('pv_badge_categorie');
  function updateBadgeCategorie() {
    badgeCategorie.textContent = $('f_categorie_display').value.trim();
  }
  $('f_categorie_display').addEventListener('input', updateBadgeCategorie);
  updateBadgeCategorie();

  // Même principe pour Type d'activité, Public (sur la photo) et Nombre de places (dans
  // l'encadré date/lieu) — vide si le champ n'est pas rempli, la plashka disparaît d'elle-même.
  var badgeFormat = $('pv_badge_format');
  var placesInput = $('f_nombre_places');
  var placesHint = $('f_nombre_places_hint');
  function updateFormatDependants() {
    badgeFormat.textContent = $('f_format').value;
    var individuel = $('f_format').value === 'Individuel';
    placesInput.disabled = individuel;
    placesHint.style.display = individuel ? '' : 'none';
    if (individuel) { placesInput.value = ''; }
    updateBadgePlaces();
  }
  $('f_format').addEventListener('change', updateFormatDependants);

  var badgePublic = $('pv_badge_public');
  $('f_public').addEventListener('change', function () { badgePublic.textContent = this.value; });
  badgePublic.textContent = $('f_public').value;

  var badgePlaces = $('pv_badge_places');
  function updateBadgePlaces() {
    var places = placesInput.value.trim();
    badgePlaces.textContent = places ? places + ' places' : '';
  }
  $('f_nombre_places').addEventListener('input', updateBadgePlaces);
  updateFormatDependants();

  // Aperçu en gros dans le pavé carré : reproduit exactement render_event_strip() côté PHP
  // (includes/site_functions.php), classes comprises, pour que ce qu'on voit ici soit ce que
  // voit le public.
  var MOIS_FR = {1:'jan',2:'fév',3:'mars',4:'avr',5:'mai',6:'juin',7:'juil',8:'août',9:'sept',10:'oct',11:'nov',12:'déc'};
  var stripBadge = $('pv_strip_badge');
  var stripDate = $('pv_strip_date');
  var stripTime = $('pv_strip_time');
  var dateDebutInput = $('f_date_debut');
  var heureInput = $('f_heure');
  function updateStripApercu() {
    var dateDebut = dateDebutInput.value;
    var heure = heureInput.value.trim();
    if (dateDebut) {
      var parts = dateDebut.split('-');
      var mois = MOIS_FR[parseInt(parts[1], 10)] || '';
      stripBadge.className = 'strip__badge';
      stripDate.className = 'strip__date';
      stripDate.innerHTML = parts[2] + '<span class="strip__date-unit">' + mois + '</span>';
      stripTime.textContent = heure;
    } else {
      stripBadge.className = 'strip__badge strip__badge--wide';
      stripDate.className = 'strip__date strip__date--text';
      stripDate.textContent = recurrenceInput.value.trim() || 'Régulier';
      stripTime.textContent = heure;
    }
  }
  dateDebutInput.addEventListener('input', updateStripApercu);
  heureInput.addEventListener('input', updateStripApercu);
  recurrenceInput.addEventListener('input', updateStripApercu);
  boutonSelect.addEventListener('change', updateStripApercu);
  updateStripApercu();

  // Avatars dans la ligne du titre : un rond par intervenant·e coché·e (sans nom, cf. la liste
  // "Intervenant·e·s" ci-contre pour les noms) — reflète toutes les cases, pas juste la première.
  var avatarsWrap = $('pv_avatars');
  var casesIntervenants = document.querySelectorAll('input[name="intervenants[]"]');
  function updateAvatars() {
    avatarsWrap.innerHTML = '';
    casesIntervenants.forEach(function (c) {
      if (c.checked && c.dataset.photo) {
        var img = document.createElement('img');
        img.className = 'event-avatar';
        img.src = c.dataset.photo;
        img.alt = c.dataset.nom || '';
        avatarsWrap.appendChild(img);
      }
    });
  }
  casesIntervenants.forEach(function (c) { c.addEventListener('change', updateAvatars); });
  updateAvatars();
})();
</script>
<?php admin_footer(); ?>
