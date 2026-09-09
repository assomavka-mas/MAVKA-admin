<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$iv = [
    'nom' => '', 'dossier' => '', 'role_titre' => '', 'resume' => '', 'domaine' => '', 'adresse' => '',
    'bio' => '', 'parcours_personnel' => '', 'vision' => '',
    'charte_benevolat_lien' => '', 'charte_benevolat_fichier' => null,
    'contrat_intervention_lien' => '', 'contrat_intervention_fichier' => null,
    'date_signee' => '',
    'cv_lien' => '', 'cv_fichier' => null,
    'rib_lien' => '', 'rib_fichier' => null,
    'assurance_lien' => '', 'assurance_fichier' => null, 'assurance_date' => '',
    'projet_developpement' => '', 'projet_developpement_fichier' => null, 'objectifs_mavka' => '',
    'photo' => null, 'email' => '', 'actif' => 1,
];
$domaines_disponibles = ['Culture', 'Éducation', 'Bien-être', 'Développement personnel'];
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
    $iv['domaine'] = implode(',', array_map('trim', $_POST['domaine'] ?? [])) ?: null;
    $iv['adresse'] = trim($_POST['adresse'] ?? '');
    $iv['bio'] = trim($_POST['bio'] ?? '');
    $iv['parcours_personnel'] = trim($_POST['parcours_personnel'] ?? '');
    $iv['vision'] = trim($_POST['vision'] ?? '');
    $iv['charte_benevolat_lien'] = trim($_POST['charte_benevolat_lien'] ?? '');
    $iv['contrat_intervention_lien'] = trim($_POST['contrat_intervention_lien'] ?? '');
    $iv['date_signee'] = $_POST['date_signee'] ?: null;
    $iv['cv_lien'] = trim($_POST['cv_lien'] ?? '');
    $iv['rib_lien'] = trim($_POST['rib_lien'] ?? '');
    $iv['assurance_lien'] = trim($_POST['assurance_lien'] ?? '');
    $iv['assurance_date'] = $_POST['assurance_date'] ?: null;
    $iv['projet_developpement'] = trim($_POST['projet_developpement'] ?? '');
    $iv['objectifs_mavka'] = trim($_POST['objectifs_mavka'] ?? '');
    $iv['email'] = trim($_POST['email'] ?? '');
    $iv['actif'] = isset($_POST['actif']) ? 1 : 0;
    $new_login_email = strtolower(trim($_POST['login_email'] ?? ''));

    if ($iv['nom'] === '') {
        $error = 'Le nom est obligatoire.';
    } else {
        $fields = [
            'nom', 'dossier', 'role_titre', 'resume', 'domaine', 'adresse',
            'bio', 'parcours_personnel', 'vision',
            'charte_benevolat_lien', 'charte_benevolat_fichier', 'contrat_intervention_lien', 'contrat_intervention_fichier',
            'date_signee', 'cv_lien', 'cv_fichier', 'rib_lien', 'rib_fichier',
            'assurance_lien', 'assurance_fichier', 'assurance_date',
            'projet_developpement', 'projet_developpement_fichier', 'objectifs_mavka', 'photo', 'email', 'actif',
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

        $champs_documents = ['charte_benevolat_fichier', 'contrat_intervention_fichier', 'cv_fichier', 'rib_fichier', 'assurance_fichier', 'projet_developpement_fichier'];
        $ins_version = db()->prepare('INSERT INTO intervenant_document_versions (intervenant_id, champ, fichier) VALUES (?,?,?)');
        foreach ([...$champs_documents, 'photo'] as $f) {
            $uploaded = handle_upload($f, $subdir);
            if ($uploaded) {
                $iv[$f] = $uploaded;
                // La photo de profil n'a pas besoin d'historique, seulement les documents administratifs.
                if (in_array($f, $champs_documents, true)) {
                    $ins_version->execute([$id, $f, $uploaded]);
                }
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
$historique_par_champ = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM intervenant_ateliers WHERE intervenant_id = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$id]);
    $ateliers = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM intervenant_document_versions WHERE intervenant_id = ? ORDER BY created_at DESC');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $v) {
        $historique_par_champ[$v['champ']][] = $v;
    }
}

$file_url = fn($f) => !empty($iv[$f]) ? '/assets/uploads/intervenants/' . $iv['dossier'] . '/' . $iv[$f] : null;
$hist = fn($f) => $historique_par_champ[$f] ?? [];

admin_header($id ? "Modifier l'intervenant·e" : 'Nouvel·le intervenant·e', $user, 'intervenants');
?>
<h1><?= $id ? "Modifier l'intervenant·e" : "Nouvel·le intervenant·e" ?></h1>

<?php if ($id && $iv['nom']): ?>
<div class="mavka-sticky-identity">
  <?php if ($u = $file_url('photo')): ?>
    <img src="<?= $u ?>" alt="">
  <?php else: ?>
    <div class="mavka-sticky-identity__placeholder"><?= htmlspecialchars(mb_strtoupper(mb_substr($iv['nom'], 0, 1))) ?></div>
  <?php endif; ?>
  <div>
    <div class="mavka-sticky-identity__name"><?= htmlspecialchars($iv['nom']) ?></div>
    <?php if ($iv['role_titre']): ?><div class="mavka-sticky-identity__role"><?= htmlspecialchars($iv['role_titre']) ?></div><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:-4px 0 14px;">Clique un titre pour replier/déplier un groupe · glisse-le par sa poignée <span class="mavka-form-section__grip" style="color:var(--mavka-color-text-muted);">⠿⠿</span> pour réordonner.</p>

<form method="post" enctype="multipart/form-data" class="mavka-form" style="max-width:720px;">
  <input type="hidden" name="action" value="save">

  <div class="mavka-form-stack" id="mavka-section-stack">

  <details class="mavka-form-section mavka-form-section--identite" data-section="identite" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🪪 Identité</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <label>Nom</label>
    <input type="text" name="nom" value="<?= htmlspecialchars($iv['nom']) ?>" required>

    <label>Rôle</label>
    <input type="text" name="role_titre" placeholder="Bénévole" value="<?= htmlspecialchars($iv['role_titre'] ?? '') ?>">

    <label>Domaine <span style="font-weight:400; color:var(--mavka-color-text-muted);">(plusieurs choix possibles)</span></label>
    <div class="mavka-picklist" style="max-height:none;">
      <?php $domaines_actuels = array_filter(explode(',', $iv['domaine'] ?? '')); ?>
      <?php foreach ($domaines_disponibles as $d): ?>
      <label class="mavka-picklist__item">
        <input type="checkbox" name="domaine[]" value="<?= $d ?>" <?= in_array($d, $domaines_actuels) ? 'checked' : '' ?>>
        <span><?= $d ?></span>
      </label>
      <?php endforeach; ?>
    </div>

    <label>Résumé (courte description affichée sur la carte, sous le rôle)</label>
    <input type="text" name="resume" placeholder="Développement personnel, accompagnement des intervenants, Parcours MAVKA" value="<?= htmlspecialchars($iv['resume'] ?? '') ?>" maxlength="300">

    <label>Statut (calculé automatiquement)</label>
    <div style="display:flex; gap:6px; padding:4px 0 10px;">
      <?php foreach (intervenant_statuts($iv) as $s): ?>
      <span class="mavka-badge mavka-badge--success"><?= htmlspecialchars($s) ?></span>
      <?php endforeach; ?>
    </div>
    <p class="mavka-form-section__hint">Bénévole apparaît quand "Charte du bénévolat" est rempli, Intervenant quand "Contrat d'intervention" est rempli (lien ou fichier) — voir Documents. Se met à jour après enregistrement.</p>

    <label>Adresse</label>
    <input type="text" name="adresse" placeholder="16000 Angoulême" value="<?= htmlspecialchars($iv['adresse'] ?? '') ?>">

    <label style="margin-top:16px;"><input type="checkbox" name="actif" <?= $iv['actif'] ? 'checked' : '' ?> style="width:auto;"> Actif (visible dans les listes)</label>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--public" data-section="public" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🌍 Contenu public de la page volontaire</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Ce que voient les visiteurs du site, dans les 3 onglets de sa page.</p>
    <label>Présentation (Bio)</label>
    <textarea name="bio"><?= htmlspecialchars($iv['bio'] ?? '') ?></textarea>
    <label>Parcours (son histoire personnelle)</label>
    <textarea name="parcours_personnel"><?= htmlspecialchars($iv['parcours_personnel'] ?? '') ?></textarea>
    <label>Ma vision</label>
    <textarea name="vision"><?= htmlspecialchars($iv['vision'] ?? '') ?></textarea>
    <label>Email de contact</label>
    <input type="email" name="email" value="<?= htmlspecialchars($iv['email'] ?? '') ?>">
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--documents" data-section="documents" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">📄 Documents</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Pour chaque document : un lien Google Drive, un fichier téléversé ici, ou les deux.</p>

    <?php champ_document('Charte du bénévolat', 'charte_benevolat_lien', 'charte_benevolat_fichier', $iv, $file_url('charte_benevolat_fichier'), $iv['dossier'] ?? null, $hist('charte_benevolat_fichier')); ?>
    <div style="margin-top:18px;"><?php champ_document("Contrat d'intervention", 'contrat_intervention_lien', 'contrat_intervention_fichier', $iv, $file_url('contrat_intervention_fichier'), $iv['dossier'] ?? null, $hist('contrat_intervention_fichier')); ?></div>

    <label style="margin-top:18px;">Date signée</label>
    <div class="mavka-date-field"><input type="date" name="date_signee" value="<?= htmlspecialchars($iv['date_signee'] ?? '') ?>"></div>

    <div style="margin-top:18px;"><?php champ_document('CV', 'cv_lien', 'cv_fichier', $iv, $file_url('cv_fichier'), $iv['dossier'] ?? null, $hist('cv_fichier')); ?></div>

    <div style="margin-top:18px;"><?php champ_document('RIB (coordonnées bancaires)', 'rib_lien', 'rib_fichier', $iv, $file_url('rib_fichier'), $iv['dossier'] ?? null, $hist('rib_fichier')); ?></div>
    <p class="mavka-form-section__hint">Pour verser les remboursements/rémunérations.</p>

    <div style="margin-top:18px;"><?php champ_document('Assurance professionnelle', 'assurance_lien', 'assurance_fichier', $iv, $file_url('assurance_fichier'), $iv['dossier'] ?? null, $hist('assurance_fichier')); ?></div>
    <label style="margin-top:10px;">Date d'échéance <span style="font-weight:400; color:var(--mavka-color-text-muted);">(facultatif — laisse vide si elle se renouvelle automatiquement)</span></label>
    <div class="mavka-date-field"><input type="date" name="assurance_date" value="<?= htmlspecialchars($iv['assurance_date'] ?? '') ?>"></div>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--photo" data-section="photo" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🖼️ Photo de profil</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <label class="mavka-photo-edit" for="photo_input">
      <span class="mavka-photo-edit__preview" id="photo_preview">
        <?php if ($u = $file_url('photo')): ?>
          <img src="<?= $u ?>" alt="">
        <?php else: ?>
          <?= htmlspecialchars(mb_strtoupper(mb_substr($iv['nom'] ?: '?', 0, 1))) ?>
        <?php endif; ?>
      </span>
      <span class="mavka-photo-edit__badge">✎</span>
    </label>
    <input type="file" id="photo_input" name="photo" accept="image/png,image/jpeg,image/webp" hidden>
    <p class="mavka-form-section__hint" style="margin-top:10px;">Clique la photo pour la changer.</p>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--interne" data-section="interne" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🔒 Suivi interne</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Pour toi et la mairie — jamais affiché sur le site.</p>
    <?php champ_document('Mon projet de développement', 'projet_developpement', 'projet_developpement_fichier', $iv, $file_url('projet_developpement_fichier'), $iv['dossier'] ?? null, $hist('projet_developpement_fichier')); ?>
    <label style="margin-top:16px;">Mes objectifs avec MAVKA</label>
    <textarea name="objectifs_mavka"><?= htmlspecialchars($iv['objectifs_mavka'] ?? '') ?></textarea>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--acces" data-section="acces" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🔑 Accès espace bénévole</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Si rempli, cette personne pourra se connecter avec Google (avec cette adresse exacte) et voir ses propres activités. Vide = pas d'accès.</p>
    <label>Email Google de connexion</label>
    <input type="email" name="login_email" placeholder="prenom.nom@gmail.com" value="<?= htmlspecialchars($login_email) ?>">
    </div>
  </details>

  <?php if ($id): ?>
  <details class="mavka-form-section mavka-form-section--ateliers" data-section="ateliers" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🎨 Ce que je propose</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Liste des ateliers possibles (pas forcément programmés) — écrite une fois, rarement modifiée. Différent des Activités réelles avec une date, gérées dans "Activités".</p>

    <div style="display:flex; flex-direction:column; gap:10px; margin:10px 0 20px;">
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

    <label>Titre de l'atelier</label>
    <input type="text" name="atelier_titre" form="atelier-form" required>
    <label>Description</label>
    <textarea name="atelier_description" form="atelier-form"></textarea>
    <button type="submit" form="atelier-form" class="mavka-btn mavka-btn--primary" style="margin-top:16px;">Ajouter</button>
    </div>
  </details>
  <?php endif; ?>

  </div>

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:8px;">Enregistrer</button>
  <a href="/admin/intervenants.php" class="mavka-btn" style="margin-top:8px;">Annuler</a>
</form>
<?php if ($id): ?>
<form method="post" id="atelier-form"><input type="hidden" name="action" value="add_atelier"></form>
<?php endif; ?>

<script>
(function(){
  var ORDER_KEY = 'mavka-iv-section-order';
  var STATE_KEY = 'mavka-iv-section-state';
  var stack = document.getElementById('mavka-section-stack');
  if (!stack) return;

  // Ordre mémorisé (affecte seulement les groupes du formulaire principal)
  try {
    var order = JSON.parse(localStorage.getItem(ORDER_KEY) || 'null');
    if (order) {
      order.forEach(function(key){
        var el = stack.querySelector('[data-section="' + key + '"]');
        if (el) stack.appendChild(el);
      });
    }
  } catch (e) {}

  // État replié/déplié mémorisé (tous les groupes, y compris "Ce que je propose")
  var tousLesGroupes = document.querySelectorAll('.mavka-form-section');
  try {
    var state = JSON.parse(localStorage.getItem(STATE_KEY) || '{}');
    tousLesGroupes.forEach(function(sec){
      var key = sec.dataset.section;
      if (key in state) sec.open = state[key];
    });
  } catch (e) {}

  tousLesGroupes.forEach(function(sec){
    sec.addEventListener('toggle', function(){
      try {
        var state = JSON.parse(localStorage.getItem(STATE_KEY) || '{}');
        state[sec.dataset.section] = sec.open;
        localStorage.setItem(STATE_KEY, JSON.stringify(state));
      } catch (e) {}
    });
  });

  // Glisser-déposer pour réordonner les groupes du formulaire principal
  var dragged = null;
  stack.querySelectorAll(':scope > .mavka-form-section > summary').forEach(function(handle){
    handle.setAttribute('draggable', 'true');
    handle.addEventListener('dragstart', function(e){
      dragged = handle.closest('.mavka-form-section');
      dragged.classList.add('mavka-dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    handle.addEventListener('dragend', function(){
      if (dragged) dragged.classList.remove('mavka-dragging');
      dragged = null;
    });
  });
  stack.addEventListener('dragover', function(e){
    if (!dragged) return;
    e.preventDefault();
    var target = e.target.closest('.mavka-form-section');
    if (!target || target === dragged || target.parentElement !== stack) return;
    var rect = target.getBoundingClientRect();
    var after = (e.clientY - rect.top) > rect.height / 2;
    stack.insertBefore(dragged, after ? target.nextSibling : target);
  });
  stack.addEventListener('drop', function(e){
    e.preventDefault();
    try {
      var keys = Array.from(stack.children).map(function(s){ return s.dataset.section; });
      localStorage.setItem(ORDER_KEY, JSON.stringify(keys));
    } catch (e) {}
  });

  // Clic sur le crayon d'un lien Google Drive : révèle le champ pour le modifier
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.mavka-link-chip__edit');
    if (!btn) return;
    var input = document.getElementById(btn.dataset.toggle);
    if (input) { input.hidden = false; input.focus(); input.select(); }
    var chip = btn.closest('.mavka-link-chip');
    if (chip) { chip.hidden = true; }
  });

  // Aperçu immédiat de la photo choisie — sans ça, rien ne se voit avant l'enregistrement,
  // ce qui pousse à recliquer sur la photo "pour vérifier" ; si ce second choix est annulé,
  // le navigateur vide le champ fichier et la photo initialement choisie est perdue.
  var photoInput = document.getElementById('photo_input');
  var photoPreview = document.getElementById('photo_preview');
  if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function () {
      var file = photoInput.files && photoInput.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        photoPreview.innerHTML = '';
        var img = document.createElement('img');
        img.src = e.target.result;
        img.alt = '';
        photoPreview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  }
})();
</script>
<?php admin_footer(); ?>
