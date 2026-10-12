<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Connecte l'utilisateur si son email Google (déjà vérifié par Google) existe dans `admins`.
function auth_login_google(string $email): bool {
    $stmt = db()->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    auth_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];

    db()->prepare('UPDATE admins SET derniere_connexion = NOW() WHERE id = ?')->execute([$user['id']]);
    if ($user['role'] === 'benevole') {
        journal_logger((int)$user['id'], 'connexion');
    }
    return true;
}

function auth_logout(): void {
    auth_start();
    $_SESSION = [];
    session_destroy();
}

function auth_user(): ?array {
    auth_start();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'],
    ];
}

// Викликати на початку сторінки, яка вимагає входу.
// $roles: null (будь-хто залогинений), 'super_admin' (одна роль), або ['super_admin','mavka_admin'] (кілька).
function auth_require(null|string|array $roles = null): array {
    $user = auth_user();
    $allowed = $roles === null ? null : (is_array($roles) ? $roles : [$roles]);
    if (!$user || ($allowed !== null && !in_array($user['role'], $allowed, true))) {
        header('Location: /admin/login.php');
        exit;
    }
    purger_vieux_messages_contact();
    return $user;
}

// Peut créer/modifier/supprimer du contenu (Activités, Intervenants, Messages).
function peut_editer(array $user): bool {
    return in_array($user['role'], ['super_admin', 'mavka_admin'], true);
}
