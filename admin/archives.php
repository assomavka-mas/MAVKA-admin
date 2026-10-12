<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Archive interne des documents officiels signés (Règlement intérieur, PV de réunion,
// politiques internes...) — jamais publique, contrairement à assets/docs/*.pdf. Une seule liste
// simple : garder une trace des versions successives (date + notes) plutôt que remplacer un
// fichier public sans historique. Réservé à super_admin/mavka_admin, comme Contacts.
$user = auth_require(['super_admin', 'mavka_admin']);

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM archives_documents WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/archives.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $titre = trim($_POST['titre'] ?? '');
    $date_document = ($_POST['date_document'] ?? '') !== '' ? $_POST['date_document'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $fichier = handle_upload('fichier', 'archives');
    if ($titre === '') {
        $error = 'Le titre est obligatoire.';
    } elseif (!$fichier) {
        $error = 'Le fichier est obligatoire (PDF, image).';
    } else {
        db()->prepare('INSERT INTO archives_documents (titre, date_document, fichier, notes) VALUES (?, ?, ?, ?)')
            ->execute([$titre, $date_document, $fichier, $notes]);
        header('Location: /admin/archives.php?ok=1');
        exit;
    }
}

$tri = $_GET['sort'] ?? 'date_document';
$sens = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$colonnes_triables = ['titre', 'date_document', 'created_at'];
$col = in_array($tri, $colonnes_triables, true) ? $tri : 'date_document';
$documents = db()->query("SELECT * FROM archives_documents ORDER BY `$col` $sens, created_at DESC")->fetchAll();

function archives_tri_lien(string $col, string $libelle, string $triActuel, string $sensActuel): string {
    $prochainSens = ($triActuel === $col && $sensActuel === 'asc') ? 'desc' : 'asc';
    $fleche = $triActuel === $col ? ($sensActuel === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="?sort=' . urlencode($col) . '&dir=' . $prochainSens . '">' . htmlspecialchars($libelle) . $fleche . '</a>';
}

admin_header('Archive', $user, 'archives');
?>
<h1>Archive</h1>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if (!empty($error)): ?><p style="color:var(--mavka-color-danger-text);"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 16px;">Documents officiels signés de l'association (règlements, PV de réunion, politiques internes...) — jamais publics, à la différence des documents PDF de la page Collectivités. Une ligne par version : la date sert à retrouver la bonne au fil du temps.</p>

<details style="margin-bottom:20px;">
  <summary class="mavka-btn mavka-btn--primary">+ Ajouter un document</summary>
  <form method="post" enctype="multipart/form-data" class="mavka-form" style="margin-top:12px; max-width:520px;">
    <input type="hidden" name="action" value="add">
    <label>Titre <input type="text" name="titre" required placeholder="Ex. Règlement intérieur, PV réunion fondateurs..."></label>
    <label>Date du document <input type="date" name="date_document"></label>
    <label>Fichier <input type="file" name="fichier" accept="application/pdf,image/png,image/jpeg,image/webp" required></label>
    <label>Notes <textarea name="notes" placeholder="Ex. Version 2, signée par les 2 fondateurs."></textarea></label>
    <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
  </form>
</details>

<table class="mavka-table">
  <tr>
    <th><?= archives_tri_lien('titre', 'Titre', $col, $sens) ?></th>
    <th><?= archives_tri_lien('date_document', 'Date du document', $col, $sens) ?></th>
    <th>Notes</th>
    <th></th>
  </tr>
  <?php foreach ($documents as $d): ?>
  <tr>
    <td><a href="/assets/uploads/archives/<?= rawurlencode($d['fichier']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($d['titre']) ?></a></td>
    <td><?= $d['date_document'] ? htmlspecialchars(date('d/m/Y', strtotime($d['date_document']))) : '—' ?></td>
    <td><?= $d['notes'] ? nl2br(htmlspecialchars($d['notes'])) : '—' ?></td>
    <td style="white-space:nowrap;">
      <a href="/admin/archives.php?delete=<?= $d['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer ce document de l\'archive ?');">Supprimer</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$documents): ?>
  <tr><td colspan="4" style="color:var(--mavka-color-text-muted);">Aucun document pour l'instant.</td></tr>
  <?php endif; ?>
</table>
<?php admin_footer(); ?>
