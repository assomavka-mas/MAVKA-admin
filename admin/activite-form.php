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
    'lien_inscription' => '', 'photo' => null, 'statut' => 'publie', 'statut_activite' => 'ouvert', 'ordre' => 0,
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
    $a['nombre_places'] = $_POST['nombre_places'] !== '' ? (int)$_POST['nombre_places'] : null;
    $a['ville'] = trim($_POST['ville'] ?? '');
    $a['texte_bouton'] = trim($_POST['texte_bouton'] ?? '') ?: 'Préinscription gratuite';
    // La récurrence n'a de sens que pour un "Événement régulier" — pour tout autre
    // texte de bouton le champ est masqué côté formulaire, donc on ignore aussi
    // toute valeur envoyée pour ne pas garder une récurrence fantôme en base.
    $a['recurrence'] = $a['texte_bouton'] === 'Événement régulier' ? trim($_POST['recurrence'] ?? '') : '';
    $a['statut'] = $_POST['statut'] === 'brouillon' ? 'brouillon' : 'publie';
    $a['statut_activite'] = in_array($_POST['statut_activite'] ?? '', ['ouvert', 'complet', 'annule', 'termine'])
        ? $_POST['statut_activite'] : 'ouvert';
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
        $a['lien_inscription'] = $premier_intervenant ? intervenant_page_url($premier_intervenant['nom']) : '';
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
            $stmt = db()->prepare('UPDATE activites SET titre=?, categorie=?, categorie_display=?, format=?, public=?, description=?, date_debut=?, heure=?, recurrence=?, lieu=?, nombre_places=?, ville=?, texte_bouton=?, lien_inscription=?, photo=?, statut=?, statut_activite=?, ordre=? WHERE id=?');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['ordre'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO activites (titre, categorie, categorie_display, format, public, description, date_debut, heure, recurrence, lieu, nombre_places, ville, texte_bouton, lien_inscription, photo, statut, statut_activite, ordre) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['ordre']]);
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

  <details class="mavka-form-section mavka-form-section--parametres" open style="max-width:640px; flex:1 1 380px; margin-bottom:0;">
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
          <?php foreach (['Culture', 'Éducation', 'Bien-être', 'Développement personnel'] as $cat): ?>
          <option value="<?= $cat ?>" <?= $a['categorie'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Sous-titre affiché (facultatif)</label>
        <input type="text" id="f_categorie_display" name="categorie_display" placeholder="Bien-être · Art-thérapie" value="<?= htmlspecialchars($a['categorie_display'] ?? '') ?>">
      </div>
    </div>

    <div class="row">
      <div>
        <label>Type d'activité</label>
        <select id="f_format" name="format">
          <option value="">—</option>
          <?php foreach (['Collectif', 'Individuel', 'Événementiel'] as $f): ?>
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

    <div class="row mavka-row--nombres">
      <div>
        <label>Nombre de places <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(vide = illimité)</span></label>
        <input type="number" min="0" id="f_nombre_places" class="mavka-input-court" name="nombre_places" value="<?= htmlspecialchars((string)($a['nombre_places'] ?? '')) ?>">
      </div>
      <div>
        <label>Ordre d'affichage <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(0 = premier)</span></label>
        <input type="number" class="mavka-input-court" name="ordre" value="<?= (int)$a['ordre'] ?>">
      </div>
    </div>
    <p class="mavka-form-section__hint">L'ordre détermine qui apparaît en premier sur le site (0, puis 1, 2...) ; à ordre égal, la date la plus proche passe devant. Aucun calcul automatique — c'est une priorité manuelle.</p>

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
      <?php foreach ($intervenants as $iv): ?>
      <label class="mavka-picklist__item">
        <input type="checkbox" name="intervenants[]" value="<?= $iv['id'] ?>"
          <?= in_array($iv['id'], $selected_intervenants) ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($iv['nom']) ?><?= $iv['role_titre'] ? ' — ' . htmlspecialchars($iv['role_titre']) : '' ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    </div>
  </details>

  <aside class="mavka-activite-preview">
    <div class="mavka-activite-preview__label">Carte publique — modifiable directement ici</div>
    <div class="mavka-activite-preview__card">
      <label for="f_photo" class="mavka-activite-preview__photo-wrap">
        <div class="mavka-activite-preview__photo-box">
          <img id="pv_photo" class="mavka-activite-preview__photo" alt=""
               <?= !empty($a['photo']) ? 'src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '"' : 'hidden' ?>>
        </div>
        <span id="pv_badge_categorie" class="mavka-activite-preview__badge mavka-activite-preview__badge--photo mavka-activite-preview__badge--tl" title="Provisoire — sera intégré à la carte lors de la maquette finale"></span>
        <span id="pv_badge_format" class="mavka-activite-preview__badge mavka-activite-preview__badge--photo mavka-activite-preview__badge--tr" title="Provisoire — sera intégré à la carte lors de la maquette finale"></span>
        <span id="pv_badge_public" class="mavka-activite-preview__badge mavka-activite-preview__badge--photo mavka-activite-preview__badge--bl" title="Provisoire — sera intégré à la carte lors de la maquette finale"></span>
        <span class="mavka-activite-preview__photo-pencil" title="Changer la photo">✎</span>
      </label>
      <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp" hidden>

      <textarea id="f_titre" name="titre" rows="1" class="mavka-activite-preview__field-default mavka-activite-preview__titre" placeholder="Titre de l'activité" required><?= htmlspecialchars($a['titre']) ?></textarea>

      <div class="mavka-activite-preview__meta">
        <div class="mavka-activite-preview__meta-row">
          <span>📅</span>
          <input type="date" id="f_date_debut" name="date_debut" class="mavka-activite-preview__field-default" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
          <input type="text" id="f_heure" name="heure" class="mavka-activite-preview__field-default" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
        </div>
        <input type="text" id="f_recurrence" name="recurrence" class="mavka-activite-preview__field-default" placeholder='Récurrence, ex. "Le jeudi"' value="<?= htmlspecialchars($a['recurrence']) ?>" <?= $a['texte_bouton'] === 'Événement régulier' ? '' : 'hidden' ?>>
        <div class="mavka-activite-preview__meta-row">
          <input type="text" id="f_lieu" name="lieu" class="mavka-activite-preview__field-default" placeholder="Lieu" value="<?= htmlspecialchars($a['lieu']) ?>">
          <input type="text" id="f_ville" name="ville" class="mavka-activite-preview__field-default" placeholder="Ville" value="<?= htmlspecialchars($a['ville']) ?>">
        </div>
        <span id="pv_badge_places" class="mavka-activite-preview__badge" style="margin-top:6px;" title="Provisoire — sera intégré à la carte lors de la maquette finale"></span>
      </div>

      <textarea id="f_description" name="description" class="mavka-activite-preview__field-default mavka-activite-preview__desc" placeholder="Description de l'activité"><?= htmlspecialchars($a['description']) ?></textarea>

      <?php
        // Préinscription gratuite : date libre, sert à constituer une liste pour la mairie
        //   (typiquement 2 séances test).
        // Préinscription : date fixée, places limitées — on compte les inscrits (payant ou
        //   gratuit selon la description/HelloAsso).
        // Gratuit : entrée libre, grandes salles / événements ouverts, pas de liste à tenir.
        // Événement régulier : pas de date unique (ex. "1er et 3e mercredi du mois") — voir Récurrence.
        // En savoir plus : partenaires / événements ouverts qui intéressent l'association.
        // Voir sa page : renvoie vers la page du volontaire/intervenant·e plutôt que vers une inscription.
        $boutons = ['Préinscription gratuite', 'Préinscription', 'Gratuit', 'Événement régulier', 'En savoir plus', 'Voir sa page'];
        if ($a['texte_bouton'] && !in_array($a['texte_bouton'], $boutons)) {
            array_unshift($boutons, $a['texte_bouton']); // garde l'ancienne valeur personnalisée si elle ne fait pas partie de la liste
        }
      ?>
      <select id="f_texte_bouton" name="texte_bouton" class="mavka-activite-preview__btn">
        <?php foreach ($boutons as $b): ?>
        <option value="<?= htmlspecialchars($b) ?>" <?= $a['texte_bouton'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </aside>

  <div class="mavka-activite-actions">
    <button type="submit" class="mavka-btn mavka-btn--primary">Enregistrer</button>
    <a href="/admin/activites.php" class="mavka-btn">Annuler</a>
  </div>

</form>
</div>

<style>
.mavka-activite-layout { display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
.mavka-input-court { max-width: 90px; }
.mavka-activite-actions { flex-basis: 100%; }
.mavka-form-section--parametres { --section-color: var(--mavka-color-purple-dark); }
.mavka-activite-preview { width: 300px; flex-shrink: 0; position: sticky; top: 24px; }
.mavka-activite-preview__label { font-weight: 700; font-size: 13.5px; color: var(--mavka-color-text-muted); margin-bottom: 8px; }
.mavka-activite-preview__card {
  background: #fff; border: 2px solid var(--mavka-color-teal); border-radius: var(--mavka-radius-card);
  padding: 18px; overflow: hidden;
}

/* Une seule apparence de champ pour tout le formulaire de la carte (titre, date, heure,
   lieu, ville, description) — celle par défaut de .mavka-form, pas de traitement à part. */

.mavka-activite-preview__photo-wrap {
  display: block; cursor: pointer; position: relative; margin-bottom: 12px;
}
.mavka-activite-preview__photo-box {
  width: 100%; aspect-ratio: 16/10; border-radius: 10px; overflow: hidden;
  background: var(--mavka-color-teal-light);
}
.mavka-activite-preview__photo { width: 100%; height: 100%; object-fit: cover; display: block; }
.mavka-activite-preview__photo-pencil {
  position: absolute; right: 8px; bottom: 8px; width: 30px; height: 30px; border-radius: 50%;
  background: var(--mavka-color-teal); color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 13px; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(36,27,40,.2);
}
.mavka-activite-preview__photo-wrap:hover .mavka-activite-preview__photo-pencil { background: var(--mavka-color-teal-dark, #276A62); }
/* Provisoire : juste pour que Catégorie/Type/Public/Places soient visibles quelque part
   avant la maquette finale des pages publiques — couleur volontairement hors palette pour
   rester repérable comme "à refaire". */
.mavka-activite-preview__badge {
  display: inline-block; max-width: 100%;
  background: #FFD54D; color: #4A3B00; font-size: 11.5px; font-weight: 700;
  padding: 4px 9px; border-radius: 6px; box-shadow: 0 1px 3px rgba(36,27,40,.25);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.mavka-activite-preview__badge:empty { display: none; }
.mavka-activite-preview__badge--photo { position: absolute; max-width: calc(50% - 30px); }
.mavka-activite-preview__badge--tl { top: 8px; left: 8px; }
.mavka-activite-preview__badge--tr { top: 8px; right: 8px; }
.mavka-activite-preview__badge--bl { bottom: 8px; left: 8px; }

.mavka-activite-preview__card .mavka-activite-preview__field-default.mavka-activite-preview__titre {
  font-family: var(--mavka-font-display); font-style: italic; font-weight: 400; font-size: 19px;
  line-height: 1.25; color: var(--mavka-color-ink); margin: 0 0 12px; display: block; padding: 4px 6px;
  resize: none; overflow: hidden;
}
.mavka-activite-preview__meta {
  border: 1.5px solid var(--mavka-color-orange); border-radius: 10px; padding: 8px 12px; margin-bottom: 12px;
}
.mavka-activite-preview__meta-row { display: flex; gap: 6px; }
.mavka-activite-preview__meta-row + .mavka-activite-preview__meta-row { margin-top: 6px; }
.mavka-activite-preview__meta-row input[type="date"] { flex: 1.4; }
.mavka-activite-preview__meta-row input[type="text"] { flex: 1; }
.mavka-activite-preview__card .mavka-activite-preview__field-default { font-size: 13px; padding: 6px 8px; }
.mavka-activite-preview__card #f_recurrence.mavka-activite-preview__field-default { margin-top: 6px; }
.mavka-activite-preview__card .mavka-activite-preview__field-default.mavka-activite-preview__desc {
  font-size: 13.5px; line-height: 1.5; margin: 0 0 14px; min-height: 130px; resize: vertical;
}
.mavka-activite-preview__card .mavka-activite-preview__btn {
  width: 100%; appearance: none; background: var(--mavka-color-purple); color: #fff; text-align: center;
  text-align-last: center; font-weight: 700; font-size: 14px; border: 1.5px dashed transparent;
  border-radius: var(--mavka-radius-pill); padding: 11px 18px; cursor: pointer;
}
.mavka-activite-preview__card .mavka-activite-preview__btn:hover { border-color: rgba(255,255,255,.6); }
.mavka-activite-preview__card .mavka-activite-preview__btn:focus { outline: none; border-color: #fff; }
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

  // Plashka provisoire sur la photo — reprend le sous-titre affiché, sinon la catégorie.
  var badgeCategorie = $('pv_badge_categorie');
  function updateBadgeCategorie() {
    var sousTitre = $('f_categorie_display').value.trim();
    badgeCategorie.textContent = sousTitre || $('f_categorie').value;
  }
  $('f_categorie').addEventListener('change', updateBadgeCategorie);
  $('f_categorie_display').addEventListener('input', updateBadgeCategorie);
  updateBadgeCategorie();

  // Même principe pour Type d'activité, Public (sur la photo) et Nombre de places (dans
  // l'encadré date/lieu) — vide si le champ n'est pas rempli, la plashka disparaît d'elle-même.
  var badgeFormat = $('pv_badge_format');
  $('f_format').addEventListener('change', function () { badgeFormat.textContent = this.value; });
  badgeFormat.textContent = $('f_format').value;

  var badgePublic = $('pv_badge_public');
  $('f_public').addEventListener('change', function () { badgePublic.textContent = this.value; });
  badgePublic.textContent = $('f_public').value;

  var badgePlaces = $('pv_badge_places');
  function updateBadgePlaces() {
    var places = $('f_nombre_places').value.trim();
    badgePlaces.textContent = places ? places + ' places' : '';
  }
  $('f_nombre_places').addEventListener('input', updateBadgePlaces);
  updateBadgePlaces();
})();
</script>
<?php admin_footer(); ?>
