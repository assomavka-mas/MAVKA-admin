<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

function deny(string $reason): void {
    http_response_code(403);
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Accès refusé</title></head><body style="font-family:sans-serif; max-width:420px; margin:60px auto;">';
    echo '<h2>Accès refusé</h2><p>' . htmlspecialchars($reason) . '</p>';
    echo '<p><a href="/admin/login.php">Retour à la connexion</a></p></body></html>';
    exit;
}

// Protection CSRF : Google envoie un cookie et un champ de formulaire qui doivent correspondre.
$csrf_cookie = $_COOKIE['g_csrf_token'] ?? '';
$csrf_body = $_POST['g_csrf_token'] ?? '';
if (!$csrf_cookie || !$csrf_body || !hash_equals($csrf_cookie, $csrf_body)) {
    deny('Échec de vérification de sécurité (CSRF). Réessaie de te connecter.');
}

$credential = $_POST['credential'] ?? '';
if (!$credential) {
    deny('Aucun jeton reçu de Google.');
}

// Vérification du jeton auprès de Google (suffisant pour un trafic de connexion, pas besoin de librairie JWT).
$ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    deny('Impossible de vérifier le jeton Google.');
}

$payload = json_decode($response, true);

if (($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    deny('Jeton Google invalide pour ce site.');
}
if (($payload['email_verified'] ?? 'false') !== 'true') {
    deny('Cet email Google n\'est pas vérifié.');
}

$email = strtolower(trim($payload['email'] ?? ''));

if (!auth_login_google($email)) {
    header('Location: /admin/login.php?denied=1');
    exit;
}

header('Location: /admin/dashboard.php');
exit;
