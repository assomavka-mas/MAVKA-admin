<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = auth_require(['super_admin', 'mavka_admin']);

header('Content-Type: application/json; charset=utf-8');

function fail(string $msg): void {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Méthode non autorisée.');
}

$id = (int)($_POST['id'] ?? 0);
$field = (string)($_POST['field'] ?? '');
$value = $_POST['value'] ?? '';

// Liste blanche : seuls les champs simples, sans contrainte de format, sont modifiables ici.
// Domaine (picklist) et les documents restent réservés à la fiche complète.
$champs_editables = ['nom', 'resume', 'adresse', 'email', 'actif'];
if (!in_array($field, $champs_editables, true)) {
    fail('Ce champ ne se modifie pas depuis le tableau.');
}
if ($id <= 0) {
    fail('Intervenant introuvable.');
}

if ($field === 'actif') {
    $value = $value === '1' ? 1 : 0;
} else {
    $value = trim((string)$value);
    if ($field === 'nom' && $value === '') {
        fail('Le nom est obligatoire.');
    }
    if ($field === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        fail('Adresse email invalide.');
    }
}

$stmt = db()->prepare("UPDATE intervenants SET `$field` = ? WHERE id = ?");
$stmt->execute([$value, $id]);

echo json_encode(['ok' => true, 'value' => $value]);
