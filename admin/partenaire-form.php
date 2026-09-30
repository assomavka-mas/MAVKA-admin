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
    'association' => 'Association', 'entreprise' => 'Entreprise', 'membre_mavka' => 'Membre MAVKA', 'autre' => 'Autre',
];
$statuts_labels = [
    'potentiel' => 'Potentiel', 'actif' => 'Actif', 'partenaire' => 'Partenaire', 'inactif' => 'Inactif', 'en_pause' => 'En pause',
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
// des titres les plus courants, pour ne pas avoir à les connaître par cœur. Une liste par type
// d'organisation : les fonctions d'une mairie n'ont rien à voir avec celles d'une entreprise ou
// d'un·e membre MAVKA. Écriture inclusive au point médian (déjà utilisée partout ailleurs dans
// ce projet — président·e, trésorier·ère...) plutôt qu'un doublon masculin/féminin par titre :
// l'accord réel se fait via le champ "genre" du contact, pas en dupliquant le texte (source du
// bug précédent, où "adjoint" avait sa forme féminine oubliée alors que les autres l'avaient).
$fonctions_suggestions_par_type = [
    'mairie' => [
        'Maire', '1er·ère adjoint·e', '2e adjoint·e', '3e adjoint·e', '4e adjoint·e',
        'Conseiller·ère municipal·e délégué·e', 'Conseiller·ère municipal·e',
        'Membre de commission', 'Vice-président·e', 'Conseiller·ère communautaire',
        'Secrétaire de mairie', 'Directeur·rice général·e des services (DGS)',
        'Chargé·e de mission vie associative',
    ],
    'centre_social' => [
        'Directeur·rice', 'Coordinateur·rice', 'Animateur·rice',
        'Travailleur·euse social·e', 'Référent·e famille',
    ],
    'fondation' => [
        'Président·e', 'Directeur·rice', 'Chargé·e de mission', 'Trésorier·ère', 'Responsable mécénat',
    ],
    'association' => [
        'Président·e', 'Vice-président·e', 'Trésorier·ère', 'Secrétaire', 'Membre du bureau', 'Bénévole',
    ],
    'entreprise' => [
        'Directeur·rice', 'Gérant·e', 'Responsable RH', 'Responsable RSE / mécénat', 'Chargé·e de communication',
    ],
    'membre_mavka' => [
        'Bénévole', 'Volontaire', 'Intervenant·e', 'Membre du bureau',
        'Président·e', 'Trésorier·ère', 'Secrétaire', 'Coordinateur·rice',
    ],
    'autre' => ['Président·e', 'Directeur·rice', 'Responsable', 'Bénévole'],
];
$genre_labels = ['M' => 'M.', 'Mme' => 'Mme', 'non_precise' => 'Non précisé'];

// Champs communs au formulaire "Ajouter un contact" et à celui de modification — $c vide (mode
// ajout) ou pré-rempli (mode modification). La date de nomination d'un maire etc. change peu,
// mais un nom mal orthographié, une fonction ou un genre mal devinés au premier contact,
// Larysa veut pouvoir les corriger au fil de sa connaissance de la personne (voir le "genre"
// pas toujours évident depuis l'étranger — prénoms français pas toujours univoques).
function partenaire_contact_champs(array $c, array $genre_labels, array $influence_labels): void {
    ?>
    <label>Nom <input type="text" name="nom" value="<?= htmlspecialchars($c['nom'] ?? '') ?>" required></label>
    <label>Genre
      <select name="genre">
        <?php foreach ($genre_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($c['genre'] ?? 'non_precise') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Fonction <input type="text" name="fonction" list="fonctions_suggestions" value="<?= htmlspecialchars($c['fonction'] ?? '') ?>" placeholder="Ex. adjoint·e, secrétaire de mairie..."></label>
    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($c['email'] ?? '') ?>"></label>
    <label>Téléphone <input type="text" name="telephone" value="<?= htmlspecialchars($c['telephone'] ?? '') ?>"></label>
    <label>Langue <input type="text" name="langue" value="<?= htmlspecialchars($c['langue'] ?? '') ?>" placeholder="Français, ukrainien..."></label>
    <label>Niveau d'influence
      <select name="niveau_influence">
        <?php foreach ($influence_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($c['niveau_influence'] ?? 'inconnu') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Notes <textarea name="notes"><?= htmlspecialchars($c['notes'] ?? '') ?></textarea></label>
    <?php
}

// Sélecteur de participant·e·s pour une rencontre ou un projet : on choisit d'abord une
// organisation dans la liste déroulante, puis on coche ses membres — évite d'afficher tous les
// contacts de tous les partenaires en même temps (illisible dès qu'il y en a beaucoup). Les
// personnes cochées apparaissent en résumé (chips) au-dessus, même si le groupe correspondant
// n'est plus affiché — voir le script partagé .mavka-participants-picker en bas de page.
function render_participants_picker(array $contacts_par_org, array $selectionnes, string $name): void {
    ?>
    <div class="mavka-participants-picker">
      <div class="mavka-participants-chips"></div>
      <select class="mavka-participants-select">
        <option value="">— Choisir une organisation pour cocher ses membres —</option>
        <?php foreach ($contacts_par_org as $organisation_nom => $contacts_org): ?>
        <option value="<?= htmlspecialchars($organisation_nom) ?>"><?= htmlspecialchars($organisation_nom) ?> (<?= count($contacts_org) ?>)</option>
        <?php endforeach; ?>
      </select>
      <?php foreach ($contacts_par_org as $organisation_nom => $contacts_org): ?>
      <fieldset class="mavka-participants__groupe" data-org="<?= htmlspecialchars($organisation_nom) ?>" hidden>
        <legend><?= htmlspecialchars($organisation_nom) ?></legend>
        <?php foreach ($contacts_org as $c): ?>
        <label class="mavka-participants__item">
          <input type="checkbox" name="<?= htmlspecialchars($name) ?>[]" value="<?= $c['id'] ?>" data-nom="<?= htmlspecialchars($c['nom']) ?>" <?= in_array((int)$c['id'], $selectionnes, true) ? 'checked' : '' ?>>
          <?= htmlspecialchars($c['nom']) ?>
        </label>
        <?php endforeach; ?>
      </fieldset>
      <?php endforeach; ?>
    </div>
    <?php
}

// Champs communs à "+ Ajouter une rencontre" et à "Modifier" (par ligne, dans le tableau) — un
// seul jeu de champs pour éviter que les deux formulaires divergent au fil des futures évolutions.
function rencontre_champs(array $r, array $contacts_par_org, array $participant_ids, array $types_rencontre_labels, string $adresse_defaut = ''): void {
    ?>
    <label>Type
      <select name="type">
        <?php foreach ($types_rencontre_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($r['type'] ?? 'rencontre') === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="row">
      <div style="flex:0 0 160px;">
        <label>Date <input type="date" name="date_rencontre" value="<?= htmlspecialchars($r['date_rencontre'] ?? date('Y-m-d')) ?>" required></label>
      </div>
      <div style="flex:0 0 120px;">
        <label>Heure <input type="time" name="heure_rencontre" value="<?= htmlspecialchars($r['heure_rencontre'] ? substr($r['heure_rencontre'], 0, 5) : '') ?>"></label>
      </div>
    </div>
    <label>Lieu / adresse <input type="text" name="lieu" value="<?= htmlspecialchars($r['lieu'] ?? $adresse_defaut) ?>" placeholder="Ex. 12 rue de la Mairie, Garat"></label>
    <label>Sujet <input type="text" name="sujet" value="<?= htmlspecialchars($r['sujet'] ?? '') ?>"></label>
    <label>Participants</label>
    <?php render_participants_picker($contacts_par_org, $participant_ids, 'participant_ids'); ?>
    <label>Compte-rendu <textarea name="compte_rendu"><?= htmlspecialchars($r['compte_rendu'] ?? '') ?></textarea></label>
    <label>Responsable MAVKA <input type="text" name="responsable" value="<?= htmlspecialchars($r['responsable'] ?? '') ?>"></label>
    <?php
}

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
$fonctions_suggestions = $fonctions_suggestions_par_type[$org['type']] ?? $fonctions_suggestions_par_type['autre'];

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
        $genre = in_array($_POST['genre'] ?? '', array_keys($genre_labels), true) ? $_POST['genre'] : 'non_precise';
        db()->prepare('INSERT INTO partenaires_contacts (organisation_id, nom, genre, fonction, email, telephone, langue, niveau_influence, notes) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $id, trim($_POST['nom'] ?? ''), $genre, trim($_POST['fonction'] ?? ''), trim($_POST['email'] ?? ''),
                trim($_POST['telephone'] ?? ''), trim($_POST['langue'] ?? ''),
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
        db()->prepare('INSERT INTO partenaires_rencontres (organisation_id, type, date_rencontre, heure_rencontre, lieu, sujet, compte_rendu, responsable) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([
                $id, $_POST['type'] ?? 'rencontre', $_POST['date_rencontre'] ?? date('Y-m-d'),
                ($_POST['heure_rencontre'] ?? '') !== '' ? $_POST['heure_rencontre'] : null,
                trim($_POST['lieu'] ?? ''),
                trim($_POST['sujet'] ?? ''), trim($_POST['compte_rendu'] ?? ''),
                trim($_POST['responsable'] ?? ''),
            ]);
        $nouvelle_rencontre_id = (int)db()->lastInsertId();
        $participants = array_map('intval', $_POST['participant_ids'] ?? []);
        if ($participants) {
            $stmt = db()->prepare('INSERT INTO partenaires_rencontres_participants (rencontre_id, contact_id) VALUES (?,?)');
            foreach ($participants as $contact_id) { $stmt->execute([$nouvelle_rencontre_id, $contact_id]); }
        }
        header('Location: /admin/partenaire-form.php?id=' . $id . '#rencontres');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_rencontre') {
        $rencontre_id = (int)($_POST['rencontre_id'] ?? 0);
        db()->prepare('UPDATE partenaires_rencontres SET type=?, date_rencontre=?, heure_rencontre=?, lieu=?, sujet=?, compte_rendu=?, responsable=? WHERE id=? AND organisation_id=?')
            ->execute([
                $_POST['type'] ?? 'rencontre', $_POST['date_rencontre'] ?? date('Y-m-d'),
                ($_POST['heure_rencontre'] ?? '') !== '' ? $_POST['heure_rencontre'] : null,
                trim($_POST['lieu'] ?? ''),
                trim($_POST['sujet'] ?? ''), trim($_POST['compte_rendu'] ?? ''),
                trim($_POST['responsable'] ?? ''),
                $rencontre_id, $id,
            ]);
        db()->prepare('DELETE FROM partenaires_rencontres_participants WHERE rencontre_id = ?')->execute([$rencontre_id]);
        $participants = array_map('intval', $_POST['participant_ids'] ?? []);
        if ($participants) {
            $stmt = db()->prepare('INSERT INTO partenaires_rencontres_participants (rencontre_id, contact_id) VALUES (?,?)');
            foreach ($participants as $contact_id) { $stmt->execute([$rencontre_id, $contact_id]); }
        }
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
        $nouveau_projet_id = (int)db()->lastInsertId();
        $participants = array_map('intval', $_POST['participant_ids'] ?? []);
        if ($participants) {
            $stmt = db()->prepare('INSERT INTO partenaires_projets_participants (projet_id, contact_id) VALUES (?,?)');
            foreach ($participants as $contact_id) { $stmt->execute([$nouveau_projet_id, $contact_id]); }
        }
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

    // Tous les contacts, toutes organisations confondues, groupés par organisation — pour
    // choisir des participant·e·s (volontaires MAVKA compris·es) sans se limiter à cette fiche.
    $tous_contacts = db()->query('
        SELECT c.id, c.nom, o.nom AS organisation_nom
        FROM partenaires_contacts c
        JOIN partenaires_organisations o ON o.id = c.organisation_id
        ORDER BY o.nom, c.nom
    ')->fetchAll();
    $tous_contacts_par_org = [];
    foreach ($tous_contacts as $c) {
        $tous_contacts_par_org[$c['organisation_nom']][] = $c;
    }

    $rencontres = db()->prepare('
        SELECT r.*,
            GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ", ") AS participants_noms,
            GROUP_CONCAT(DISTINCT rp.contact_id) AS participant_ids_str
        FROM partenaires_rencontres r
        LEFT JOIN partenaires_rencontres_participants rp ON rp.rencontre_id = r.id
        LEFT JOIN partenaires_contacts c ON c.id = rp.contact_id
        WHERE r.organisation_id = ?
        GROUP BY r.id
        ORDER BY r.date_rencontre DESC, r.heure_rencontre DESC
    ');
    $rencontres->execute([$id]);
    $rencontres = $rencontres->fetchAll();

    $projets = db()->prepare('
        SELECT p.*, GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ", ") AS participants_noms
        FROM partenaires_projets p
        LEFT JOIN partenaires_projets_participants pp ON pp.projet_id = p.id
        LEFT JOIN partenaires_contacts c ON c.id = pp.contact_id
        WHERE p.organisation_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ');
    $projets->execute([$id]);
    $projets = $projets->fetchAll();
}

admin_header($id ? 'Modifier ' . $org['nom'] : 'Nouveau partenaire', $user, 'partenaires');
?>
<h1><?= $id ? 'Modifier « ' . htmlspecialchars($org['nom']) . ' »' : 'Nouveau partenaire' ?></h1>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<?php if (!empty($error)): ?><p style="color:var(--mavka-color-danger-text);"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<?php if ($id): ?>
<!-- Une fois l'organisation créée, tout se range en onglets (Organisation/Contacts/Rencontres/
     Projets) — un seul bloc visible à la fois plutôt qu'une longue page qui s'allonge avec le
     nombre de contacts saisis. Avant création, il n'y a que le formulaire de l'organisation. -->
<div class="mavka-tabs" style="justify-content:flex-start; margin-bottom:18px;">
  <button type="button" class="mavka-tab" data-tab-target="organisation">Organisation</button>
  <button type="button" class="mavka-tab" data-tab-target="contacts">Contacts</button>
  <button type="button" class="mavka-tab" data-tab-target="rencontres">Rencontres</button>
  <button type="button" class="mavka-tab" data-tab-target="projets">Projets &amp; accords</button>
</div>
<?php endif; ?>

<div data-tab-panel="organisation">
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
</div>

<?php if ($id): ?>

<div class="mavka-form-section" data-tab-panel="contacts" style="padding:20px;">
  <datalist id="fonctions_suggestions">
    <?php foreach ($fonctions_suggestions as $f): ?>
    <option value="<?= htmlspecialchars($f) ?>">
    <?php endforeach; ?>
  </datalist>
  <?php if ($contacts): ?>
  <p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:0 0 8px;">Clique une cellule pour la corriger directement — pratique pour ajuster au fil de ta connaissance de la personne (orthographe, genre, fonction...).</p>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Genre</th><th>Nom</th><th>Fonction</th><th>Email</th><th>Téléphone</th><th>Influence</th><th>Notes</th><th></th></tr>
    <?php foreach ($contacts as $c): ?>
    <tr>
      <td>
        <select data-contact-editable-select data-id="<?= $c['id'] ?>" data-field="genre">
          <?php foreach ($genre_labels as $val => $label): ?>
          <option value="<?= $val ?>" <?= $c['genre'] === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><span class="mavka-editable" data-contact-editable data-id="<?= $c['id'] ?>" data-field="nom"><?= htmlspecialchars($c['nom']) ?></span></td>
      <td><span class="mavka-editable" data-contact-editable data-id="<?= $c['id'] ?>" data-field="fonction" data-list="fonctions_suggestions"><?= htmlspecialchars($c['fonction'] ?? '') ?></span></td>
      <td><span class="mavka-editable" data-contact-editable data-id="<?= $c['id'] ?>" data-field="email"><?= htmlspecialchars($c['email'] ?? '') ?></span></td>
      <td><span class="mavka-editable" data-contact-editable data-id="<?= $c['id'] ?>" data-field="telephone"><?= htmlspecialchars($c['telephone'] ?? '') ?></span></td>
      <td>
        <select data-contact-editable-select data-id="<?= $c['id'] ?>" data-field="niveau_influence">
          <?php foreach ($influence_labels as $val => $label): ?>
          <option value="<?= $val ?>" <?= $c['niveau_influence'] === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td style="max-width:220px;">
        <span class="mavka-editable" data-contact-editable data-field-type="textarea" data-id="<?= $c['id'] ?>" data-field="notes"><?= htmlspecialchars($c['notes'] ?? '') ?></span>
      </td>
      <td><a href="?id=<?= $id ?>&delete_contact=<?= $c['id'] ?>#contacts" class="mavka-btn mavka-btn--sm mavka-btn--danger" onclick="return confirm('Supprimer ce contact ?');">Supprimer</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <details>
    <summary class="mavka-btn mavka-btn--sm">+ Ajouter un contact</summary>
    <form method="post" class="mavka-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_contact">
      <?php partenaire_contact_champs([], $genre_labels, $influence_labels); ?>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<script>
(function(){
  function envoyer(id, field, value, onOk, onErr){
    fetch('/admin/partenaire-contact-inline-update.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({id: id, field: field, value: value})
    }).then(function(r){ return r.json(); }).then(function(data){
      if (data.ok) onOk(data.value); else onErr(data.error || 'Erreur');
    }).catch(function(){ onErr('Impossible de contacter le serveur.'); });
  }

  document.querySelectorAll('[data-contact-editable]').forEach(function(span){
    span.addEventListener('click', function(){
      if (span.querySelector('input, textarea')) return;
      var estTextarea = span.dataset.fieldType === 'textarea';
      var original = span.textContent;
      var input = document.createElement(estTextarea ? 'textarea' : 'input');
      if (!estTextarea) {
        input.type = span.dataset.field === 'email' ? 'email' : 'text';
        if (span.dataset.list) input.setAttribute('list', span.dataset.list);
      } else {
        input.rows = 3;
        input.style.width = '100%';
      }
      input.value = original;
      span.textContent = '';
      span.appendChild(input);
      input.focus();
      input.select();

      function fermer(nouvelleValeur){ span.textContent = nouvelleValeur; }
      function tenterEnregistrer(){
        var val = input.value;
        if (val === original) { fermer(original); return; }
        envoyer(span.dataset.id, span.dataset.field, val, function(saved){
          fermer(saved);
        }, function(err){
          alert(err);
          fermer(original);
        });
      }
      input.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && !estTextarea) { e.preventDefault(); input.blur(); }
        if (e.key === 'Escape') { input.value = original; input.blur(); }
      });
      input.addEventListener('blur', tenterEnregistrer);
    });
  });

  document.querySelectorAll('[data-contact-editable-select]').forEach(function(select){
    select.addEventListener('change', function(){
      var original = select.dataset.original || select.value;
      var nouvelleValeur = select.value;
      envoyer(select.dataset.id, select.dataset.field, nouvelleValeur, function(){
        select.dataset.original = nouvelleValeur;
      }, function(err){
        alert(err);
        select.value = original;
      });
    });
  });
})();
</script>

<div class="mavka-form-section" data-tab-panel="rencontres" style="padding:20px;">
  <?php if ($rencontres): ?>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Date</th><th>Type</th><th>Lieu</th><th>Sujet</th><th>Participants</th><th></th></tr>
    <?php foreach ($rencontres as $r): ?>
    <?php $participant_ids = $r['participant_ids_str'] ? array_map('intval', explode(',', $r['participant_ids_str'])) : []; ?>
    <tr>
      <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_rencontre']))) ?><?= $r['heure_rencontre'] ? ' à ' . htmlspecialchars(substr($r['heure_rencontre'], 0, 5)) : '' ?></td>
      <td><?= htmlspecialchars($types_rencontre_labels[$r['type']] ?? $r['type']) ?></td>
      <td><?= htmlspecialchars($r['lieu'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['sujet'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['participants_noms'] ?? '') ?: '—' ?></td>
      <td style="white-space:nowrap;">
        <button type="button" class="mavka-btn mavka-btn--sm" data-toggle-edit-rencontre="<?= $r['id'] ?>">Modifier</button>
        <a href="?id=<?= $id ?>&delete_rencontre=<?= $r['id'] ?>#rencontres" class="mavka-btn mavka-btn--sm mavka-btn--danger" onclick="return confirm('Supprimer cette rencontre ?');">Supprimer</a>
      </td>
    </tr>
    <tr id="edit-rencontre-<?= $r['id'] ?>" style="display:none;">
      <td colspan="6" style="background:var(--mavka-color-cream-soft);">
        <form method="post" class="mavka-form" style="margin:12px 0;">
          <input type="hidden" name="action" value="edit_rencontre">
          <input type="hidden" name="rencontre_id" value="<?= $r['id'] ?>">
          <?php rencontre_champs($r, $tous_contacts_par_org, $participant_ids, $types_rencontre_labels); ?>
          <button type="submit" class="mavka-btn mavka-btn--primary">Enregistrer</button>
          <button type="button" class="mavka-btn mavka-btn--sm" data-toggle-edit-rencontre="<?= $r['id'] ?>">Annuler</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <script>
  document.querySelectorAll('[data-toggle-edit-rencontre]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var row = document.getElementById('edit-rencontre-' + btn.dataset.toggleEditRencontre);
      row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    });
  });
  </script>
  <?php endif; ?>
  <details>
    <summary class="mavka-btn mavka-btn--sm">+ Ajouter une rencontre</summary>
    <form method="post" class="mavka-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_rencontre">
      <?php rencontre_champs([], $tous_contacts_par_org, [], $types_rencontre_labels, $org['adresse'] ?? ''); ?>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<div class="mavka-form-section" data-tab-panel="projets" style="padding:20px;">
  <p class="mavka-form-section__hint">Ce qui découle des rencontres — pas l'inverse.</p>
  <?php if ($projets): ?>
  <table class="mavka-table" style="margin-bottom:16px;">
    <tr><th>Nom</th><th>Statut</th><th>Début</th><th>Fin</th><th>Participants</th><th></th></tr>
    <?php foreach ($projets as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['nom']) ?></td>
      <td><?= htmlspecialchars($statuts_projet_labels[$p['statut']] ?? $p['statut']) ?></td>
      <td><?= $p['date_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_debut']))) : '' ?></td>
      <td><?= $p['date_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($p['date_fin']))) : '' ?></td>
      <td><?= htmlspecialchars($p['participants_noms'] ?? '') ?: '—' ?></td>
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
      <label>Participants</label>
      <?php render_participants_picker($tous_contacts_par_org, [], 'participant_ids'); ?>
      <label>Dossier Drive <input type="url" name="dossier_drive_lien" placeholder="https://drive.google.com/..."></label>
      <label>Notes <textarea name="notes"></textarea></label>
      <button type="submit" class="mavka-btn mavka-btn--primary">Ajouter</button>
    </form>
  </details>
</div>

<script>
(function(){
  var ongletsValides = ['organisation', 'contacts', 'rencontres', 'projets'];
  var boutons = document.querySelectorAll('.mavka-tab[data-tab-target]');
  var panneaux = document.querySelectorAll('[data-tab-panel]');
  function activer(nom){
    boutons.forEach(function(b){ b.classList.toggle('mavka-tab--active', b.dataset.tabTarget === nom); });
    panneaux.forEach(function(p){ p.hidden = p.dataset.tabPanel !== nom; });
  }
  boutons.forEach(function(b){
    b.addEventListener('click', function(){
      activer(b.dataset.tabTarget);
      history.replaceState(null, '', '#' + b.dataset.tabTarget);
    });
  });
  var initial = (location.hash || '').replace('#', '');
  activer(ongletsValides.includes(initial) ? initial : 'organisation');
})();
</script>

<script>
(function(){
  // Sélecteur "Participants" : choisir une organisation affiche ses membres à cocher, et un
  // résumé (chips) au-dessus reste à jour même quand le groupe correspondant est masqué —
  // plusieurs instances possibles par page (Ajouter une rencontre, une ligne "Modifier" par
  // rencontre existante, Ajouter un projet), chacune isolée à son propre conteneur.
  document.querySelectorAll('.mavka-participants-picker').forEach(function(picker){
    var chips = picker.querySelector('.mavka-participants-chips');
    var select = picker.querySelector('.mavka-participants-select');

    function rafraichirChips(){
      chips.innerHTML = '';
      picker.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb){
        var chip = document.createElement('span');
        chip.className = 'mavka-participants-chip';
        chip.appendChild(document.createTextNode(cb.dataset.nom));
        var retirer = document.createElement('button');
        retirer.type = 'button';
        retirer.textContent = '×';
        retirer.title = 'Retirer';
        retirer.addEventListener('click', function(){
          cb.checked = false;
          rafraichirChips();
        });
        chip.appendChild(retirer);
        chips.appendChild(chip);
      });
    }

    select.addEventListener('change', function(){
      picker.querySelectorAll('.mavka-participants__groupe').forEach(function(g){ g.hidden = true; });
      if (select.value) {
        picker.querySelectorAll('.mavka-participants__groupe').forEach(function(g){
          if (g.dataset.org === select.value) g.hidden = false;
        });
      }
    });

    picker.addEventListener('change', function(e){
      if (e.target.matches('input[type="checkbox"]')) rafraichirChips();
    });

    rafraichirChips(); // état initial (modification d'une rencontre déjà renseignée)
  });
})();
</script>

<?php endif; ?>
<?php admin_footer(); ?>
