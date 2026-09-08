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
