<?php
require_once __DIR__ . '/../includes/auth.php';

// N'importe quel compte connecté peut accepter — mais uniquement une de ses PROPRES
// activités (liée à son propre profil intervenant via activite_intervenant), jamais
// au nom d'un·e autre volontaire.
$user = auth_require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/mes-activites.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$stmt->execute([$user['id']]);
$intervenant_id = $stmt->fetchColumn();

if ($id && $intervenant_id) {
    $stmt = db()->prepare('SELECT 1 FROM activite_intervenant WHERE activite_id = ? AND intervenant_id = ?');
    $stmt->execute([$id, $intervenant_id]);
    if ($stmt->fetchColumn()) {
        db()->prepare('UPDATE activites SET accepte_intervenant = 1, accepte_le = NOW() WHERE id = ?')->execute([$id]);
    }
}

header('Location: /admin/mes-activites.php?accepte=1');
exit;
