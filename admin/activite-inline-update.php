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
$champs_texte = ['lieu', 'ville', 'heure', 'recurrence', 'texte_bouton'];
$champs_nombre = ['nombre_places', 'ordre'];
// '' est une valeur autorisée pour format/public (nullable) — convertie en NULL plus bas.
$champs_select = [
    'statut' => ['publie', 'brouillon'],
    'statut_activite' => ['ouvert', 'complet', 'annule', 'termine'],
    'categorie' => ['Culture', 'Éducation', 'Bien-être', 'Développement personnel', 'Événementiel'],
    'format' => ['', 'Collectif', 'Individuel'],
    'public' => ['', 'Enfant', 'Familial', 'Adultes'],
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
} elseif ($field === 'date_debut') {
    $value = trim((string)$value);
    if ($value === '') {
        $value = null;
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            fail('Date invalide.');
        }
    }
} elseif (isset($champs_select[$field])) {
    if (!in_array($value, $champs_select[$field], true)) {
        fail('Valeur non autorisée.');
    }
    if ($value === '' && in_array($field, ['format', 'public'], true)) {
        $value = null;
    }
} else {
    fail('Ce champ ne se modifie pas depuis le tableau.');
}

// Individuel = pas de nombre de places (voir aussi activite-form.php) : mise à jour d'un
// seul champ à la fois ici, donc on vérifie l'autre côté à chaque fois.
if ($field === 'nombre_places' && $value !== null) {
    $stmt = db()->prepare('SELECT format FROM activites WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() === 'Individuel') {
        fail('Type d\'activité « Individuel » : pas de nombre de places.');
    }
}

$stmt = db()->prepare("UPDATE activites SET `$field` = ? WHERE id = ?");
$stmt->execute([$value, $id]);

if ($field === 'format' && $value === 'Individuel') {
    db()->prepare('UPDATE activites SET nombre_places = NULL WHERE id = ?')->execute([$id]);
}

echo json_encode(['ok' => true, 'value' => $value]);
