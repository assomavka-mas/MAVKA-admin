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

if ($id <= 0) {
    fail('Qualification introuvable.');
}

// "Vérifié" (+ date_verification/verifie_par, auto-remplis) et la note administrative sont
// réservés à super_admin — voir admin/intervenant-form.php pour le contexte complet.
if ($field === 'statut') {
    $statuts_valides = ['a_verifier', 'a_completer'];
    if ($user['role'] === 'super_admin') {
        $statuts_valides[] = 'verifie';
    }
    if (!in_array($value, $statuts_valides, true)) {
        fail('Valeur non autorisée.');
    }
    if ($value === 'verifie') {
        db()->prepare('UPDATE intervenant_qualifications SET statut = ?, date_verification = CURDATE(), verifie_par = ? WHERE id = ?')
            ->execute([$value, $user['email'], $id]);
    } else {
        db()->prepare('UPDATE intervenant_qualifications SET statut = ? WHERE id = ?')->execute([$value, $id]);
    }
} elseif ($field === 'note_admin') {
    if ($user['role'] !== 'super_admin') {
        fail('Réservé à super_admin.');
    }
    $value = trim((string)$value);
    db()->prepare('UPDATE intervenant_qualifications SET note_admin = ? WHERE id = ?')->execute([$value !== '' ? $value : null, $id]);
} else {
    fail('Ce champ ne se modifie pas depuis ici.');
}

echo json_encode(['ok' => true, 'value' => $value]);
