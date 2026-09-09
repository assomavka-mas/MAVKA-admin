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
    $a['lien_inscription'] = trim($_POST['lien_inscription'] ?? '');
    $a['statut'] = $_POST['statut'] === 'brouillon' ? 'brouillon' : 'publie';
    $a['statut_activite'] = in_array($_POST['statut_activite'] ?? '', ['ouvert', 'complet', 'annule', 'termine'])
        ? $_POST['statut_activite'] : 'ouvert';
    $a['ordre'] = (int)($_POST['ordre'] ?? 0);
    $posted_intervenants = array_map('intval', $_POST['intervenants'] ?? []);

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

$intervenants = db()->query('SELECT * FROM intervenants WHERE actif = 1 ORDER BY nom')->fetchAll();

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
        <select name="categorie">
          <?php foreach (['Culture', 'Éducation', 'Bien-être', 'Développement personnel'] as $cat): ?>
          <option value="<?= $cat ?>" <?= $a['categorie'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Sous-titre affiché (facultatif)</label>
        <input type="text" name="categorie_display" placeholder="Bien-être · Art-thérapie" value="<?= htmlspecialchars($a['categorie_display'] ?? '') ?>">
      </div>
    </div>

    <div class="row">
      <div>
        <label>Type d'activité</label>
        <select name="format">
          <option value="">—</option>
          <?php foreach (['Collectif', 'Individuel', 'Événementiel'] as $f): ?>
          <option value="<?= $f ?>" <?= $a['format'] === $f ? 'selected' : '' ?>><?= $f ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Public</label>
        <select name="public">
          <option value="">—</option>
          <?php foreach (['Enfant', 'Familial', 'Adultes'] as $p): ?>
          <option value="<?= $p ?>" <?= $a['public'] === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Nombre de places (vide = illimité)</label>
        <input type="number" min="0" name="nombre_places" value="<?= htmlspecialchars((string)($a['nombre_places'] ?? '')) ?>">
      </div>
      <div>
        <label>Statut de l'activité</label>
        <select name="statut_activite">
          <?php $statuts = ['ouvert' => 'Ouvert', 'complet' => 'Complet', 'annule' => 'Annulé', 'termine' => 'Terminé']; ?>
          <?php foreach ($statuts as $val => $label): ?>
          <option value="<?= $val ?>" <?= $a['statut_activite'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Lien d'inscription</label>
        <input type="url" name="lien_inscription" placeholder="https://helloasso.com/..." value="<?= htmlspecialchars($a['lien_inscription']) ?>">
      </div>
      <div>
        <label>Statut de publication</label>
        <select name="statut">
          <option value="publie" <?= $a['statut'] === 'publie' ? 'selected' : '' ?>>Publié</option>
          <option value="brouillon" <?= $a['statut'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
        </select>
      </div>
    </div>

    <label>Ordre d'affichage (0 = premier)</label>
    <input type="number" name="ordre" value="<?= (int)$a['ordre'] ?>">

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
        <span class="mavka-activite-preview__photo-pencil" title="Changer la photo">✎</span>
      </label>
      <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp" hidden>

      <input type="text" id="f_titre" name="titre" class="mavka-activite-preview__titre" placeholder="Titre de l'activité" value="<?= htmlspecialchars($a['titre']) ?>" required>

      <div class="mavka-activite-preview__meta">
        <div class="mavka-activite-preview__meta-row">
          <span>📅</span>
          <input type="date" id="f_date_debut" name="date_debut" class="mavka-activite-preview__field-default" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
          <input type="text" id="f_heure" name="heure" class="mavka-activite-preview__field-default" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
        </div>
        <input type="text" id="f_recurrence" name="recurrence" class="mavka-activite-preview__field-default" placeholder='Récurrence, ex. "Le jeudi"' value="<?= htmlspecialchars($a['recurrence']) ?>" <?= $a['texte_bouton'] === 'Événement régulier' ? '' : 'hidden' ?>>
        <div class="mavka-activite-preview__meta-row">
          <input type="text" id="f_lieu" name="lieu" placeholder="Lieu" value="<?= htmlspecialchars($a['lieu']) ?>">
          <input type="text" id="f_ville" name="ville" placeholder="Ville" value="<?= htmlspecialchars($a['ville']) ?>">
        </div>
      </div>

      <textarea id="f_description" name="description" class="mavka-activite-preview__desc" placeholder="Description de l'activité"><?= htmlspecialchars($a['description']) ?></textarea>

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
.mavka-activite-actions { flex-basis: 100%; }
.mavka-form-section--parametres { --section-color: var(--mavka-color-purple-dark); }
.mavka-activite-preview { width: 300px; flex-shrink: 0; position: sticky; top: 24px; }
.mavka-activite-preview__label { font-weight: 700; font-size: 13.5px; color: var(--mavka-color-text-muted); margin-bottom: 8px; }
.mavka-activite-preview__card {
  background: #fff; border: 2px solid var(--mavka-color-teal); border-radius: var(--mavka-radius-card);
  padding: 18px; overflow: hidden;
}

/* Champs "invisibles" tant qu'on n'interagit pas avec eux — la carte doit se lire
   comme la carte publique, pas comme un formulaire, jusqu'à ce qu'on clique dedans.
   Date/Heure/Récurrence gardent leur apparence de champ normale (.mavka-form input). */
.mavka-activite-preview__card input:not([type="checkbox"]):not(.mavka-activite-preview__field-default),
.mavka-activite-preview__card textarea {
  width: 100%; border: 1.5px dashed transparent; border-radius: 6px; background: transparent;
  font-family: inherit; color: inherit; padding: 2px 4px; margin: -2px -4px;
  transition: border-color .15s, background-color .15s;
}
.mavka-activite-preview__card input:not([type="checkbox"]):not(.mavka-activite-preview__field-default):hover,
.mavka-activite-preview__card textarea:hover {
  border-color: var(--mavka-color-teal-light);
}
.mavka-activite-preview__card input:not([type="checkbox"]):not(.mavka-activite-preview__field-default):focus,
.mavka-activite-preview__card textarea:focus {
  outline: none; border-color: var(--mavka-color-teal); background: var(--mavka-color-cream-soft);
}
.mavka-activite-preview__card input::placeholder,
.mavka-activite-preview__card textarea::placeholder { color: inherit; opacity: .45; }

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

.mavka-activite-preview__card .mavka-activite-preview__titre {
  font-family: var(--mavka-font-display); font-style: italic; font-weight: 400; font-size: 19px;
  line-height: 1.25; color: var(--mavka-color-ink); margin: 0 0 12px; display: block;
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
.mavka-activite-preview__card .mavka-activite-preview__desc {
  font-size: 13.5px; line-height: 1.5; margin: 0 0 14px; min-height: 54px; resize: vertical;
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
  boutonSelect.addEventListener('change', applyRecurrenceVisibility);
  applyRecurrenceVisibility();

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
})();
</script>
<?php admin_footer(); ?>
