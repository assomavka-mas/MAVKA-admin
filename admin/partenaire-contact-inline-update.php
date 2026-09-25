<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Édition directe dans le tableau des contacts (comme intervenant-inline-update.php pour les
// bénévoles) — un champ à la fois, sans passer par le formulaire "Modifier" complet.
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

$champs_texte = ['nom', 'fonction', 'email', 'telephone', 'langue', 'notes'];
$champs_enum = [
    'genre' => ['M', 'Mme', 'non_precise'],
    'niveau_influence' => ['decideur_final', 'decideur_delegue', 'consultatif', 'administratif', 'inconnu'],
];
if (!in_array($field, $champs_texte, true) && !isset($champs_enum[$field])) {
    fail('Ce champ ne se modifie pas depuis le tableau.');
}
if ($id <= 0) {
    fail('Contact introuvable.');
}

if (isset($champs_enum[$field])) {
    if (!in_array($value, $champs_enum[$field], true)) {
        fail('Valeur invalide.');
    }
} else {
    $value = trim((string)$value);
    if ($field === 'nom' && $value === '') {
        fail('Le nom est obligatoire.');
    }
    if ($field === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        fail('Adresse email invalide.');
    }
}

$stmt = db()->prepare("UPDATE partenaires_contacts SET `$field` = ? WHERE id = ?");
$stmt->execute([$value, $id]);

echo json_encode(['ok' => true, 'value' => $value]);
