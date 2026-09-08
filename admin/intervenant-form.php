<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$iv = [
    'nom' => '', 'dossier' => '', 'role_titre' => '', 'resume' => '', 'domaine' => '', 'adresse' => '',
    'specialite' => '', 'secteur_intervention' => '', 'bio' => '', 'parcours_personnel' => '', 'vision' => '',
    'charte_benevolat_lien' => '', 'charte_benevolat_fichier' => null,
    'contrat_intervention_lien' => '', 'contrat_intervention_fichier' => null,
    'date_signee' => '',
    'cv_lien' => '', 'cv_fichier' => null,
    'documents_pro_lien' => '', 'documents_pro_fichier' => null,
    'projet_developpement' => '', 'objectifs_mavka' => '',
    'photo' => null, 'email' => '', 'actif' => 1,
];
$login_email = '';

if ($id) {
    $stmt = db()->prepare('SELECT * FROM intervenants WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Intervenant introuvable.'); }
    $iv = $found;

    $stmt = db()->prepare('SELECT email FROM admins WHERE intervenant_id = ?');
    $stmt->execute([$id]);
    $login_email = $stmt->fetchColumn() ?: '';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'save') {
    $iv['nom'] = trim($_POST['nom'] ?? '');
    $iv['role_titre'] = trim($_POST['role_titre'] ?? '');
    $iv['resume'] = trim($_POST['resume'] ?? '');
    $iv['domaine'] = trim($_POST['domaine'] ?? '') ?: null;
    $iv['adresse'] = trim($_POST['adresse'] ?? '');
    $iv['specialite'] = trim($_POST['specialite'] ?? '');
    $iv['secteur_intervention'] = trim($_POST['secteur_intervention'] ?? '');
    $iv['bio'] = trim($_POST['bio'] ?? '');
    $iv['parcours_personnel'] = trim($_POST['parcours_personnel'] ?? '');
    $iv['vision'] = trim($_POST['vision'] ?? '');
    $iv['charte_benevolat_lien'] = trim($_POST['charte_benevolat_lien'] ?? '');
    $iv['contrat_intervention_lien'] = trim($_POST['contrat_intervention_lien'] ?? '');
    $iv['date_signee'] = $_POST['date_signee'] ?: null;
    $iv['cv_lien'] = trim($_POST['cv_lien'] ?? '');
    $iv['documents_pro_lien'] = trim($_POST['documents_pro_lien'] ?? '');
    $iv['projet_developpement'] = trim($_POST['projet_developpement'] ?? '');
    $iv['objectifs_mavka'] = trim($_POST['objectifs_mavka'] ?? '');
    $iv['email'] = trim($_POST['email'] ?? '');
    $iv['actif'] = isset($_POST['actif']) ? 1 : 0;
    $new_login_email = strtolower(trim($_POST['login_email'] ?? ''));

    if ($iv['nom'] === '') {
        $error = 'Le nom est obligatoire.';
    } else {
        $fields = [
            'nom', 'dossier', 'role_titre', 'resume', 'domaine', 'adresse', 'specialite', 'secteur_intervention',
            'bio', 'parcours_personnel', 'vision',
            'charte_benevolat_lien', 'charte_benevolat_fichier', 'contrat_intervention_lien', 'contrat_intervention_fichier',
            'date_signee', 'cv_lien', 'cv_fichier', 'documents_pro_lien', 'documents_pro_fichier',
            'projet_developpement', 'objectifs_mavka', 'photo', 'email', 'actif',
        ];

        if (!$id) {
            // Nouvel intervenant : on l'enregistre d'abord (sans fichiers) pour connaître son id,
            // seulement ensuite on peut créer son dossier et y placer les fichiers.
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $stmt = db()->prepare('INSERT INTO intervenants (' . implode(', ', $fields) . ") VALUES ($placeholders)");
            $stmt->execute(array_map(fn($f) => $iv[$f], $fields));
            $id = (int)db()->lastInsertId();
        }

        if (empty($iv['dossier'])) {
            $iv['dossier'] = intervenant_dossier($id, $iv['nom']);
        }
        $subdir = 'intervenants/' . $iv['dossier'];

        foreach (['photo', 'charte_benevolat_fichier', 'contrat_intervention_fichier', 'cv_fichier', 'documents_pro_fichier'] as $f) {
            $uploaded = handle_upload($f, $subdir);
            if ($uploaded) {
                $iv[$f] = $uploaded;
            }
        }

        $set = implode(', ', array_map(fn($f) => "$f = ?", $fields));
        $stmt = db()->prepare("UPDATE intervenants SET $set WHERE id = ?");
        $stmt->execute([...array_map(fn($f) => $iv[$f], $fields), $id]);

        // Synchronise l'accès espace bénévole avec l'email indiqué
        $stmt = db()->prepare('SELECT id FROM admins WHERE intervenant_id = ?');
        $stmt->execute([$id]);
        $existing_admin_id = $stmt->fetchColumn();

        if ($new_login_email === '') {
            if ($existing_admin_id) {
                db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$existing_admin_id]);
            }
        } elseif ($existing_admin_id) {
            db()->prepare('UPDATE admins SET email = ? WHERE id = ?')->execute([$new_login_email, $existing_admin_id]);
        } else {
            db()->prepare('INSERT INTO admins (email, password_hash, role, intervenant_id) VALUES (?,NULL,\'benevole\',?)')
                ->execute([$new_login_email, $id]);
        }

        header('Location: /admin/intervenant-form.php?id=' . $id . '&ok=1');
        exit;
    }
}

// Gestion de la liste "Ce que je propose" (ateliers possibles, séparés des Activités programmées)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_atelier' && $id) {
    $titre = trim($_POST['atelier_titre'] ?? '');
    if ($titre !== '') {
        db()->prepare('INSERT INTO intervenant_ateliers (intervenant_id, titre, description) VALUES (?,?,?)')
            ->execute([$id, $titre, trim($_POST['atelier_description'] ?? '')]);
    }
    header('Location: /admin/intervenant-form.php?id=' . $id . '&ok=1');
    exit;
}
if (isset($_GET['delete_atelier']) && $id) {
    db()->prepare('DELETE FROM intervenant_ateliers WHERE id = ? AND intervenant_id = ?')
        ->execute([(int)$_GET['delete_atelier'], $id]);
    header('Location: /admin/intervenant-form.php?id=' . $id);
    exit;
}

$ateliers = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM intervenant_ateliers WHERE intervenant_id = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$id]);
    $ateliers = $stmt->fetchAll();
}

$file_url = fn($f) => !empty($iv[$f]) ? '/assets/uploads/intervenants/' . $iv['dossier'] . '/' . $iv[$f] : null;

admin_header($id ? "Modifier l'intervenant·e" : 'Nouvel·le intervenant·e', $user, 'intervenants');
?>
<h1><?= $id ? "Modifier l'intervenant·e" : "Nouvel·le intervenant·e" ?></h1>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mavka-form mavka-card" style="max-width:640px;">
  <input type="hidden" name="action" value="save">
  <label>Nom</label>
  <input type="text" name="nom" value="<?= htmlspecialchars($iv['nom']) ?>" required>

  <div class="row">
    <div><label>Rôle</label><input type="text" name="role_titre" placeholder="Bénévole" value="<?= htmlspecialchars($iv['role_titre'] ?? '') ?>"></div>
    <div>
      <label>Domaine</label>
      <select name="domaine">
        <option value="">—</option>
        <?php foreach (['Culture', 'Éducation', 'Bien-être', 'Développement personnel'] as $d): ?>
        <option value="<?= $d ?>" <?= $iv['domaine'] === $d ? 'selected' : '' ?>><?= $d ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <label>Résumé (courte description affichée sur la carte, sous le rôle)</label>
  <input type="text" name="resume" placeholder="Développement personnel, accompagnement des intervenants, Parcours MAVKA" value="<?= htmlspecialchars($iv['resume'] ?? '') ?>" maxlength="300">

  <div class="row">
    <div>
      <label>Statut (calculé automatiquement)</label>
      <div style="display:flex; gap:6px; padding:10px 0;">
        <?php foreach (intervenant_statuts($iv) as $s): ?>
        <span class="mavka-badge mavka-badge--success"><?= htmlspecialchars($s) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div><label>Adresse</label><input type="text" name="adresse" placeholder="16000 Angoulême" value="<?= htmlspecialchars($iv['adresse'] ?? '') ?>"></div>
  </div>
  <p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:-8px 0 8px;">Bénévole apparaît quand "Charte du bénévolat" est rempli, Intervenant quand "Contrat d'intervention" est rempli (lien ou fichier). Les deux peuvent être actifs en même temps. Se met à jour après enregistrement.</p>

  <div class="row">
    <div><label>Spécialité</label><input type="text" name="specialite" placeholder="Musique" value="<?= htmlspecialchars($iv['specialite'] ?? '') ?>"></div>
    <div><label>Secteur intervention</label><input type="text" name="secteur_intervention" value="<?= htmlspecialchars($iv['secteur_intervention'] ?? '') ?>"></div>
  </div>

  <h3 style="margin-top:20px; font-size:16px;">Contenu public de la page volontaire</h3>
  <p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:0 0 8px;">Ce que voient les visiteurs du site, dans les 3 onglets de sa page.</p>
  <label>Présentation (Bio)</label>
  <textarea name="bio"><?= htmlspecialchars($iv['bio'] ?? '') ?></textarea>
  <label>Parcours (son histoire personnelle)</label>
  <textarea name="parcours_personnel"><?= htmlspecialchars($iv['parcours_personnel'] ?? '') ?></textarea>
  <label>Ma vision</label>
  <textarea name="vision"><?= htmlspecialchars($iv['vision'] ?? '') ?></textarea>

  <label style="margin-top:16px;">Email de contact</label>
  <input type="email" name="email" value="<?= htmlspecialchars($iv['email'] ?? '') ?>">

  <h3 style="margin-top:20px; font-size:16px;">Documents</h3>
  <p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:0 0 8px;">Pour chaque document : soit un lien Google Drive, soit un fichier téléversé ici (image ou PDF) — les deux sont possibles.</p>

  <label>Charte du bénévolat</label>
  <input type="url" name="charte_benevolat_lien" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($iv['charte_benevolat_lien'] ?? '') ?>">
  <input type="file" name="charte_benevolat_fichier" accept="image/png,image/jpeg,image/webp,application/pdf" style="margin-top:6px;">
  <?php if ($u = $file_url('charte_benevolat_fichier')): ?><p style="margin:4px 0;"><a href="<?= $u ?>" target="_blank">Fichier actuel</a></p><?php endif; ?>

  <label style="margin-top:16px;">Contrat d'intervention</label>
  <input type="url" name="contrat_intervention_lien" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($iv['contrat_intervention_lien'] ?? '') ?>">
  <input type="file" name="contrat_intervention_fichier" accept="image/png,image/jpeg,image/webp,application/pdf" style="margin-top:6px;">
  <?php if ($u = $file_url('contrat_intervention_fichier')): ?><p style="margin:4px 0;"><a href="<?= $u ?>" target="_blank">Fichier actuel</a></p><?php endif; ?>

  <label style="margin-top:16px;">Date signée</label>
  <input type="date" name="date_signee" value="<?= htmlspecialchars($iv['date_signee'] ?? '') ?>">

  <label style="margin-top:16px;">CV</label>
  <input type="url" name="cv_lien" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($iv['cv_lien'] ?? '') ?>">
  <input type="file" name="cv_fichier" accept="image/png,image/jpeg,image/webp,application/pdf" style="margin-top:6px;">
  <?php if ($u = $file_url('cv_fichier')): ?><p style="margin:4px 0;"><a href="<?= $u ?>" target="_blank">Fichier actuel</a></p><?php endif; ?>

  <label style="margin-top:16px;">Documents professionnels</label>
  <input type="url" name="documents_pro_lien" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($iv['documents_pro_lien'] ?? '') ?>">
  <input type="file" name="documents_pro_fichier" accept="image/png,image/jpeg,image/webp,application/pdf" style="margin-top:6px;">
  <?php if ($u = $file_url('documents_pro_fichier')): ?><p style="margin:4px 0;"><a href="<?= $u ?>" target="_blank">Fichier actuel</a></p><?php endif; ?>

  <h3 style="margin-top:20px; font-size:16px;">Suivi interne (toi + mairie, jamais affiché sur le site)</h3>
  <label>Mon projet de développement</label>
  <textarea name="projet_developpement"><?= htmlspecialchars($iv['projet_developpement'] ?? '') ?></textarea>
  <label>Mes objectifs avec MAVKA</label>
  <textarea name="objectifs_mavka"><?= htmlspecialchars($iv['objectifs_mavka'] ?? '') ?></textarea>

  <h3 style="margin-top:20px; font-size:16px;">Photo de profil</h3>
  <?php if ($u = $file_url('photo')): ?>
    <img src="<?= $u ?>" alt="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:8px; display:block;">
  <?php endif; ?>
  <input type="file" name="photo" accept="image/png,image/jpeg,image/webp">

  <label style="margin-top:16px;"><input type="checkbox" name="actif" <?= $iv['actif'] ? 'checked' : '' ?> style="width:auto;"> Actif (visible dans les listes)</label>

  <h3 style="margin-top:20px; font-size:16px;">Accès espace bénévole (facultatif)</h3>
  <p style="font-size:13px; color:var(--mavka-color-text-muted); margin:0;">Si rempli, cette personne pourra se connecter avec Google (avec cette adresse exacte) et voir ses propres activités. Vide = pas d'accès.</p>
  <label>Email Google de connexion</label>
  <input type="email" name="login_email" placeholder="prenom.nom@gmail.com" value="<?= htmlspecialchars($login_email) ?>">

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:20px;">Enregistrer</button>
  <a href="/admin/intervenants.php" class="mavka-btn" style="margin-top:20px;">Annuler</a>
</form>

<?php if ($id): ?>
<h2 style="margin-top:32px;">Ce que je propose</h2>
<p style="font-size:13.5px; color:var(--mavka-color-text-muted); max-width:600px;">Liste des ateliers possibles (pas forcément programmés) — écrite une fois, rarement modifiée. Différent des Activités réelles avec une date, gérées dans "Activités".</p>

<div style="display:flex; flex-direction:column; gap:10px; margin:16px 0; max-width:600px;">
  <?php foreach ($ateliers as $at): ?>
  <div class="mavka-card" style="padding:14px 18px; display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
    <div>
      <div style="font-weight:700;"><?= htmlspecialchars($at['titre']) ?></div>
      <?php if ($at['description']): ?><div style="font-size:13.5px; color:var(--mavka-color-text-muted); margin-top:4px;"><?= htmlspecialchars($at['description']) ?></div><?php endif; ?>
    </div>
    <a href="/admin/intervenant-form.php?id=<?= $id ?>&delete_atelier=<?= $at['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
       onclick="return confirm('Supprimer ?');">Supprimer</a>
  </div>
  <?php endforeach; ?>
  <?php if (!$ateliers): ?>
  <p style="color:var(--mavka-color-text-muted); font-size:13.5px;">Aucun atelier proposé pour l'instant.</p>
  <?php endif; ?>
</div>

<form method="post" class="mavka-form mavka-card" style="max-width:500px;">
  <input type="hidden" name="action" value="add_atelier">
  <label>Titre de l'atelier</label>
  <input type="text" name="atelier_titre" required>
  <label>Description</label>
  <textarea name="atelier_description"></textarea>
  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:16px;">Ajouter</button>
</form>
<?php endif; ?>
<?php admin_footer(); ?>
