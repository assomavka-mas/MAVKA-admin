<?php
require_once __DIR__ . '/db.php';

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
// $role: null (будь-хто залогинений) або 'admin' (тільки адмін).
function auth_require(?string $role = null): array {
    $user = auth_user();
    if (!$user || ($role !== null && $user['role'] !== $role)) {
        header('Location: /admin/login.php');
        exit;
    }
    return $user;
}
