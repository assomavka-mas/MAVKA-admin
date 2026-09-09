<?php
// Statut calculé automatiquement à partir des documents signés (jamais saisi à la main).
function intervenant_statuts(array $iv): array {
    $statuts = [];
    if (!empty($iv['charte_benevolat_lien']) || !empty($iv['charte_benevolat_fichier'])) {
        $statuts[] = 'Bénévole';
    }
    if (!empty($iv['contrat_intervention_lien']) || !empty($iv['contrat_intervention_fichier'])) {
        $statuts[] = 'Intervenant';
    }
    return $statuts ?: ['Volontaire'];
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
    ?>
    <label><?= htmlspecialchars($label) ?></label>
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
