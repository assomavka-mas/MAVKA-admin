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

// Met à jour SA PROPRE ligne dans activite_intervenant — l'acceptation d'une autre personne
// liée à la même activité (s'il y en a) n'est ni touchée ni remplacée par celle-ci.
if ($id && $intervenant_id) {
    db()->prepare('UPDATE activite_intervenant SET accepte = 1, accepte_le = NOW() WHERE activite_id = ? AND intervenant_id = ?')
        ->execute([$id, $intervenant_id]);
}

header('Location: /admin/mes-activites.php?accepte=1');
exit;
