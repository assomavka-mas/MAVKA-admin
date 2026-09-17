<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$iv = [
    'nom' => '', 'dossier' => '', 'resume' => '', 'domaine' => '', 'adresse' => '',
    'bio' => '', 'parcours_personnel' => '', 'vision' => '',
    'charte_benevolat_lien' => '', 'charte_benevolat_fichier' => null,
    'contrat_intervention_lien' => '', 'contrat_intervention_fichier' => null,
    'date_signee' => '',
    'cv_lien' => '', 'cv_fichier' => null,
    'rib_lien' => '', 'rib_fichier' => null,
    'assurance_lien' => '', 'assurance_fichier' => null, 'assurance_date' => '',
    'numero_siret' => '', 'piece_identite_fichier' => null, 'piece_identite_date_verification' => '',
    'droit_exercer_necessaire' => 0, 'droit_exercer_fichier' => null, 'avis_sirene_fichier' => null,
    'b3_presente' => 0, 'b3_date' => null, 'b3_verifie_par' => null,
    'date_entree_prestataire' => '', 'dossier_prestataire_maj_le' => null,
    'projet_developpement' => '', 'projet_developpement_fichier' => null, 'projet_developpement_description' => '', 'objectifs_mavka' => '',
    'statut_qualifications' => 'non_requis',
    'photo' => null, 'avatar_mavka' => null,
    'avatar_domaine_culture' => null, 'avatar_domaine_education' => null, 'avatar_domaine_bien_etre' => null, 'avatar_domaine_initiatives' => null,
    'email' => '', 'actif' => 1,
];
$domaines_disponibles = ['Culture', 'Éducation', 'Bien-être', 'Initiatives'];
// Bibliothèque personnelle de visuels (un par domaine coché) que Larysa pose ensuite à la main
// sur les cartes d'activité concernées — aucun lien automatique avec les activités elles-mêmes.
$domaine_avatar_champs = [
    'Culture' => 'avatar_domaine_culture',
    'Éducation' => 'avatar_domaine_education',
    'Bien-être' => 'avatar_domaine_bien_etre',
    'Initiatives' => 'avatar_domaine_initiatives',
];
$qualification_types = [
    'diplome' => 'Diplôme', 'attestation' => 'Attestation', 'certification' => 'Certification',
    'reconnaissance' => 'Reconnaissance / équivalence', 'autorisation' => 'Autorisation', 'autre' => 'Autre',
];
$qualification_statuts = ['a_verifier' => 'À vérifier', 'verifie' => 'Vérifié', 'a_completer' => 'À compléter'];
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
    // Pour savoir si le Dossier Prestataire a réellement changé (→ dossier_prestataire_maj_le),
    // il faut comparer à l'état AVANT d'écraser $iv avec les valeurs du formulaire ci-dessous.
    $champs_dossier_prestataire = [
        'numero_siret', 'piece_identite_fichier', 'piece_identite_date_verification',
        'droit_exercer_necessaire', 'droit_exercer_fichier', 'avis_sirene_fichier',
        'b3_presente', 'b3_date', 'assurance_fichier', 'assurance_date',
    ];
    $dossier_prestataire_avant = array_intersect_key($iv, array_flip($champs_dossier_prestataire));

    $iv['nom'] = trim($_POST['nom'] ?? '');
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
    $iv['numero_siret'] = trim($_POST['numero_siret'] ?? '');
    $iv['piece_identite_date_verification'] = $_POST['piece_identite_date_verification'] ?: null;
    $iv['droit_exercer_necessaire'] = isset($_POST['droit_exercer_necessaire']) ? 1 : 0;
    $iv['date_entree_prestataire'] = $_POST['date_entree_prestataire'] ?: null;
    // B3 : date et vérificateur s'auto-remplissent au moment où la case passe à cochée (jamais
    // saisis à la main) — et se réinitialisent si on la décoche (l'info n'est alors plus à jour).
    $b3_avant = (int)($iv['b3_presente'] ?? 0);
    $iv['b3_presente'] = isset($_POST['b3_presente']) ? 1 : 0;
    if ($iv['b3_presente'] && !$b3_avant) {
        $iv['b3_date'] = date('Y-m-d');
        $iv['b3_verifie_par'] = $user['email'];
    } elseif (!$iv['b3_presente']) {
        $iv['b3_date'] = null;
        $iv['b3_verifie_par'] = null;
    }
    $iv['projet_developpement'] = trim($_POST['projet_developpement'] ?? '');
    $iv['projet_developpement_description'] = trim($_POST['projet_developpement_description'] ?? '');
    $iv['objectifs_mavka'] = trim($_POST['objectifs_mavka'] ?? '');
    $iv['statut_qualifications'] = in_array($_POST['statut_qualifications'] ?? '', ['non_requis', 'a_verifier', 'verifie', 'a_completer'], true)
        ? $_POST['statut_qualifications'] : 'non_requis';
    $iv['email'] = trim($_POST['email'] ?? '');
    $iv['actif'] = isset($_POST['actif']) ? 1 : 0;
    $new_login_email = strtolower(trim($_POST['login_email'] ?? ''));

    if ($iv['nom'] === '') {
        $error = 'Le nom est obligatoire.';
    } else {
        $fields = [
            'nom', 'dossier', 'resume', 'domaine', 'adresse',
            'bio', 'parcours_personnel', 'vision',
            'charte_benevolat_lien', 'charte_benevolat_fichier', 'contrat_intervention_lien', 'contrat_intervention_fichier',
            'date_signee', 'cv_lien', 'cv_fichier', 'rib_lien', 'rib_fichier',
            'assurance_lien', 'assurance_fichier', 'assurance_date',
            'numero_siret', 'piece_identite_fichier', 'piece_identite_date_verification',
            'droit_exercer_necessaire', 'droit_exercer_fichier', 'avis_sirene_fichier',
            'b3_presente', 'b3_date', 'b3_verifie_par', 'date_entree_prestataire', 'dossier_prestataire_maj_le',
            'projet_developpement', 'projet_developpement_fichier', 'projet_developpement_description', 'objectifs_mavka',
            'statut_qualifications', 'photo', 'avatar_mavka',
            'avatar_domaine_culture', 'avatar_domaine_education', 'avatar_domaine_bien_etre', 'avatar_domaine_initiatives',
            'email', 'actif',
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

        $champs_documents = ['charte_benevolat_fichier', 'contrat_intervention_fichier', 'cv_fichier', 'rib_fichier', 'assurance_fichier', 'projet_developpement_fichier', 'piece_identite_fichier', 'droit_exercer_fichier', 'avis_sirene_fichier'];
        $ins_version = db()->prepare('INSERT INTO intervenant_document_versions (intervenant_id, champ, fichier) VALUES (?,?,?)');
        $champs_avatars = ['avatar_mavka', 'avatar_domaine_culture', 'avatar_domaine_education', 'avatar_domaine_bien_etre', 'avatar_domaine_initiatives'];
        foreach ([...$champs_documents, 'photo', ...$champs_avatars] as $f) {
            $uploaded = handle_upload($f, $subdir);
            if ($uploaded) {
                $iv[$f] = $uploaded;
                // La photo de profil n'a pas besoin d'historique, seulement les documents administratifs.
                if (in_array($f, $champs_documents, true)) {
                    $ins_version->execute([$id, $f, $uploaded]);
                }
                // Avatars : on retire automatiquement le fond blanc pour un rendu détouré.
                if (in_array($f, $champs_avatars, true)) {
                    retirer_fond_blanc(__DIR__ . '/../assets/uploads/' . $subdir . '/' . $uploaded);
                }
            }
        }

        $dossier_prestataire_apres = array_intersect_key($iv, array_flip($champs_dossier_prestataire));
        if ($dossier_prestataire_apres !== $dossier_prestataire_avant) {
            $iv['dossier_prestataire_maj_le'] = date('Y-m-d H:i:s');
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

// Gestion de la galerie publique (photos de réalisations / de l'atelier, page volontaire).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_galerie' && $id) {
    if (empty($iv['dossier'])) {
        $iv['dossier'] = intervenant_dossier($id, $iv['nom']);
        db()->prepare('UPDATE intervenants SET dossier = ? WHERE id = ?')->execute([$iv['dossier'], $id]);
    }
    $fichiers = handle_multi_upload('galerie_images', 'intervenants/' . $iv['dossier'] . '/galerie');
    if ($fichiers) {
        $stmt = db()->prepare('SELECT COALESCE(MAX(ordre), -1) FROM intervenant_galerie WHERE intervenant_id = ?');
        $stmt->execute([$id]);
        $ordre = (int)$stmt->fetchColumn();
        $ins = db()->prepare('INSERT INTO intervenant_galerie (intervenant_id, image, ordre) VALUES (?,?,?)');
        foreach ($fichiers as $f) {
            $ins->execute([$id, $f, ++$ordre]);
        }
    }
    header('Location: /admin/intervenant-form.php?id=' . $id . '&ok=1');
    exit;
}
if (isset($_GET['delete_galerie']) && $id) {
    $stmt = db()->prepare('SELECT image FROM intervenant_galerie WHERE id = ? AND intervenant_id = ?');
    $stmt->execute([(int)$_GET['delete_galerie'], $id]);
    if ($image = $stmt->fetchColumn()) {
        db()->prepare('DELETE FROM intervenant_galerie WHERE id = ? AND intervenant_id = ?')
            ->execute([(int)$_GET['delete_galerie'], $id]);
        @unlink(__DIR__ . '/../assets/uploads/intervenants/' . $iv['dossier'] . '/galerie/' . $image);
    }
    header('Location: /admin/intervenant-form.php?id=' . $id);
    exit;
}

// Gestion de "Qualifications et justificatifs" — section privée, jamais publique (voir
// intervenant.php : ce bloc n'y apparaît nulle part). N'importe quel·le éditeur·ice (super_admin
// ou mavka_admin) peut ajouter/supprimer une ligne ; seul super_admin peut la faire passer
// "Vérifié", ce qui remplit automatiquement date_verification et verifie_par (jamais saisis à
// la main), et éditer la note administrative privée.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_qualification' && $id) {
    if (empty($iv['dossier'])) {
        $iv['dossier'] = intervenant_dossier($id, $iv['nom']);
        db()->prepare('UPDATE intervenants SET dossier = ? WHERE id = ?')->execute([$iv['dossier'], $id]);
    }
    $type = $_POST['qualification_type'] ?? '';
    $intitule = trim($_POST['qualification_intitule'] ?? '');
    $types_valides = ['diplome', 'attestation', 'certification', 'reconnaissance', 'autorisation', 'autre'];
    if (in_array($type, $types_valides, true) && $intitule !== '') {
        $fichier = handle_upload('qualification_fichier', 'intervenants/' . $iv['dossier'] . '/qualifications');
        db()->prepare('INSERT INTO intervenant_qualifications (intervenant_id, type_justificatif, intitule, organisme, pays, annee_obtention, fichier) VALUES (?,?,?,?,?,?,?)')
            ->execute([
                $id, $type, $intitule,
                trim($_POST['qualification_organisme'] ?? '') ?: null,
                trim($_POST['qualification_pays'] ?? '') ?: null,
                (int)($_POST['qualification_annee'] ?? 0) ?: null,
                $fichier,
            ]);
    }
    header('Location: /admin/intervenant-form.php?id=' . $id . '&ok=1');
    exit;
}
// Changer le statut (dont "Vérifié", réservé à super_admin) et la note administrative se fait
// en direct via admin/qualification-inline-update.php (JS, plus bas) — pas ici, pour éviter un
// <form> imbriqué dans le formulaire principal de la page.
if (isset($_GET['delete_qualification']) && $id) {
    $stmt = db()->prepare('SELECT fichier FROM intervenant_qualifications WHERE id = ? AND intervenant_id = ?');
    $stmt->execute([(int)$_GET['delete_qualification'], $id]);
    if (($fichier = $stmt->fetchColumn()) !== false) {
        db()->prepare('DELETE FROM intervenant_qualifications WHERE id = ? AND intervenant_id = ?')
            ->execute([(int)$_GET['delete_qualification'], $id]);
        if ($fichier) {
            @unlink(__DIR__ . '/../assets/uploads/intervenants/' . $iv['dossier'] . '/qualifications/' . $fichier);
        }
    }
    header('Location: /admin/intervenant-form.php?id=' . $id);
    exit;
}

$ateliers = [];
$galerie = [];
$qualifications = [];
$historique_par_champ = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM intervenant_ateliers WHERE intervenant_id = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$id]);
    $ateliers = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM intervenant_galerie WHERE intervenant_id = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$id]);
    $galerie = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM intervenant_qualifications WHERE intervenant_id = ? ORDER BY created_at DESC');
    $stmt->execute([$id]);
    $qualifications = $stmt->fetchAll();

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

    <label>Résumé (courte description affichée sur la carte — peut inclure le rôle, ex. « Présidente, accompagnement des intervenants »)</label>
    <input type="text" name="resume" placeholder="Initiatives, accompagnement des intervenants, Parcours MAVKA" value="<?= htmlspecialchars($iv['resume'] ?? '') ?>" maxlength="300">

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
    <p class="mavka-form-section__hint" style="margin-top:4px;"><a href="<?= MAVKA_MODELE_CHARTE_BENEVOLAT_URL ?>" target="_blank" rel="noopener">📄 Modèle vierge</a> — la personne le voit aussi depuis son "Mon profil".</p>
    <div style="margin-top:18px;">
      <?php champ_document("Contrat d'intervention", 'contrat_intervention_lien', 'contrat_intervention_fichier', $iv, $file_url('contrat_intervention_fichier'), $iv['dossier'] ?? null, $hist('contrat_intervention_fichier')); ?>
      <p class="mavka-form-section__hint" style="margin-top:4px;"><a href="<?= MAVKA_MODELE_CONTRAT_INTERVENTION_URL ?>" target="_blank" rel="noopener">📄 Modèle vierge</a> — la personne le voit aussi depuis son "Mon profil".</p>
    </div>

    <label style="margin-top:18px;">Date signée</label>
    <div class="mavka-date-field"><input type="date" name="date_signee" value="<?= htmlspecialchars($iv['date_signee'] ?? '') ?>"></div>

    <div style="margin-top:18px;"><?php champ_document('CV', 'cv_lien', 'cv_fichier', $iv, $file_url('cv_fichier'), $iv['dossier'] ?? null, $hist('cv_fichier')); ?></div>

    <div style="margin-top:18px;"><?php champ_document('RIB (coordonnées bancaires)', 'rib_lien', 'rib_fichier', $iv, $file_url('rib_fichier'), $iv['dossier'] ?? null, $hist('rib_fichier')); ?></div>
    <p class="mavka-form-section__hint">Pour verser les remboursements/rémunérations.</p>

    <div style="margin-top:18px;"><?php champ_document('RC Professionnelle (assurance)', 'assurance_lien', 'assurance_fichier', $iv, $file_url('assurance_fichier'), $iv['dossier'] ?? null, $hist('assurance_fichier')); ?></div>
    <label style="margin-top:10px;">Date d'échéance <span style="font-weight:400; color:var(--mavka-color-text-muted);">(déclenche une alerte automatique 30 jours avant, voir tableau de bord)</span></label>
    <div class="mavka-date-field"><input type="date" name="assurance_date" value="<?= htmlspecialchars($iv['assurance_date'] ?? '') ?>"></div>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--prestataire" data-section="prestataire" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🗂️ Dossier Prestataire (privé)</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">
      Vérifications administratives pour rester en règle vis-à-vis des organismes de contrôle — jamais visible sur la page publique.
      <?php if (!empty($iv['dossier_prestataire_maj_le'])): ?>Dernière mise à jour : <?= htmlspecialchars(date('d/m/Y H:i', strtotime($iv['dossier_prestataire_maj_le']))) ?>.<?php endif; ?>
    </p>

    <label>SIREN / SIRET</label>
    <input type="text" name="numero_siret" value="<?= htmlspecialchars($iv['numero_siret'] ?? '') ?>" placeholder="14 chiffres (SIRET) ou 9 (SIREN)">

    <div style="margin-top:18px;"><?php champ_fichier_seul("Pièce d'identité", 'piece_identite_fichier', $iv, $file_url('piece_identite_fichier'), $hist('piece_identite_fichier'), $iv['dossier'] ?? null); ?></div>
    <label style="margin-top:10px;">Date de vérification</label>
    <div class="mavka-date-field"><input type="date" name="piece_identite_date_verification" value="<?= htmlspecialchars($iv['piece_identite_date_verification'] ?? '') ?>"></div>

    <label style="margin-top:18px; display:flex; align-items:center; gap:8px; font-weight:600;">
      <input type="checkbox" name="droit_exercer_necessaire" <?= !empty($iv['droit_exercer_necessaire']) ? 'checked' : '' ?>>
      Droit d'exercer nécessaire pour cette personne
    </label>
    <div style="margin-top:10px;"><?php champ_fichier_seul("Justificatif du droit d'exercer", 'droit_exercer_fichier', $iv, $file_url('droit_exercer_fichier'), $hist('droit_exercer_fichier'), $iv['dossier'] ?? null); ?></div>

    <div style="margin-top:18px;"><?php champ_fichier_seul('Avis SIRENE', 'avis_sirene_fichier', $iv, $file_url('avis_sirene_fichier'), $hist('avis_sirene_fichier'), $iv['dossier'] ?? null); ?></div>

    <div style="margin-top:22px; padding-top:18px; border-top:1px solid var(--mavka-color-cream-soft);">
      <label style="display:flex; align-items:center; gap:8px; font-weight:600;">
        <input type="checkbox" name="b3_presente" <?= !empty($iv['b3_presente']) ? 'checked' : '' ?>>
        Bulletin n°3 du casier judiciaire (B3) présenté
      </label>
      <p class="mavka-form-section__hint" style="margin-top:4px;">Le document lui-même n'est jamais conservé ici — seulement la trace que la vérification a eu lieu.</p>
      <?php if (!empty($iv['b3_presente']) && !empty($iv['b3_date'])): ?>
      <p style="font-size:13px; color:var(--mavka-color-text-muted); margin-top:6px;">Vérifié le <?= htmlspecialchars(date('d/m/Y', strtotime($iv['b3_date']))) ?> par <?= htmlspecialchars($iv['b3_verifie_par'] ?? '') ?>.</p>
      <?php endif; ?>
    </div>

    <label style="margin-top:18px;">Date d'entrée comme prestataire</label>
    <div class="mavka-date-field"><input type="date" name="date_entree_prestataire" value="<?= htmlspecialchars($iv['date_entree_prestataire'] ?? '') ?>"></div>

    <p class="mavka-form-section__hint" style="margin-top:18px;">RC Professionnelle et Contrat-cadre : voir la section "📄 Documents" plus haut (RC Professionnelle / Contrat d'intervention) — pas de doublon ici.</p>
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

    <div style="margin-top:22px; padding-top:18px; border-top:1px solid var(--mavka-color-cream-soft);">
      <label>MAVKA-avatar <span style="font-weight:400; color:var(--mavka-color-text-muted);">(illustration, pas une photo — utilisée dans le bloc "Qui est [Nom]" de sa page publique)</span></label>
      <p class="mavka-form-section__hint" style="margin-top:-4px; margin-bottom:8px;">PNG uniquement — le fond blanc est retiré automatiquement à l'envoi.</p>
      <label class="mavka-photo-edit" for="avatar_mavka_input">
        <span class="mavka-photo-edit__preview" id="avatar_mavka_preview">
          <?php if ($u = $file_url('avatar_mavka')): ?>
            <img src="<?= $u ?>" alt="">
          <?php else: ?>
            🎨
          <?php endif; ?>
        </span>
        <span class="mavka-photo-edit__badge">✎</span>
      </label>
      <input type="file" id="avatar_mavka_input" name="avatar_mavka" accept="image/png" hidden>
    </div>

    <?php if ($domaines_actuels): ?>
    <div style="margin-top:22px; padding-top:18px; border-top:1px solid var(--mavka-color-cream-soft);">
      <label>Avatars par direction <span style="font-weight:400; color:var(--mavka-color-text-muted);">(un visuel par domaine coché plus haut, affiché automatiquement sur les cartes d'activité de la personne quand aucune photo n'est ajoutée)</span></label>
      <p class="mavka-form-section__hint" style="margin-top:-4px; margin-bottom:8px;">PNG uniquement — le fond blanc est retiré automatiquement à l'envoi.</p>
      <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:8px;">
        <?php foreach ($domaines_actuels as $d): ?>
        <?php $champ = $domaine_avatar_champs[$d] ?? null; if (!$champ) continue; ?>
        <div style="text-align:center;">
          <label class="mavka-photo-edit" for="<?= $champ ?>_input">
            <span class="mavka-photo-edit__preview" id="<?= $champ ?>_preview">
              <?php if ($u = $file_url($champ)): ?>
                <img src="<?= $u ?>" alt="">
              <?php else: ?>
                🎨
              <?php endif; ?>
            </span>
            <span class="mavka-photo-edit__badge">✎</span>
          </label>
          <input type="file" id="<?= $champ ?>_input" name="<?= $champ ?>" accept="image/png" hidden>
          <div style="font-size:12px; color:var(--mavka-color-text-muted); margin-top:4px;"><?= htmlspecialchars($d) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--interne" data-section="interne" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🔒 Suivi interne</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Le projet de développement (description, lien et fichier ci-dessous) apparaît sur la page publique de la personne, dans un bloc "Projet personnel" — utile pour les échanges avec les mairies et partenaires. Tout vide = le bloc ne s'affiche pas du tout sur sa page.</p>
    <label>Description publique du projet</label>
    <textarea name="projet_developpement_description" placeholder="Quelques phrases présentables au public : ce que la personne construit, où elle en est."><?= htmlspecialchars($iv['projet_developpement_description'] ?? '') ?></textarea>
    <p class="mavka-form-section__hint" style="margin-top:4px;">Le texte affiché tel quel dans le bloc. Le lien/fichier ci-dessous servent de document complet, consultable en plus (ex. lien vers le document Google Docs détaillé).</p>
    <?php champ_document('Lien ou fichier du document complet', 'projet_developpement', 'projet_developpement_fichier', $iv, $file_url('projet_developpement_fichier'), $iv['dossier'] ?? null, $hist('projet_developpement_fichier')); ?>
    <label style="margin-top:16px;">Mes objectifs avec MAVKA</label>
    <p class="mavka-form-section__hint" style="margin-top:-6px;">Contrairement au projet ci-dessus : reste entre toi et la personne, jamais affiché sur le site.</p>
    <textarea name="objectifs_mavka"><?= htmlspecialchars($iv['objectifs_mavka'] ?? '') ?></textarea>

    <label style="margin-top:16px;">Statut des qualifications</label>
    <p class="mavka-form-section__hint" style="margin-top:-6px;">À cocher toi-même, au cas par cas — ne se déduit pas automatiquement de la liste "Qualifications et justificatifs" ci-dessous. Utile seulement quand une activité exige une qualification professionnelle vérifiée ; sinon laisse "Non requis".</p>
    <select name="statut_qualifications">
      <?php foreach (['non_requis' => 'Non requis', 'a_verifier' => 'À vérifier', 'verifie' => 'Vérifié', 'a_completer' => 'À compléter'] as $val => $label): ?>
      <option value="<?= $val ?>" <?= $iv['statut_qualifications'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
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

  <details class="mavka-form-section mavka-form-section--galerie" data-section="galerie" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🖼️ Galerie</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Photos de ses réalisations, de son atelier — affichées sur sa page publique. Vide = ce bloc n'apparaît pas du tout sur sa page.</p>

    <?php if ($galerie): ?>
    <div class="mavka-galerie-grid">
      <?php foreach ($galerie as $g): ?>
      <div class="mavka-galerie-item">
        <img src="/assets/uploads/intervenants/<?= htmlspecialchars($iv['dossier']) ?>/galerie/<?= htmlspecialchars($g['image']) ?>" alt="">
        <a href="/admin/intervenant-form.php?id=<?= $id ?>&delete_galerie=<?= $g['id'] ?>" class="mavka-galerie-item__delete" title="Supprimer"
           onclick="return confirm('Supprimer cette photo ?');">✕</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:var(--mavka-color-text-muted); font-size:13.5px;">Aucune photo pour l'instant.</p>
    <?php endif; ?>

    <label>Ajouter des photos</label>
    <input type="file" name="galerie_images[]" form="galerie-form" accept="image/png,image/jpeg,image/webp" multiple>
    <button type="submit" form="galerie-form" class="mavka-btn mavka-btn--primary" style="margin-top:16px;">Ajouter</button>
    </div>
  </details>

  <details class="mavka-form-section mavka-form-section--qualifications" data-section="qualifications" open>
    <summary class="mavka-form-section__header">
      <span class="mavka-form-section__grip">⠿⠿</span>
      <h3 class="mavka-form-section__title">🎓 Qualifications et justificatifs</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Section privée et interne — n'apparaît jamais sur la page publique de la personne. Pas besoin d'en ajouter pour tout le monde : seulement quand une qualification est pertinente (une activité qui l'exige, par exemple).</p>

    <div style="display:flex; flex-direction:column; gap:12px; margin:10px 0 20px;">
      <?php foreach ($qualifications as $q): ?>
      <div class="mavka-card" style="padding:14px 18px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
          <div>
            <div style="font-weight:700;"><?= htmlspecialchars($q['intitule']) ?></div>
            <div style="font-size:13.5px; color:var(--mavka-color-text-muted); margin-top:2px;">
              <?= htmlspecialchars($qualification_types[$q['type_justificatif']] ?? $q['type_justificatif']) ?>
              <?php if ($q['organisme']): ?> · <?= htmlspecialchars($q['organisme']) ?><?php endif; ?>
              <?php if ($q['pays']): ?> · <?= htmlspecialchars($q['pays']) ?><?php endif; ?>
              <?php if ($q['annee_obtention']): ?> · <?= (int)$q['annee_obtention'] ?><?php endif; ?>
            </div>
            <?php if ($q['fichier']): ?>
            <a href="/assets/uploads/intervenants/<?= htmlspecialchars($iv['dossier']) ?>/qualifications/<?= htmlspecialchars($q['fichier']) ?>" target="_blank" rel="noopener" style="font-size:13.5px;">📄 Voir le justificatif</a>
            <?php endif; ?>
          </div>
          <a href="/admin/intervenant-form.php?id=<?= $id ?>&delete_qualification=<?= $q['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
             onclick="return confirm('Supprimer cette qualification ?');">Supprimer</a>
        </div>

        <div style="margin-top:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <?php if ($user['role'] === 'super_admin' || $q['statut'] !== 'verifie'): ?>
          <select data-qualification-statut data-id="<?= $q['id'] ?>">
            <?php foreach ($qualification_statuts as $val => $label): ?>
            <?php if ($val === 'verifie' && $user['role'] !== 'super_admin') continue; ?>
            <option value="<?= $val ?>" <?= $q['statut'] === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <?php else: ?>
          <span class="mavka-badge mavka-badge--success">Vérifié</span>
          <?php endif; ?>
          <?php if ($q['statut'] === 'verifie'): ?>
          <span style="font-size:12.5px; color:var(--mavka-color-text-muted);">le <?= htmlspecialchars(date('d/m/Y', strtotime($q['date_verification']))) ?> par <?= htmlspecialchars($q['verifie_par']) ?></span>
          <?php endif; ?>
        </div>

        <?php if ($user['role'] === 'super_admin'): ?>
        <div style="margin-top:10px;">
          <label style="font-size:12.5px;">Note administrative <span style="font-weight:400; color:var(--mavka-color-text-muted);">(privée, réservée à toi)</span></label>
          <textarea data-qualification-note data-id="<?= $q['id'] ?>" rows="2" style="font-size:13.5px;"><?= htmlspecialchars($q['note_admin'] ?? '') ?></textarea>
          <button type="button" data-qualification-note-save data-id="<?= $q['id'] ?>" class="mavka-btn mavka-btn--sm" style="margin-top:6px;">Enregistrer la note</button>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$qualifications): ?>
      <p style="color:var(--mavka-color-text-muted); font-size:13.5px;">Aucune qualification enregistrée pour l'instant.</p>
      <?php endif; ?>
    </div>

    <label>Type de justificatif</label>
    <select name="qualification_type" form="qualification-form" required>
      <option value="">—</option>
      <?php foreach ($qualification_types as $val => $label): ?>
      <option value="<?= $val ?>"><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <label>Intitulé de la qualification</label>
    <input type="text" name="qualification_intitule" form="qualification-form" required>
    <div class="row">
      <div>
        <label>Organisme / établissement</label>
        <input type="text" name="qualification_organisme" form="qualification-form">
      </div>
      <div>
        <label>Pays</label>
        <input type="text" name="qualification_pays" form="qualification-form">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Année d'obtention</label>
        <input type="number" style="max-width:120px;" name="qualification_annee" form="qualification-form" min="1950" max="2100">
      </div>
      <div>
        <label>Document justificatif <span style="font-weight:400; color:var(--mavka-color-text-muted);">(facultatif)</span></label>
        <input type="file" name="qualification_fichier" form="qualification-form" accept="image/png,image/jpeg,image/webp,application/pdf">
      </div>
    </div>
    <button type="submit" form="qualification-form" class="mavka-btn mavka-btn--primary" style="margin-top:16px;">Ajouter</button>
    </div>
  </details>
  <?php endif; ?>

  </div>

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:8px;">Enregistrer</button>
  <a href="/admin/intervenants.php" class="mavka-btn" style="margin-top:8px;">Annuler</a>
</form>
<?php if ($id): ?>
<form method="post" id="atelier-form"><input type="hidden" name="action" value="add_atelier"></form>
<form method="post" id="galerie-form" enctype="multipart/form-data"><input type="hidden" name="action" value="add_galerie"></form>
<form method="post" id="qualification-form" enctype="multipart/form-data"><input type="hidden" name="action" value="add_qualification"></form>
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

  // Même aperçu immédiat pour le MAVKA-avatar et les avatars par direction.
  ['avatar_mavka', 'avatar_domaine_culture', 'avatar_domaine_education', 'avatar_domaine_bien_etre', 'avatar_domaine_initiatives'].forEach(function (champ) {
    var input = document.getElementById(champ + '_input');
    var preview = document.getElementById(champ + '_preview');
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.innerHTML = '';
        var img = document.createElement('img');
        img.src = e.target.result;
        img.alt = '';
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });

  // Qualifications : statut (dont "Vérifié", géré côté serveur pour rester réservé à
  // super_admin) et note administrative — en direct via admin/qualification-inline-update.php,
  // pas de <form> ici pour éviter d'en imbriquer un dans le formulaire principal de la page.
  document.querySelectorAll('[data-qualification-statut]').forEach(function (select) {
    select.addEventListener('change', function () {
      var val = select.value;
      if (val === 'verifie' && !confirm('Marquer cette qualification comme vérifiée ?')) {
        return;
      }
      fetch('/admin/qualification-inline-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: select.dataset.id, field: 'statut', value: val })
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (data.ok) { location.reload(); } else { alert(data.error || 'Erreur'); }
      }).catch(function () { alert('Impossible de contacter le serveur.'); });
    });
  });

  document.querySelectorAll('[data-qualification-note-save]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.dataset.id;
      var textarea = document.querySelector('[data-qualification-note][data-id="' + id + '"]');
      fetch('/admin/qualification-inline-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: id, field: 'note_admin', value: textarea.value })
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (data.ok) {
          var original = btn.textContent;
          btn.textContent = '✓ Enregistré';
          setTimeout(function () { btn.textContent = original; }, 1500);
        } else {
          alert(data.error || 'Erreur');
        }
      }).catch(function () { alert('Impossible de contacter le serveur.'); });
    });
  });
})();
</script>
<?php admin_footer(); ?>
