<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Fiche d'un partenaire : infos de l'organisation + ses contacts, ses rencontres
// (négociations) et les projets/accords qui en découlent. Pas d'édition en place pour les
// sous-listes (contacts/rencontres/projets) dans cette v1 — ajouter et supprimer suffit pour
// démarrer ; une vraie édition pourra venir plus tard si le besoin se confirme.
$user = auth_require(['super_admin', 'mavka_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$types_labels = [
    'mairie' => 'Mairie', 'centre_social' => 'Centre social', 'fondation' => 'Fondation',
    'association' => 'Association', 'entreprise' => 'Entreprise', 'autre' => 'Autre',
];
$statuts_labels = [
    'potentiel' => 'Potentiel', 'actif' => 'Actif', 'inactif' => 'Inactif', 'en_pause' => 'En pause',
];
$etapes_labels = [
    'premiere_rencontre' => 'Première rencontre',
    'co_construction' => 'Co-construction',
    'phase_pilote' => 'Phase pilote',
    'mise_en_place' => 'Mise en place progressive',
    'faire_evoluer' => 'Faire évoluer le partenariat',
    'bilan' => 'Faire le bilan et décider de la suite',
];
$types_rencontre_labels = ['rencontre' => 'Rencontre', 'appel' => 'Appel', 'email' => 'Email', 'courrier' => 'Courrier'];
$statuts_projet_labels = ['en_cours' => 'En cours', 'termine' => 'Terminé', 'abandonne' => 'Abandonné'];
// Échelle volontairement indépendante des titres français précis (maire, adjoint délégué,
// conseiller communautaire...) — utilisable sans connaître toutes leurs nuances. Voir
// alter-champs-v28.sql.
$influence_labels = [
    'decideur_final' => 'Décision finale (maire, président·e...)',
    'decideur_delegue' => 'Décision déléguée (adjoint·e, conseiller·ère délégué·e, vice-président·e...)',
    'consultatif' => 'Influence / avis consultatif (conseiller·ère, membre de commission...)',
    'administratif' => 'Suivi administratif (secrétaire de mairie, DGS...)',
    'inconnu' => 'Inconnu pour l\'instant',
];
// Suggestions pour le champ "Fonction" (autocomplétion libre, pas une liste fermée) — pense-bête
// des titres français les plus courants, pour ne pas avoir à les connaître par cœur.
$fonctions_suggestions = [
    'Maire', '1er adjoint', '1ère adjointe', '2e adjoint', '2e adjointe', '3e adjoint', '3e adjointe', '4e adjoint', '4e adjointe',
    'Conseiller municipal délégué', 'Conseillère municipale déléguée', 'Conseiller municipal', 'Conseillère municipale',
    'Membre de commission', 'Vice-président', 'Vice-présidente', 'Conseiller communautaire', 'Conseillère communautaire',
    'Secrétaire de mairie', 'Directeur général des services (DGS)', 'Directrice générale des services (DGS)',
    'Chargé de mission vie associative', 'Chargée de mission vie associative', 'Directeur', 'Directrice', 'Président', 'Présidente',
];

$org = [
    'nom' => '', 'type' => 'autre', 'ville' => '', 'adresse' => '', 'site_web' => '',
    'email_general' => '', 'telephone' => '', 'statut' => 'potentiel', 'notes' => '', 'dossier_drive_lien' => '',
];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM partenaires_organisations WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Partenaire introuvable.'); }
    $org = $found;
}

// ---- Enregistrement de l'organisation ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_organisation') {
    $fields = ['nom', 'type', 'ville', 'adresse', 'site_web', 'email_general', 'telephone', 'statut', 'notes', 'dossier_drive_lien'];
    foreach ($fields as $f) {
        $org[$f] = trim($_POST[$f] ?? '');
    }
    if ($org['nom'] === '') {
        $error = 'Le nom est obligatoire.';
    } else {
        if ($id) {
            $set = implode(', ', array_map(fn($f) => "$f = ?", $fields));
            db()->prepare("UPDATE partenaires_organisations SET $set WHERE id = ?")
                ->execute([...array_map(fn($f) => $org[$f], $fields), $id]);
        } else {
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            db()->prepare('INSERT INTO partenaires_organisations (' . implode(', ', $fields) . ") VALUES ($placeholders)")
                ->execute(array_map(fn($f) => $org[$f], $fields));
            $id = (int)db()->lastInsertId();
        }
        header('Location: /admin/partenaire-form.php?id=' . $id . '&ok=1');
        exit;
    }
}

// ---- Sous-listes : uniquement possibles une fois l'organisation créée ----
if ($id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_contact') {
        $niveau = in_array($_POST['niveau_influence'] ?? '', array_keys($influence_labels), true) ? $_POST['niveau_influence'] : 'inconnu';
        db()->prepare('INSERT INTO partenaires_contacts (organisation_id, nom, fonction, email, email_secondaire, telephone, langue, niveau_influence, notes) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $id, trim($_POST['nom'] ?? ''), trim($_POST['fonction'] ?? ''), trim($_POST['email'] ?? ''),
                trim($_POST['email_secondaire'] ?? ''), trim($_POST['telephone'] ?? ''), trim($_POST['langue'] ?? ''),
                $niveau, trim($_POST['notes'] ?? ''),
            ]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#contacts');
        exit;
    }
    if (isset($_GET['delete_contact'])) {
        db()->prepare('DELETE FROM partenaires_contacts WHERE id = ? AND organisation_id = ?')->execute([(int)$_GET['delete_contact'], $id]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#contacts');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_rencontre') {
        db()->prepare('INSERT INTO partenaires_rencontres (organisation_id, contact_id, type, date_rencontre, sujet, compte_rendu, etape_parcours, prochaine_action, date_prochaine_action, responsable) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $id, ($_POST['contact_id'] ?? '') !== '' ? (int)$_POST['contact_id'] : null,
                $_POST['type'] ?? 'rencontre', $_POST['date_rencontre'] ?? date('Y-m-d'),
                trim($_POST['sujet'] ?? ''), trim($_POST['compte_rendu'] ?? ''),
                ($_POST['etape_parcours'] ?? '') !== '' ? $_POST['etape_parcours'] : null,
                trim($_POST['prochaine_action'] ?? ''), ($_POST['date_prochaine_action'] ?? '') !== '' ? $_POST['date_prochaine_action'] : null,
                trim($_POST['responsable'] ?? ''),
            ]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#rencontres');
        exit;
    }
    if (isset($_GET['delete_rencontre'])) {
        db()->prepare('DELETE FROM partenaires_rencontres WHERE id = ? AND organisation_id = ?')->execute([(int)$_GET['delete_rencontre'], $id]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#rencontres');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_projet') {
        db()->prepare('INSERT INTO partenaires_projets (organisation_id, origine_rencontre_id, nom, description, statut, date_debut, date_fin, dossier_drive_lien, notes) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $id, ($_POST['origine_rencontre_id'] ?? '') !== '' ? (int)$_POST['origine_rencontre_id'] : null,
                trim($_POST['nom'] ?? ''), trim($_POST['description'] ?? ''), $_POST['statut'] ?? 'en_cours',
                ($_POST['date_debut'] ?? '') !== '' ? $_POST['date_debut'] : null,
                ($_POST['date_fin'] ?? '') !== '' ? $_POST['date_fin'] : null,
                trim($_POST['dossier_drive_lien'] ?? ''), trim($_POST['notes'] ?? ''),
            ]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#projets');
        exit;
    }
    if (isset($_GET['delete_projet'])) {
        db()->prepare('DELETE FROM partenaires_projets WHERE id = ? AND organisation_id = ?')->execute([(int)$_GET['delete_projet'], $id]);
        header('Location: /admin/partenaire-form.php?id=' . $id . '#projets');
        exit;
    }

    $contacts = db()->prepare('SELECT * FROM partenaires_contacts WHERE organisation_id = ? ORDER BY nom');
    $contacts->execute([$id]);
    $contacts = $contacts->fetchAll();

    $rencontres = db()->prepare('SELECT * FROM partenaires_rencontres WHERE organisation_id = ? ORDER BY date_rencontre DESC');
    $rencontres->execute([$id]);
    $rencontres = $rencontres->fetchAll();

    $projets = db()->prepare('SELECT * FROM partenaires_projets WHERE organisation_id = ? ORDER BY created_at DESC');
    $projets->execute([$id]);
    $projets = $projets->fetchAll();
}

admin_header($id ? 'Modifier ' . $org['nom'] : 'Nouveau partenaire', $user, 'partenaires');
?>
<h1><?= $id ? 'Modifier « ' . htmlspecialchars($org['nom']) . ' »' : 'Nouveau partenaire' ?></h1>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if (!empty($error)): ?><p style="color:var(--mavka-color-danger-text);"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<form method="post" class="mavka-form">
  <input type="hidden" name="action" value="save_organisation">
  <label>Nom <input type="text" name="nom" value="<?= htmlspecialchars($org['nom']) ?>" required></label>
  <label>Type
    <select name="type">
      <?php foreach ($types_labels as $val => $label): ?>
      <option value="<?= $val ?>" <?= $org['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Ville <input type="text" name="ville" value="<?= htmlspecialchars($org['ville'] ?? '') ?>"></label>
  <label>Adresse <input type="text" name="adresse" value="<?= htmlspecialchars($org['adresse'] ?? '') ?>"></label>
  <label>Site web <input type="url" name="site_web" value="<?= htmlspecialchars($org['site_web'] ?? '') ?>" placeholder="https://"></label>
  <label>Email général <input type="email" name="email_general" value="<?= htmlspecialchars($org['email_general'] ?? '') ?>"></label>
  <label>Téléphone <input type="text" name="telephone" value="<?= htmlspecialchars($org['telephone'] ?? '') ?>"></label>
  <label>Statut
    <select name="statut">
      <?php foreach ($statuts_labels as $val => $label): ?>
      <option value="<?= $val ?>" <?= $org['statut'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Dossier Drive (documents) <input type="url" name="dossier_drive_lien" value="<?= htmlspecialchars($org['dossier_drive_lien'] ?? '') ?>" placeholder="https://drive.google.com/..."></label>
  <label>Notes <textarea name="notes"><?= htmlspecialchars($org['notes'] ?? '') ?></textarea></label>
  <button type="submit" class="mavka-btn mavka-btn--primary"><?= $id ? 'Enregistrer' : 'Créer le partenaire' ?></button>
</form>

<?php if ($id): ?>

<div class="mavka-form-section" id="contacts" style="margin-top:32px; padding:20px;">
  <h3>Contacts</h3>
  <?php if ($contacts): ?>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Nom</th><th>Fonction</th><th>Email</th><th>Email secondaire</th><th>Téléphone</th><th>Influence</th><th></th></tr>
    <?php foreach ($contacts as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['nom']) ?></td>
      <td><?= htmlspecialchars($c['fonction'] ?? '') ?></td>
      <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
      <td><?= htmlspecialchars($c['email_secondaire'] ?? '') ?></td>
      <td><?= htmlspecialchars($c['telephone'] ?? '') ?></td>
      <td><?= htmlspecialchars($influence_labels[$c['niveau_influence']] ?? $c['niveau_influence']) ?></td>
      <td><a href="?id=<?= $id ?>&delete_contact=<?= $c['id'] ?>#contacts" class="mavka-btn mavka-btn--sm mavka-btn--danger" onclick="return confirm('Supprimer ce contact ?');">Supprimer</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <details>
    <summary class="mavka-btn mavka-btn--sm">+ Ajouter un contact</summary>
    <form method="post" class="mavka-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_contact">
      <label>Nom <input type="text" name="nom" required></label>
      <label>Fonction <input type="text" name="fonction" list="fonctions_suggestions" placeholder="Ex. 1er adjoint, secrétaire de mairie..."></label>
      <datalist id="fonctions_suggestions">
        <?php foreach ($fonctions_suggestions as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>">
        <?php endforeach; ?>
      </datalist>
      <label>Email <input type="email" name="email"></label>
      <label>Email secondaire <input type="email" name="email_secondaire" placeholder="Ex. email personnel, en plus de celui de la mairie"></label>
      <label>Téléphone <input type="text" name="telephone"></label>
      <label>Langue <input type="text" name="langue" placeholder="Français, ukrainien..."></label>
      <label>Niveau d'influence
        <select name="niveau_influence">
          <?php foreach ($influence_labels as $val => $label): ?>
          <option value="<?= $val ?>" <?= $val === 'inconnu' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Notes <textarea name="notes"></textarea></label>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<div class="mavka-form-section" id="rencontres" style="margin-top:24px; padding:20px;">
  <h3>Rencontres (négociations)</h3>
  <?php if ($rencontres): ?>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Date</th><th>Type</th><th>Sujet</th><th>Étape</th><th>Prochaine action</th><th></th></tr>
    <?php foreach ($rencontres as $r): ?>
    <tr>
      <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rencontre']))) ?></td>
      <td><?= htmlspecialchars($types_rencontre_labels[$r['type']] ?? $r['type']) ?></td>
      <td><?= htmlspecialchars($r['sujet'] ?? '') ?></td>
      <td><?= $r['etape_parcours'] ? htmlspecialchars($etapes_labels[$r['etape_parcours']] ?? $r['etape_parcours']) : '' ?></td>
      <td>
        <?php if ($r['date_prochaine_action']): ?>
          <?= htmlspecialchars($r['prochaine_action'] ?? '') ?> — <?= htmlspecialchars(date('d/m/Y', strtotime($r['date_prochaine_action']))) ?>
          <a href="<?= htmlspecialchars(google_calendar_lien(
              'MAVKA — ' . $org['nom'] . ' : ' . ($r['prochaine_action'] ?: 'Suivi'),
              $r['date_prochaine_action'],
              $r['compte_rendu'] ?? ''
          )) ?>" target="_blank" rel="noopener" title="Ajouter à Google Calendar">📅</a>
        <?php endif; ?>
      </td>
      <td><a href="?id=<?= $id ?>&delete_rencontre=<?= $r['id'] ?>#rencontres" class="mavka-btn mavka-btn--sm mavka-btn--danger" onclick="return confirm('Supprimer cette rencontre ?');">Supprimer</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <details>
    <summary class="mavka-btn mavka-btn--sm">+ Ajouter une rencontre</summary>
    <form method="post" class="mavka-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_rencontre">
      <label>Type
        <select name="type">
          <?php foreach ($types_rencontre_labels as $val => $label): ?>
          <option value="<?= $val ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Date <input type="date" name="date_rencontre" value="<?= date('Y-m-d') ?>" required></label>
      <label>Contact
        <select name="contact_id">
          <option value="">—</option>
          <?php foreach ($contacts as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Sujet <input type="text" name="sujet"></label>
      <label>Compte-rendu <textarea name="compte_rendu"></textarea></label>
      <label>Étape du parcours
        <select name="etape_parcours">
          <option value="">—</option>
          <?php foreach ($etapes_labels as $val => $label): ?>
          <option value="<?= $val ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Prochaine action <input type="text" name="prochaine_action" placeholder="Ex. Envoyer la proposition d'atelier"></label>
      <label>Date de la prochaine action <input type="date" name="date_prochaine_action"></label>
      <label>Responsable MAVKA <input type="text" name="responsable"></label>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<div class="mavka-form-section" id="projets" style="margin-top:24px; padding:20px;">
  <h3>Projets &amp; accords</h3>
  <p class="mavka-form-section__hint">Ce qui découle des rencontres — pas l'inverse.</p>
  <?php if ($projets): ?>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Nom</th><th>Statut</th><th>Début</th><th>Fin</th><th></th></tr>
    <?php foreach ($projets as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['nom']) ?></td>
      <td><?= htmlspecialchars($statuts_projet_labels[$p['statut']] ?? $p['statut']) ?></td>
      <td><?= $p['date_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_debut']))) : '' ?></td>
      <td><?= $p['date_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_fin']))) : '' ?></td>
      <td><a href="?id=<?= $id ?>&delete_projet=<?= $p['id'] ?>#projets" class="mavka-btn mavka-btn--sm mavka-btn--danger" onclick="return confirm('Supprimer ce projet ?');">Supprimer</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <details>
    <summary class="mavka-btn mavka-btn--sm">+ Ajouter un projet</summary>
    <form method="post" class="mavka-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_projet">
      <label>Nom <input type="text" name="nom" required></label>
      <label>Description <textarea name="description"></textarea></label>
      <label>Statut
        <select name="statut">
          <?php foreach ($statuts_projet_labels as $val => $label): ?>
          <option value="<?= $val ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Issu de la rencontre
        <select name="origine_rencontre_id">
          <option value="">—</option>
          <?php foreach ($rencontres as $r): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rencontre']))) ?> — <?= htmlspecialchars($r['sujet'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Date de début <input type="date" name="date_debut"></label>
      <label>Date de fin <input type="date" name="date_fin"></label>
      <label>Dossier Drive <input type="url" name="dossier_drive_lien" placeholder="https://drive.google.com/..."></label>
      <label>Notes <textarea name="notes"></textarea></label>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<?php endif; ?>
<?php admin_footer(); ?>
