<?php
// Modèles vierges (Google Docs) à imprimer, signer, puis rapporter à Larysa — distincts de la
// version signée elle-même (charte_benevolat_lien/fichier, contrat_intervention_lien/fichier
// sur `intervenants`), qui est propre à chaque personne. Ceux-ci sont communs à tout le monde.
const MAVKA_MODELE_CHARTE_BENEVOLAT_URL = 'https://docs.google.com/document/d/1LyGOTT19kAvVHCHUgAY6ut4QiwOpwdJTKR5rTZaH0zE/edit?usp=sharing';
const MAVKA_MODELE_CONTRAT_INTERVENTION_URL = 'https://docs.google.com/document/d/1u0cvCNCnOfa3CI_OWb2FXYORGVuDYeFq2Gy52laZiwY/edit?usp=sharing';

// Une activité liée à un·e ou plusieurs intervenant·e·s n'est prête pour le site public que si
// elle a un lien d'inscription ET que CHACUNE des personnes liées l'a acceptée — pas juste
// une seule (voir alter-champs-v14.sql et la vue activites_publiques : acceptation par
// personne, sur activite_intervenant, jamais par exception). $a doit contenir nb_intervenants
// et nb_acceptes, calculés par la requête SQL (COUNT sur activite_intervenant).
function activite_incomplete(array $a): bool {
    $manque_lien = trim((string)($a['lien_inscription'] ?? '')) === '';
    $attente_acceptation = (int)($a['nb_intervenants'] ?? 0) > (int)($a['nb_acceptes'] ?? 0);
    return $manque_lien || $attente_acceptation;
}

// Statut calculé automatiquement à partir des documents signés (jamais saisi à la main).
// "Découverte" (pas "Volontaire") pour le palier sans document signé : en France, "volontaire"
// désigne un statut encadré par la loi (volontariat associatif, service civique...), avec
// contrat, indemnité et agrément d'État — rien de tout ça à ce stade, juste un profil pas
// encore engagé. Voir aussi site_intervenant_statut_label() (includes/site_functions.php),
// même terminologie côté page publique.
function intervenant_statuts(array $iv): array {
    $statuts = [];
    if (!empty($iv['charte_benevolat_lien']) || !empty($iv['charte_benevolat_fichier'])) {
        $statuts[] = 'Bénévole';
    }
    if (!empty($iv['contrat_intervention_lien']) || !empty($iv['contrat_intervention_fichier'])) {
        $statuts[] = 'Intervenant';
    }
    return $statuts ?: ['Découverte'];
}

// Transforme un nom en morceau de chemin de dossier lisible : "Olena Kovalenko" -> "olena-kovalenko"
function slugify(string $text): string {
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    if ($translit !== false) {
        $text = $translit;
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'sans-nom';
}

// Dossier unique et lisible pour un intervenant : "12-olena-kovalenko" (l'id évite les collisions
// entre plusieurs personnes portant le même prénom).
function intervenant_dossier(int $id, string $nom): string {
    return $id . '-' . slugify($nom);
}

// Page publique du·de la volontaire sur le nouveau site (remplace l'ancienne page WordPress
// "Notre équipe" : https://mavka16.fr/notre-equipe/hanna-sokha/).
function intervenant_page_url(int $id): string {
    return '/intervenant.php?id=' . $id;
}

// Traite un champ <input type="file"> et renvoie le nom du fichier enregistré,
// ou null si aucun fichier n'a été envoyé (auquel cas on garde l'ancien fichier).
function handle_upload(string $field, string $subdir): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $mime = mime_content_type($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$mime])) {
        return null;
    }

    $dir = __DIR__ . '/../assets/uploads/' . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename);
    return $filename;
}

// Retire automatiquement le fond blanc d'un PNG (avatars MAVKA/direction) : remplit à partir
// des 4 bords de l'image et rend transparent tout pixel quasi-blanc connecté au bord — les
// blancs "intérieurs" (yeux, vêtements...) ne sont donc jamais touchés. Fonctionne bien sur les
// illustrations Canva à fond uni ; pas adapté à une photo ou un fond en dégradé/texturé.
// Renvoie un code (au lieu de void) pour pouvoir diagnostiquer un échec silencieux en prod —
// voir admin/nettoyer-avatars.php, qui affiche ce code pour chaque fichier retraité.
function retirer_fond_blanc(string $chemin): string {
    if (!extension_loaded('gd')) {
        return 'gd-absent';
    }
    if (!is_file($chemin)) {
        return 'fichier-introuvable';
    }
    if (!is_writable($chemin)) {
        return 'fichier-non-modifiable';
    }
    $info = @getimagesize($chemin);
    if (!$info) {
        return 'image-illisible';
    }
    if ($info['mime'] !== 'image/png') {
        return 'pas-un-png (' . $info['mime'] . ')';
    }
    $image = @imagecreatefrompng($chemin);
    if (!$image) {
        return 'echec-decodage-png';
    }

    $max = 1200;
    if (imagesx($image) > $max || imagesy($image) > $max) {
        $ratio = min($max / imagesx($image), $max / imagesy($image));
        $largeurRedim = (int)round(imagesx($image) * $ratio);
        $hauteurRedim = (int)round(imagesy($image) * $ratio);
        $redim = imagecreatetruecolor($largeurRedim, $hauteurRedim);
        imagealphablending($redim, false);
        imagesavealpha($redim, true);
        imagecopyresampled($redim, $image, 0, 0, 0, 0, $largeurRedim, $hauteurRedim, imagesx($image), imagesy($image));
        imagedestroy($image);
        $image = $redim;
    }

    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);

    $largeur = imagesx($image);
    $hauteur = imagesy($image);
    $seuil = 18;
    $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);

    $visite = new SplFixedArray($largeur * $hauteur);
    $pile = [];
    for ($x = 0; $x < $largeur; $x++) {
        $pile[] = [$x, 0];
        $pile[] = [$x, $hauteur - 1];
    }
    for ($y = 0; $y < $hauteur; $y++) {
        $pile[] = [0, $y];
        $pile[] = [$largeur - 1, $y];
    }

    while ($pile) {
        [$x, $y] = array_pop($pile);
        if ($x < 0 || $x >= $largeur || $y < 0 || $y >= $hauteur) {
            continue;
        }
        $idx = $y * $largeur + $x;
        if ($visite[$idx]) {
            continue;
        }
        $visite[$idx] = true;

        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r < 255 - $seuil || $g < 255 - $seuil || $b < 255 - $seuil) {
            continue;
        }

        imagesetpixel($image, $x, $y, $transparent);
        $pile[] = [$x + 1, $y];
        $pile[] = [$x - 1, $y];
        $pile[] = [$x, $y + 1];
        $pile[] = [$x, $y - 1];
    }

    $ok = imagepng($image, $chemin);
    imagedestroy($image);
    return $ok ? 'ok' : 'echec-ecriture';
}

// Comme handle_upload(), mais pour un <input type="file" name="..[]" multiple> : plusieurs
// photos ajoutées à la galerie en un seul envoi. Renvoie la liste des noms de fichiers
// enregistrés (les fichiers invalides ou en erreur sont simplement ignorés).
function handle_multi_upload(string $field, string $subdir): array {
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) {
        return [];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dir = __DIR__ . '/../assets/uploads/' . $subdir . '/';
    $saved = [];

    foreach ($files['name'] as $i => $name) {
        if ($name === '' || $files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        $mime = mime_content_type($files['tmp_name'][$i]);
        if (!isset($allowed[$mime])) {
            continue;
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        move_uploaded_file($files['tmp_name'][$i], $dir . $filename);
        $saved[] = $filename;
    }

    return $saved;
}

// Bloc "lien + fichier" pour un document (Charte, CV, RIB...) tenu sur une seule ligne compacte :
// puce verte "Lien" (avec crayon pour l'éditer) + aperçu/badge de fichier (avec crayon pour le remplacer).
// $historique : lignes de intervenant_document_versions pour ce champ (les plus récentes d'abord),
// pour retrouver un ancien fichier après remplacement — rien n'est perdu, juste plus "actuel".
function champ_document(string $label, string $lien_field, string $fichier_field, array $iv, ?string $file_url, ?string $dossier = null, array $historique = [], string $accept = 'image/png,image/jpeg,image/webp,application/pdf'): void {
    $lien_id = 'lien_' . $lien_field;
    $fichier_id = 'fichier_' . $fichier_field;
    $ext = $iv[$fichier_field] ? strtolower(pathinfo($iv[$fichier_field], PATHINFO_EXTENSION)) : null;
    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    // Les anciennes versions = tout l'historique sauf le fichier actuellement actif
    $anciennes = array_filter($historique, fn($v) => $v['fichier'] !== $iv[$fichier_field]);
    $rempli = !empty($iv[$lien_field]) || !empty($iv[$fichier_field]);
    ?>
    <label><?= htmlspecialchars($label) ?><?= $rempli ? ' <span style="color:var(--mavka-color-teal); font-weight:700;" title="Rempli">✓</span>' : '' ?></label>
    <div class="mavka-doc-row">
      <?php if (!empty($iv[$lien_field])): ?>
        <div class="mavka-link-chip">
          <span class="mavka-link-chip__check">✓</span>
          <a href="<?= htmlspecialchars($iv[$lien_field]) ?>" target="_blank" class="mavka-link-chip__label">Lien</a>
          <button type="button" class="mavka-link-chip__edit" data-toggle="<?= $lien_id ?>" title="Modifier le lien">✎</button>
        </div>
        <input type="url" id="<?= $lien_id ?>" class="mavka-doc-row__lien" name="<?= htmlspecialchars($lien_field) ?>" value="<?= htmlspecialchars($iv[$lien_field]) ?>" hidden>
      <?php else: ?>
        <input type="url" id="<?= $lien_id ?>" class="mavka-doc-row__lien" name="<?= htmlspecialchars($lien_field) ?>" placeholder="Lien Google Drive">
      <?php endif; ?>

      <?php if ($file_url): ?>
        <a href="<?= htmlspecialchars($file_url) ?>" target="_blank" class="mavka-file-slot__preview">
          <?php if ($is_image): ?>
            <img src="<?= htmlspecialchars($file_url) ?>" alt="">
          <?php else: ?>
            <span class="mavka-file-slot__badge"><?= htmlspecialchars(strtoupper($ext ?: '?')) ?></span>
          <?php endif; ?>
          <span class="mavka-file-slot__label">Voir le fichier</span>
        </a>
        <label class="mavka-file-slot__replace" for="<?= $fichier_id ?>" title="Remplacer le fichier">✎</label>
      <?php else: ?>
        <label class="mavka-file-slot__empty" for="<?= $fichier_id ?>">+ Ajouter un fichier</label>
      <?php endif; ?>
      <input type="file" id="<?= $fichier_id ?>" name="<?= htmlspecialchars($fichier_field) ?>" accept="<?= htmlspecialchars($accept) ?>" hidden>
    </div>
    <?php if ($anciennes && $dossier): ?>
    <details class="mavka-doc-historique">
      <summary>Historique (<?= count($anciennes) ?>)</summary>
      <ul>
        <?php foreach ($anciennes as $v): ?>
        <li>
          <a href="/assets/uploads/intervenants/<?= htmlspecialchars($dossier) ?>/<?= htmlspecialchars($v['fichier']) ?>" target="_blank">Voir</a>
          — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($v['created_at']))) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>
    <?php
}

// Variante de champ_document() sans lien Google Drive — juste un fichier (Pièce d'identité,
// Avis SIRENE...) : ces documents n'ont pas d'équivalent "lien à partager", contrairement à la
// Charte ou au Contrat qui existent d'abord comme modèle Google Docs.
function champ_fichier_seul(string $label, string $fichier_field, array $iv, ?string $file_url, array $historique = [], ?string $dossier = null, string $accept = 'image/png,image/jpeg,image/webp,application/pdf'): void {
    $fichier_id = 'fichier_' . $fichier_field;
    $ext = $iv[$fichier_field] ? strtolower(pathinfo($iv[$fichier_field], PATHINFO_EXTENSION)) : null;
    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    $anciennes = array_filter($historique, fn($v) => $v['fichier'] !== $iv[$fichier_field]);
    ?>
    <label><?= htmlspecialchars($label) ?><?= !empty($iv[$fichier_field]) ? ' <span style="color:var(--mavka-color-teal); font-weight:700;" title="Rempli">✓</span>' : '' ?></label>
    <div class="mavka-doc-row">
      <?php if ($file_url): ?>
        <a href="<?= htmlspecialchars($file_url) ?>" target="_blank" class="mavka-file-slot__preview">
          <?php if ($is_image): ?>
            <img src="<?= htmlspecialchars($file_url) ?>" alt="">
          <?php else: ?>
            <span class="mavka-file-slot__badge"><?= htmlspecialchars(strtoupper($ext ?: '?')) ?></span>
          <?php endif; ?>
          <span class="mavka-file-slot__label">Voir le fichier</span>
        </a>
        <label class="mavka-file-slot__replace" for="<?= $fichier_id ?>" title="Remplacer le fichier">✎</label>
      <?php else: ?>
        <label class="mavka-file-slot__empty" for="<?= $fichier_id ?>">+ Ajouter un fichier</label>
      <?php endif; ?>
      <input type="file" id="<?= $fichier_id ?>" name="<?= htmlspecialchars($fichier_field) ?>" accept="<?= htmlspecialchars($accept) ?>" hidden>
    </div>
    <?php if ($anciennes && $dossier): ?>
    <details class="mavka-doc-historique">
      <summary>Historique (<?= count($anciennes) ?>)</summary>
      <ul>
        <?php foreach ($anciennes as $v): ?>
        <li>
          <a href="/assets/uploads/intervenants/<?= htmlspecialchars($dossier) ?>/<?= htmlspecialchars($v['fichier']) ?>" target="_blank">Voir</a>
          — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($v['created_at']))) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>
    <?php
}

// Politique de confidentialité (RGPD, section "Durées de conservation") : les messages du
// formulaire de contact sont conservés 2 ans max après le dernier échange, puis supprimés.
// Pas de vrai cron sur l'hébergement pour l'instant, donc on s'en charge ici : appelée à chaque
// connexion admin (voir auth_require() dans auth.php), avec 1 chance sur 20 de vraiment lancer
// la suppression — pour ne pas faire une requête d'écriture à chaque page vue, tout en restant
// sûr de tourner régulièrement puisque l'admin se connecte souvent. Un simple DELETE, sans
// risque à relancer plusieurs fois (contrairement aux alertes email de alertes-documents.php).
function purger_vieux_messages_contact(): void {
    if (random_int(1, 20) !== 1) {
        return;
    }
    db()->exec("DELETE FROM messages_contact WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)");
}

// Activité des bénévoles (v26) : connexions + actions qu'un·e bénévole fait lui/elle-même
// (profil, photos d'activité), pour que Larysa puisse voir qui s'engage sans avoir à demander —
// jamais utilisé pour les actions d'un super_admin/mavka_admin (voir dashboard.php).
function journal_logger(int $admin_id, string $action, ?string $detail = null): void {
    $stmt = db()->prepare('INSERT INTO journal_activite (admin_id, action, detail) VALUES (?, ?, ?)');
    $stmt->execute([$admin_id, $action, $detail]);
}
