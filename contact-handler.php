<?php
// Обробник форми контактів. Форма на сайті має відправляти POST сюди.
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function fail(string $msg): void {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Méthode non autorisée.');
}

// Honeypot anti-spam : champ caché "site_web" que seuls les robots remplissent
if (!empty($_POST['site_web'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$sujet = trim($_POST['sujet'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($nom === '' || $message === '') {
    fail('Nom et message sont obligatoires.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Adresse email invalide.');
}

$stmt = db()->prepare('INSERT INTO messages_contact (nom, email, sujet, message) VALUES (?,?,?,?)');
$stmt->execute([$nom, $email, $sujet, $message]);

require_once __DIR__ . '/config.php';
$subject = '[Site MAVKA] Nouveau message' . ($sujet ? " — $sujet" : '');
$body = "Nom : $nom\nEmail : $email\nSujet : $sujet\n\n$message";
$headers = "From: MAVKA Site <no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">\r\nReply-To: $email";
@mail(CONTACT_EMAIL, $subject, $body, $headers);

echo json_encode(['ok' => true]);
