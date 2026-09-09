<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require('super_admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$compte = ['nom' => '', 'email' => '', 'role' => 'mavka_admin', 'intervenant_id' => null];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found || $found['role'] === 'benevole') { http_response_code(404); exit('Accès introuvable.'); }
    $compte = $found;
}
// Le rôle super_admin ne se distribue pas depuis ce formulaire (seul mavka_admin/partenaire) —
// on le protège pour ne jamais l'écraser par erreur en éditant son propre compte.
$est_super_admin = $compte['role'] === 'super_admin';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compte['nom'] = trim($_POST['nom'] ?? '');
    $compte['email'] = strtolower(trim($_POST['email'] ?? ''));
    if (!$est_super_admin) {
        $compte['role'] = in_array($_POST['role'] ?? '', ['mavka_admin', 'partenaire']) ? $_POST['role'] : 'mavka_admin';
    }
    $compte['intervenant_id'] = $_POST['intervenant_id'] !== '' ? (int)$_POST['intervenant_id'] : null;

    if ($compte['email'] === '' || !filter_var($compte['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        if ($id) {
            $stmt = db()->prepare('UPDATE admins SET nom=?, email=?, role=?, intervenant_id=? WHERE id=?');
            $stmt->execute([$compte['nom'], $compte['email'], $compte['role'], $compte['intervenant_id'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO admins (nom, email, role, intervenant_id, password_hash) VALUES (?,?,?,?,NULL)');
            $stmt->execute([$compte['nom'], $compte['email'], $compte['role'], $compte['intervenant_id']]);
        }
        header('Location: /admin/acces.php?ok=1');
        exit;
    }
}

$intervenants = db()->query('SELECT id, nom FROM intervenants ORDER BY nom')->fetchAll();

admin_header($id ? "Modifier l'accès" : 'Nouvel accès', $user, 'acces');
?>
<h1><?= $id ? "Modifier l'accès" : 'Nouvel accès' ?></h1>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<form method="post" class="mavka-form mavka-card" style="max-width:500px;">
  <label>Nom</label>
  <input type="text" name="nom" placeholder="Ex. Ihor Petrenko (trésorier)" value="<?= htmlspecialchars($compte['nom'] ?? '') ?>">

  <label>Email Google de connexion</label>
  <input type="email" name="email" required value="<?= htmlspecialchars($compte['email']) ?>">

  <label>Rôle</label>
  <?php if ($est_super_admin): ?>
    <p style="margin:0; font-weight:600;">Super admin <span style="font-weight:400; color:var(--mavka-color-text-muted);">(non modifiable ici)</span></p>
  <?php else: ?>
    <select name="role">
      <option value="mavka_admin" <?= $compte['role'] === 'mavka_admin' ? 'selected' : '' ?>>Mavka-admin (gère le contenu)</option>
      <option value="partenaire" <?= $compte['role'] === 'partenaire' ? 'selected' : '' ?>>Partenaire (lecture seule)</option>
    </select>
  <?php endif; ?>

  <label>Lié à un profil intervenant·e (facultatif)</label>
  <p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:0 0 6px;">Si cette personne est aussi bénévole/intervenant·e (comme toi, par exemple), lie son compte à son profil : elle verra ses propres Activités en plus de son rôle actuel, sans avoir besoin d'un deuxième compte.</p>
  <select name="intervenant_id">
    <option value="">— Aucun —</option>
    <?php foreach ($intervenants as $iv): ?>
    <option value="<?= $iv['id'] ?>" <?= (int)($compte['intervenant_id'] ?? 0) === (int)$iv['id'] ? 'selected' : '' ?>><?= htmlspecialchars($iv['nom']) ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="mavka-btn mavka-btn--primary" style="margin-top:20px;">Enregistrer</button>
  <a href="/admin/acces.php" class="mavka-btn" style="margin-top:20px;">Annuler</a>
</form>
<?php admin_footer(); ?>
