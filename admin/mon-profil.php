<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Profil "hybride" : le·la volontaire modifie lui/elle-même ses données de contact et sa page
// publique (nom excepté) + peut téléverser son propre CV. Les documents officiels (Charte,
// Contrat, RIB, Assurance) restent en lecture seule ici — c'est admin/intervenant-form.php,
// réservé à super_admin/mavka_admin, qui les gère. Le suivi interne (projet de développement,
// objectifs — jamais affiché sur le site) n'apparaît pas du tout sur cette page.
$user = auth_require();

$stmt = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$stmt->execute([$user['id']]);
$intervenant_id = (int)$stmt->fetchColumn();

if (!$intervenant_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$domaines_disponibles = ['Culture', 'Éducation', 'Bien-être', 'Développement personnel'];

$stmt = db()->prepare('SELECT * FROM intervenants WHERE id = ?');
$stmt->execute([$intervenant_id]);
$iv = $stmt->fetch();
if (!$iv) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iv['resume'] = trim($_POST['resume'] ?? '');
    $iv['domaine'] = implode(',', array_map('trim', $_POST['domaine'] ?? [])) ?: null;
    $iv['adresse'] = trim($_POST['adresse'] ?? '');
    $iv['bio'] = trim($_POST['bio'] ?? '');
    $iv['parcours_personnel'] = trim($_POST['parcours_personnel'] ?? '');
    $iv['vision'] = trim($_POST['vision'] ?? '');
    $iv['email'] = trim($_POST['email'] ?? '');
    $iv['cv_lien'] = trim($_POST['cv_lien'] ?? '');

    $fields = ['resume', 'domaine', 'adresse', 'bio', 'parcours_personnel', 'vision', 'email', 'cv_lien', 'cv_fichier', 'photo'];

    if (empty($iv['dossier'])) {
        $iv['dossier'] = intervenant_dossier($intervenant_id, $iv['nom']);
        db()->prepare('UPDATE intervenants SET dossier = ? WHERE id = ?')->execute([$iv['dossier'], $intervenant_id]);
    }
    $subdir = 'intervenants/' . $iv['dossier'];

    foreach (['cv_fichier', 'photo'] as $f) {
        $uploaded = handle_upload($f, $subdir);
        if ($uploaded) {
            $iv[$f] = $uploaded;
            if ($f === 'cv_fichier') {
                db()->prepare('INSERT INTO intervenant_document_versions (intervenant_id, champ, fichier) VALUES (?,?,?)')
                    ->execute([$intervenant_id, 'cv_fichier', $uploaded]);
            }
        }
    }

    $set = implode(', ', array_map(fn($f) => "$f = ?", $fields));
    $stmt = db()->prepare("UPDATE intervenants SET $set WHERE id = ?");
    $stmt->execute([...array_map(fn($f) => $iv[$f], $fields), $intervenant_id]);

    header('Location: /admin/mon-profil.php?ok=1');
    exit;
}

$stmt = db()->prepare('SELECT * FROM intervenant_document_versions WHERE intervenant_id = ? AND champ = ? ORDER BY created_at DESC');
$stmt->execute([$intervenant_id, 'cv_fichier']);
$cv_historique = $stmt->fetchAll();

$file_url = fn(string $f) => !empty($iv[$f]) ? '/assets/uploads/intervenants/' . $iv['dossier'] . '/' . $iv[$f] : null;

// Documents officiels : lecture seule ici, juste de quoi vérifier que c'est bien fourni —
// modifiables uniquement par un·e admin dans admin/intervenant-form.php.
function mon_profil_document_statut(string $label, ?string $lien, ?string $file_url): void {
    $fourni = !empty($lien) || !empty($file_url);
    ?>
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid var(--mavka-color-cream-soft);">
      <span><?= htmlspecialchars($label) ?></span>
      <?php if ($fourni): ?>
        <span class="mavka-fill-yes">✓ Fourni <?php if ($lien): ?><a href="<?= htmlspecialchars($lien) ?>" target="_blank" rel="noopener">(lien)</a><?php endif; ?><?php if ($file_url): ?> <a href="<?= htmlspecialchars($file_url) ?>" target="_blank" rel="noopener">(fichier)</a><?php endif; ?></span>
      <?php else: ?>
        <span class="mavka-fill-no">— Pas encore</span>
      <?php endif; ?>
    </div>
    <?php
}

admin_header('Mon profil', $user, 'mon-profil');
?>
<h1>Mon profil</h1>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mavka-form" style="max-width:640px;">
  <details class="mavka-form-section" open>
    <summary class="mavka-form-section__header">
      <h3 class="mavka-form-section__title">🪪 Mes informations</h3>
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

    <label style="margin-top:16px;">Nom</label>
    <input type="text" value="<?= htmlspecialchars($iv['nom']) ?>" disabled>
    <p class="mavka-form-section__hint">Pour changer ton nom, écris à Larysa.</p>

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

    <label>Résumé (courte description affichée sur ta carte)</label>
    <input type="text" name="resume" value="<?= htmlspecialchars($iv['resume'] ?? '') ?>" maxlength="300">

    <label>Adresse</label>
    <input type="text" name="adresse" placeholder="16000 Angoulême" value="<?= htmlspecialchars($iv['adresse'] ?? '') ?>">

    <label>Email de contact</label>
    <input type="email" name="email" value="<?= htmlspecialchars($iv['email'] ?? '') ?>">
    </div>
  </details>

  <details class="mavka-form-section" open>
    <summary class="mavka-form-section__header">
      <h3 class="mavka-form-section__title">🌍 Ma page publique</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Ce que voient les visiteurs du site, dans les 3 onglets de ta page.</p>
    <label>Présentation</label>
    <textarea name="bio"><?= htmlspecialchars($iv['bio'] ?? '') ?></textarea>
    <label>Parcours (ton histoire personnelle)</label>
    <textarea name="parcours_personnel"><?= htmlspecialchars($iv['parcours_personnel'] ?? '') ?></textarea>
    <label>Ma vision</label>
    <textarea name="vision"><?= htmlspecialchars($iv['vision'] ?? '') ?></textarea>
    </div>
  </details>

  <details class="mavka-form-section" open>
    <summary class="mavka-form-section__header">
      <h3 class="mavka-form-section__title">📄 Mon CV</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <?php champ_document('CV', 'cv_lien', 'cv_fichier', $iv, $file_url('cv_fichier'), $iv['dossier'] ?? null, $cv_historique); ?>
    </div>
  </details>

  <details class="mavka-form-section" open>
    <summary class="mavka-form-section__header">
      <h3 class="mavka-form-section__title">🔒 Mes documents officiels</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Gérés par Larysa — écris-lui pour les fournir ou les mettre à jour.</p>
    <?php
      mon_profil_document_statut('Charte du bénévolat', $iv['charte_benevolat_lien'] ?? null, $file_url('charte_benevolat_fichier'));
      mon_profil_document_statut("Contrat d'intervention", $iv['contrat_intervention_lien'] ?? null, $file_url('contrat_intervention_fichier'));
      mon_profil_document_statut('RIB (coordonnées bancaires)', $iv['rib_lien'] ?? null, $file_url('rib_fichier'));
      mon_profil_document_statut('Assurance professionnelle', $iv['assurance_lien'] ?? null, $file_url('assurance_fichier'));
    ?>
    </div>
  </details>

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:16px;">Enregistrer</button>
</form>

<script>
(function () {
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.mavka-link-chip__edit');
    if (!btn) return;
    var input = document.getElementById(btn.dataset.toggle);
    if (input) { input.hidden = false; input.focus(); input.select(); }
    var chip = btn.closest('.mavka-link-chip');
    if (chip) { chip.hidden = true; }
  });

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
