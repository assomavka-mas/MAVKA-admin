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
require_once __DIR__ . '/includes/mailer.php';
$subject = '[Site MAVKA] Nouveau message' . ($sujet ? " — $sujet" : '');
$body = "Nom : $nom\nEmail : $email\nSujet : $sujet\n\n$message";

// SMTP (Gmail) si configuré dans config.php — bien plus fiable que mail() sur hébergement
// mutualisé. Si SMTP_* n'est pas défini, on retombe sur mail() (peut ne rien envoyer du tout
// selon l'hébergeur, mais le message reste de toute façon enregistré en base ci-dessus).
if (!mavka_smtp_envoyer(CONTACT_EMAIL, $subject, $body, $email)) {
    $headers = "From: MAVKA Site <no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">\r\nReply-To: $email";
    @mail(CONTACT_EMAIL, $subject, $body, $headers);
}

echo json_encode(['ok' => true]);
