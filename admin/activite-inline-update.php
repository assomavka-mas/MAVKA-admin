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

// Champs texte/nombre libres, modifiables directement dans le tableau.
$champs_texte = ['lieu', 'ville', 'heure'];
$champs_nombre = ['nombre_places', 'ordre'];
$champs_select = [
    'statut' => ['publie', 'brouillon'],
    'statut_activite' => ['ouvert', 'complet', 'annule', 'termine'],
];

if ($id <= 0) {
    fail('Activité introuvable.');
}

if (in_array($field, $champs_texte, true)) {
    $value = trim((string)$value);
} elseif (in_array($field, $champs_nombre, true)) {
    $value = trim((string)$value);
    if ($field === 'nombre_places' && $value === '') {
        $value = null;
    } elseif (!ctype_digit($value)) {
        fail('Ce champ attend un nombre entier.');
    } else {
        $value = (int)$value;
    }
} elseif (isset($champs_select[$field])) {
    if (!in_array($value, $champs_select[$field], true)) {
        fail('Valeur non autorisée.');
    }
} else {
    fail('Ce champ ne se modifie pas depuis le tableau.');
}

$stmt = db()->prepare("UPDATE activites SET `$field` = ? WHERE id = ?");
$stmt->execute([$value, $id]);

echo json_encode(['ok' => true, 'value' => $value]);
