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

<form method="post" enctype="multipart/form-data" class="mavka-form mavka-card" style="max-width:640px;">
  <label>Titre</label>
  <input type="text" name="titre" value="<?= htmlspecialchars($a['titre']) ?>" required>

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
  <textarea name="description"><?= htmlspecialchars($a['description']) ?></textarea>

  <div class="row">
    <div>
      <label>Date (si événement ponctuel)</label>
      <input type="date" name="date_debut" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
    </div>
    <div>
      <label>Heure</label>
      <input type="text" name="heure" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
    </div>
  </div>

  <label>Récurrence (si activité régulière, ex. "Le jeudi")</label>
  <input type="text" name="recurrence" value="<?= htmlspecialchars($a['recurrence']) ?>">

  <div class="row">
    <div>
      <label>Lieu</label>
      <input type="text" name="lieu" value="<?= htmlspecialchars($a['lieu']) ?>">
    </div>
    <div>
      <label>Ville</label>
      <input type="text" name="ville" value="<?= htmlspecialchars($a['ville']) ?>">
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
      <select name="texte_bouton">
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
  <input type="file" name="photo" accept="image/png,image/jpeg,image/webp">

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
<?php admin_footer(); ?>
