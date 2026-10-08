<?php
// Outil à usage unique : ouvrir cette page une fois (connectée en admin), vérifier le rapport,
// puis supprimer ce fichier — ce n'est pas une page d'administration permanente.
//
// Même schéma que admin/seed-activites-2026-09.php (déjà exécuté) : une carte-vitrine par
// volontaire sur l'accueil ("Voir sa page"), et ses offres détaillées en "Ce que je propose"
// sur sa page — 2 ateliers collectifs (Garat/Soyaux, pour les échanges avec les mairies) et
// 1 atelier individuel (Grand Angoulême) chacune, pour Nataliia Veremeienko et Nadiia Denysenko.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

function seed_trouver_intervenant(string $nom): ?array {
    $stmt = db()->prepare('SELECT id, nom FROM intervenants WHERE LOWER(nom) = LOWER(?) LIMIT 1');
    $stmt->execute([$nom]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function seed_activite_existe(string $titre): bool {
    $stmt = db()->prepare('SELECT id FROM activites WHERE titre = ? LIMIT 1');
    $stmt->execute([$titre]);
    return (bool)$stmt->fetch();
}

function seed_atelier_existe(int $intervenantId, string $titre): bool {
    $stmt = db()->prepare('SELECT id FROM intervenant_ateliers WHERE intervenant_id = ? AND titre = ? LIMIT 1');
    $stmt->execute([$intervenantId, $titre]);
    return (bool)$stmt->fetch();
}

function seed_creer_activite(array $a, array $intervenantIds): int {
    $stmt = db()->prepare('INSERT INTO activites
        (titre, categorie, categorie_display, format, public, description, date_debut, heure, recurrence,
         lieu, nombre_places, ville, texte_bouton, lien_inscription, statut, statut_activite, ordre)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $a['titre'], $a['categorie'], $a['categorie_display'] ?? null, $a['format'] ?? null, $a['public'] ?? null,
        $a['description'] ?? null, $a['date_debut'] ?? null, $a['heure'] ?? null, $a['recurrence'] ?? null,
        $a['lieu'] ?? null, $a['nombre_places'] ?? null, $a['ville'] ?? null,
        $a['texte_bouton'] ?? 'En savoir plus', $a['lien_inscription'] ?? null,
        'publie', 'ouvert', 0,
    ]);
    $id = (int)db()->lastInsertId();
    if ($intervenantIds) {
        $ins = db()->prepare('INSERT INTO activite_intervenant (activite_id, intervenant_id) VALUES (?, ?)');
        foreach ($intervenantIds as $ivId) {
            $ins->execute([$id, $ivId]);
        }
    }
    return $id;
}

function seed_creer_atelier(int $intervenantId, string $titre, string $description, int $ordre): int {
    $stmt = db()->prepare('INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (?,?,?,?)');
    $stmt->execute([$intervenantId, $titre, $description, $ordre]);
    return (int)db()->lastInsertId();
}

/** Carte-vitrine + 3 ateliers (2 collectifs Garat/Soyaux, 1 individuel Grand Angoulême) pour une musicienne. */
function seed_musicienne(string $nom, string $descriptionVitrine, array $ateliers, array &$rapport): void {
    $iv = seed_trouver_intervenant($nom);
    $rapport[] = $iv
        ? "✅ Intervenant·e trouvé·e : $nom (id={$iv['id']})"
        : "⚠️ Intervenant·e INTROUVABLE : $nom — rien ne sera créé pour elle.";
    if (!$iv) return;

    $titre = "Musique avec $nom";
    if (seed_activite_existe($titre)) {
        $rapport[] = "⏭️ Activité déjà présente, ignorée : $titre";
    } else {
        seed_creer_activite([
            'titre' => $titre,
            'categorie' => 'Culture',
            'categorie_display' => 'Culture · Musique',
            'description' => $descriptionVitrine,
            'ville' => 'Grand Angoulême',
            'texte_bouton' => 'Voir sa page',
            'lien_inscription' => intervenant_page_url((int)$iv['id']),
        ], [$iv['id']]);
        $rapport[] = "✅ Activité créée : $titre";
    }

    foreach ($ateliers as $i => [$at_titre, $at_description]) {
        if (seed_atelier_existe((int)$iv['id'], $at_titre)) {
            $rapport[] = "⏭️ Atelier déjà présent, ignoré : $at_titre";
        } else {
            seed_creer_atelier((int)$iv['id'], $at_titre, $at_description, $i);
            $rapport[] = "✅ Atelier créé (page de $nom) : $at_titre";
        }
    }
}

$rapport = [];

// --- Nataliia Veremeienko — saxophone, flûte, initiation, éveil musical, interculturel --------
seed_musicienne(
    'Nataliia Veremeienko',
    "Envie de découvrir le saxophone, la flûte ou l'éveil musical ? Nataliia Veremeienko propose au sein de MAVKA des ateliers d'initiation à la musique, de pratique collective et de rencontres interculturelles autour de la culture musicale.\n\n"
        . "Ouverts aux débutants comme aux personnes ayant déjà une expérience musicale. Découvrez ses activités et les préinscriptions disponibles.",
    [
        ['Découverte du saxophone et de la flûte à Garat',
            "Vous êtes curieux(se) de découvrir le saxophone ou la flûte, seul(e) ou en famille ?\n\n"
            . "MAVKA envisage la création d'un atelier de découverte musicale à Garat, ouvert aux débutants comme aux personnes ayant déjà une expérience.\n\n"
            . "Cet atelier collectif permettra une première approche des instruments, dans une ambiance conviviale et bienveillante, avec des temps de pratique en groupe.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Éveil musical et rencontres interculturelles à Soyaux',
            "MAVKA envisage la création d'un atelier d'éveil musical à Soyaux, pensé pour les enfants comme pour les adultes.\n\n"
            . "Au programme : découverte d'instruments, expression artistique à travers la musique, et rencontres interculturelles autour de la culture musicale.\n\n"
            . "Cet atelier collectif est ouvert à tous les niveaux.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Cours individuels de saxophone et de flûte',
            "Nataliia Veremeienko propose des cours individuels de saxophone et de flûte, adaptés au niveau et aux objectifs de chacun.\n\n"
            . "Les cours s'adressent aux débutants ainsi qu'aux personnes souhaitant progresser ou reprendre la pratique d'un instrument.\n\n"
            . "Les séances sont organisées à Grand Angoulême, selon les disponibilités.\n\n"
            . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante."],
    ],
    $rapport
);

// --- Nadiia Denysenko — domra, mandoline, piano, guitare, chant, instruments traditionnels ----
seed_musicienne(
    'Nadiia Denysenko',
    "Envie d'apprendre le piano, la guitare, le chant, ou de découvrir des instruments traditionnels comme la domra et la mandoline ? Nadiia Denysenko propose au sein de MAVKA des ateliers et cours adaptés à l'âge, au niveau et aux objectifs de chacun.\n\n"
        . "Découvrez ses activités et les préinscriptions disponibles.",
    [
        ['Découverte des instruments traditionnels (domra et mandoline) à Garat',
            "Envie de découvrir des instruments traditionnels peu connus, la domra et la mandoline ?\n\n"
            . "MAVKA envisage la création d'un atelier de découverte musicale à Garat, ouvert aux débutants comme aux personnes déjà initiées à la musique.\n\n"
            . "Cet atelier collectif permettra une première approche de ces instruments, dans une ambiance conviviale, avec des temps de pratique en groupe.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Éveil musical et créativité pour enfants à Soyaux',
            "MAVKA envisage la création d'un atelier d'éveil musical à Soyaux, pensé pour les enfants (piano dès 6 ans, guitare dès 6 ans) qui souhaitent développer leur créativité à travers la musique.\n\n"
            . "Au programme : premiers pas au piano ou à la guitare, chant, et jeux musicaux en groupe.\n\n"
            . "Cet atelier collectif est adapté à l'âge et au niveau de chacun.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Cours individuels de piano, guitare et chant',
            "Nadiia Denysenko propose des cours individuels de piano (6–14 ans), de guitare (débutants dès 6 ans) et de chant moderne / variété, adaptés à l'âge, au niveau et aux objectifs de chacun.\n\n"
            . "Les séances sont organisées à Grand Angoulême, selon les disponibilités.\n\n"
            . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante."],
    ],
    $rapport
);

admin_header('Seed activités — musiciennes', $user, 'activites');
?>
<h1>Résultat</h1>
<div class="mavka-form-section__body" style="background:#fff; border-radius:12px; padding:20px 24px; max-width:720px;">
  <ul style="line-height:1.9; font-size:14.5px; margin:0; padding-left:20px;">
    <?php foreach ($rapport as $ligne): ?>
    <li><?= htmlspecialchars($ligne) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<p style="max-width:720px; margin-top:16px;">
  <a href="/admin/activites.php">→ Voir les activités</a> ·
  <strong>Pense à supprimer ce fichier (admin/seed-activites-2026-09-musiciennes.php) une fois vérifié.</strong>
</p>
<?php admin_footer(); ?>
