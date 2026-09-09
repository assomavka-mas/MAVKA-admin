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
    $a['recurrence'] = trim($_POST['recurrence'] ?? '');
    $a['lieu'] = trim($_POST['lieu'] ?? '');
    $a['nombre_places'] = $_POST['nombre_places'] !== '' ? (int)$_POST['nombre_places'] : null;
    $a['ville'] = trim($_POST['ville'] ?? '');
    $a['texte_bouton'] = trim($_POST['texte_bouton'] ?? '') ?: 'Préinscription gratuite';
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
<form method="post" enctype="multipart/form-data" class="mavka-form mavka-card" style="max-width:640px;">
  <label>Titre</label>
  <input type="text" id="f_titre" name="titre" value="<?= htmlspecialchars($a['titre']) ?>" required>

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

  <label>Description</label>
  <textarea id="f_description" name="description"><?= htmlspecialchars($a['description']) ?></textarea>

  <div class="row">
    <div>
      <label>Date (si événement ponctuel)</label>
      <input type="date" id="f_date_debut" name="date_debut" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
    </div>
    <div>
      <label>Heure</label>
      <input type="text" id="f_heure" name="heure" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
    </div>
  </div>

  <label>Récurrence (si activité régulière, ex. "Le jeudi")</label>
  <input type="text" id="f_recurrence" name="recurrence" value="<?= htmlspecialchars($a['recurrence']) ?>">

  <div class="row">
    <div>
      <label>Lieu</label>
      <input type="text" id="f_lieu" name="lieu" value="<?= htmlspecialchars($a['lieu']) ?>">
    </div>
    <div>
      <label>Ville</label>
      <input type="text" id="f_ville" name="ville" value="<?= htmlspecialchars($a['ville']) ?>">
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
      <label>Texte du bouton</label>
      <?php
        $boutons = ['Préinscription gratuite', 'Préinscription', 'Gratuit', 'Événement régulier', 'En savoir plus', 'Voir sa page', 'Payer la participation'];
        if ($a['texte_bouton'] && !in_array($a['texte_bouton'], $boutons)) {
            array_unshift($boutons, $a['texte_bouton']); // garde l'ancienne valeur personnalisée si elle ne fait pas partie de la liste
        }
      ?>
      <select id="f_texte_bouton" name="texte_bouton">
        <?php foreach ($boutons as $b): ?>
        <option value="<?= htmlspecialchars($b) ?>" <?= $a['texte_bouton'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Lien d'inscription</label>
      <input type="url" name="lien_inscription" placeholder="https://helloasso.com/..." value="<?= htmlspecialchars($a['lien_inscription']) ?>">
    </div>
  </div>

  <label>Photo</label>
  <?php if (!empty($a['photo'])): ?>
    <img src="/assets/uploads/activites/<?= htmlspecialchars($a['photo']) ?>" alt="" style="width:120px; border-radius:10px; margin-bottom:8px; display:block;">
  <?php endif; ?>
  <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp">

  <div class="row">
    <div>
      <label>Statut de publication</label>
      <select name="statut">
        <option value="publie" <?= $a['statut'] === 'publie' ? 'selected' : '' ?>>Publié</option>
        <option value="brouillon" <?= $a['statut'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
      </select>
    </div>
    <div>
      <label>Ordre d'affichage (0 = premier)</label>
      <input type="number" name="ordre" value="<?= (int)$a['ordre'] ?>">
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

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:22px;">Enregistrer</button>
  <a href="/admin/activites.php" class="mavka-btn" style="margin-top:22px;">Annuler</a>
</form>

<aside class="mavka-activite-preview">
  <div class="mavka-activite-preview__label">Aperçu de la carte publique</div>
  <div class="mavka-activite-preview__card">
    <img id="pv_photo" class="mavka-activite-preview__photo" alt=""
         <?= !empty($a['photo']) ? 'src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '"' : 'hidden' ?>>
    <h3 id="pv_titre" class="mavka-activite-preview__titre"></h3>
    <div class="mavka-activite-preview__meta">
      <div class="mavka-activite-preview__meta-line mavka-activite-preview__meta-line--date">
        <span>📅</span> <span id="pv_date"></span>
      </div>
      <div id="pv_lieu" class="mavka-activite-preview__meta-line"></div>
    </div>
    <p id="pv_description" class="mavka-activite-preview__desc"></p>
    <div id="pv_bouton" class="mavka-activite-preview__btn"></div>
  </div>
</aside>
</div>

<style>
.mavka-activite-layout { display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
.mavka-activite-preview { width: 300px; flex-shrink: 0; position: sticky; top: 24px; }
.mavka-activite-preview__label { font-weight: 700; font-size: 13.5px; color: var(--mavka-color-text-muted); margin-bottom: 8px; }
.mavka-activite-preview__card {
  background: #fff; border: 2px solid var(--mavka-color-teal); border-radius: var(--mavka-radius-card);
  padding: 18px; overflow: hidden;
}
.mavka-activite-preview__photo { width: 100%; aspect-ratio: 16/10; object-fit: cover; border-radius: 10px; margin-bottom: 12px; }
.mavka-activite-preview__titre {
  font-family: var(--mavka-font-display); font-style: italic; font-weight: 400; font-size: 19px;
  line-height: 1.25; color: var(--mavka-color-ink); margin: 0 0 12px;
}
.mavka-activite-preview__titre:empty::before { content: "Titre de l'activité"; opacity: .45; font-style: italic; }
.mavka-activite-preview__meta {
  border: 1.5px solid var(--mavka-color-orange); border-radius: 10px; padding: 8px 12px; margin-bottom: 12px;
}
.mavka-activite-preview__meta-line { font-size: 13.5px; color: var(--mavka-color-text); }
.mavka-activite-preview__meta-line--date { font-weight: 700; margin-bottom: 2px; }
.mavka-activite-preview__meta-line:empty { display: none; }
.mavka-activite-preview__desc { font-size: 13.5px; color: var(--mavka-color-text-muted); line-height: 1.5; margin: 0 0 14px; }
.mavka-activite-preview__desc:empty { display: none; }
.mavka-activite-preview__btn {
  background: var(--mavka-color-purple); color: #fff; text-align: center; font-weight: 700; font-size: 14px;
  border-radius: var(--mavka-radius-pill); padding: 11px 18px;
}
</style>

<script>
(function () {
  function $(id) { return document.getElementById(id); }

  function formatDate(iso) {
    if (!iso) return '';
    var parts = iso.split('-');
    if (parts.length !== 3) return iso;
    return parts[2] + '/' + parts[1] + '/' + parts[0];
  }

  function updatePreview() {
    $('pv_titre').textContent = $('f_titre').value.trim();

    var dateVal = formatDate($('f_date_debut').value);
    var heureVal = $('f_heure').value.trim();
    var recurrenceVal = $('f_recurrence').value.trim();
    var dateLine = '';
    if (dateVal) {
      dateLine = dateVal + (heureVal ? ' · ' + heureVal : '');
    } else if (recurrenceVal) {
      dateLine = recurrenceVal + (heureVal ? ' · ' + heureVal : '');
    } else if (heureVal) {
      dateLine = heureVal;
    }
    $('pv_date').textContent = dateLine;

    var lieuVal = $('f_lieu').value.trim();
    var villeVal = $('f_ville').value.trim();
    $('pv_lieu').textContent = [lieuVal, villeVal].filter(Boolean).join(', ');

    $('pv_description').textContent = $('f_description').value.trim();

    var boutonSelect = $('f_texte_bouton');
    $('pv_bouton').textContent = boutonSelect.value || 'Préinscription gratuite';
  }

  ['f_titre', 'f_date_debut', 'f_heure', 'f_recurrence', 'f_lieu', 'f_ville', 'f_description'].forEach(function (id) {
    $(id).addEventListener('input', updatePreview);
  });
  $('f_texte_bouton').addEventListener('change', updatePreview);

  $('f_photo').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      var img = $('pv_photo');
      img.src = ev.target.result;
      img.hidden = false;
    };
    reader.readAsDataURL(file);
  });

  updatePreview();
})();
</script>
<?php admin_footer(); ?>
