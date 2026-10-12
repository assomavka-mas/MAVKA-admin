<?php
// Outil à usage unique : ouvrir cette page une fois (connectée en admin), vérifier le rapport,
// puis supprimer ce fichier — ce n'est pas une page d'administration permanente.
//
// Crée les activités et ateliers demandés par Larysa (issus des captures de l'ancien site
// WordPress), pour avoir du contenu réaliste sur le site en cours de conception. Idempotent :
// relancer la page ne recrée pas les lignes déjà présentes (comparaison par titre).
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

/** @param array<int,int> $intervenantIds */
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

$rapport = [];

// --- Volontaires attendus (déjà créés dans Intervenants, d'après Larysa) -----------------
$william = seed_trouver_intervenant('William Aubert');
$hanna = seed_trouver_intervenant('Hanna Sokha');
$snizhana = seed_trouver_intervenant('Snizhana Zhuravlova');

foreach (['William Aubert' => $william, 'Hanna Sokha' => $hanna, 'Snizhana Zhuravlova' => $snizhana] as $nom => $iv) {
    $rapport[] = $iv
        ? "✅ Intervenant·e trouvé·e : $nom (id={$iv['id']})"
        : "⚠️ Intervenant·e INTROUVABLE : $nom — l'activité sera créée sans lien vers son profil.";
}

// --- 1. Art-Thérapie Évolutive — William AUBERT ------------------------------------------
$titre = 'Art-Thérapie Évolutive';
if (seed_activite_existe($titre)) {
    $rapport[] = "⏭️ Activité déjà présente, ignorée : $titre";
} else {
    seed_creer_activite([
        'titre' => $titre,
        'categorie' => 'Bien-être',
        'categorie_display' => 'Bien-être · Art-thérapie',
        'format' => 'Collectif',
        'description' => "Découvrez l'Art-Thérapie Évolutive lors d'une séance d'initiation ouverte à tous.\n\n"
            . "Aucun talent artistique n'est nécessaire. Venez vivre une première expérience créative, dans un cadre bienveillant, et échanger avec l'intervenant autour de cette approche.\n\n"
            . "Préinscription gratuite recommandée.",
        'date_debut' => '2026-10-01',
        'heure' => '18:00',
        'lieu' => 'Salle de temps libre',
        'ville' => 'Garat',
        'texte_bouton' => 'Préinscription gratuite',
    ], $william ? [$william['id']] : []);
    $rapport[] = "✅ Activité créée : $titre";
}

// --- 2. Festival du jeu Garat (pas de volontaire nommé) ----------------------------------
$titre = 'Festival du jeu Garat';
if (seed_activite_existe($titre)) {
    $rapport[] = "⏭️ Activité déjà présente, ignorée : $titre";
} else {
    seed_creer_activite([
        'titre' => $titre,
        'categorie' => 'Culture',
        'format' => 'Événementiel',
        'public' => 'Familial',
        'description' => "En famille ou entre amis, petits et grands sont invités à partager un moment chaleureux et convivial autour d'une multitude d'activités : jeux de société (réflexion, stratégie, ambiance), grands jeux géants en extérieur, espace dédié aux tout-petits, espace jeux vidéo, ainsi que des démonstrations d'impression 3D.",
        'date_debut' => '2026-09-26',
        'heure' => '14:00',
        'lieu' => "Salle de l'Atrium",
        'ville' => 'Garat',
        'texte_bouton' => 'En savoir plus',
    ], []);
    $rapport[] = "✅ Activité créée : $titre";
}

// --- 3. Gymnastique articulaire — Hanna SOKHA (récurrent, le jeudi) ----------------------
$titre = 'Gymnastique articulaire';
if (seed_activite_existe($titre)) {
    $rapport[] = "⏭️ Activité déjà présente, ignorée : $titre";
} else {
    seed_creer_activite([
        'titre' => $titre,
        'categorie' => 'Bien-être',
        'format' => 'Collectif',
        'public' => 'Adultes',
        'description' => "Vous êtes intéressé(e) par des séances de gymnastique articulaire le jeudi matin à Garat ?\n\n"
            . "Cette préinscription gratuite et sans engagement nous permet de connaître le nombre de personnes intéressées et d'étudier avec la mairie la possibilité de mettre en place cette activité.\n\n"
            . "Laissez vos coordonnées pour être informé(e) si les séances peuvent être organisées.",
        'recurrence' => 'Le jeudi matin',
        'heure' => '09:30',
        'lieu' => "Salle de l'Atrium",
        'ville' => 'Garat',
        'texte_bouton' => 'Préinscription',
    ], $hanna ? [$hanna['id']] : []);
    $rapport[] = "✅ Activité créée : $titre";
}

// --- 4. Musique avec Snizhana ZHURAVLOVA — carte-vitrine vers sa page --------------------
$titre = 'Musique avec Snizhana Zhuravlova';
if (seed_activite_existe($titre)) {
    $rapport[] = "⏭️ Activité déjà présente, ignorée : $titre";
} else {
    seed_creer_activite([
        'titre' => $titre,
        'categorie' => 'Culture',
        'categorie_display' => 'Culture · Musique',
        'description' => "Envie d'apprendre, de reprendre un instrument ou de jouer avec d'autres ? Snizhana ZHURAVLOVA propose au sein de MAVKA des activités autour du violon, du piano, de la formation musicale, du chant et de la musique d'ensemble.\n\n"
            . "Pour débutants ou musiciens plus expérimentés, enfants et adultes. Découvrez ses activités et les préinscriptions disponibles.",
        'ville' => 'Grand Angoulême',
        'texte_bouton' => 'Voir sa page',
        'lien_inscription' => $snizhana ? intervenant_page_url((int)$snizhana['id']) : null,
    ], $snizhana ? [$snizhana['id']] : []);
    $rapport[] = "✅ Activité créée : $titre" . ($snizhana ? '' : " (⚠️ sans lien — profil introuvable, à corriger à la main)");
}

// --- Ateliers possibles de Snizhana (sur sa page volontaire, pas sur l'accueil) ----------
if ($snizhana) {
    $ateliers = [
        ['Atelier de musique d\'ensemble à Garat',
            "Vous pratiquez déjà un instrument et souhaitez apprendre à jouer avec d'autres musiciens ?\n\n"
            . "MAVKA envisage la création d'un atelier de musique d'ensemble à Garat.\n\n"
            . "Ces ateliers permettront de découvrir le jeu collectif, d'apprendre à écouter les autres et à jouer ensemble. Selon les participants et les instruments présents, ils pourront évoluer vers la création de petits ensembles musicaux.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Atelier de musique d\'ensemble à Soyaux',
            "Vous pratiquez déjà un instrument et souhaitez apprendre à jouer avec d'autres musiciens ?\n\n"
            . "MAVKA envisage la création d'un atelier de musique d'ensemble à Soyaux.\n\n"
            . "Ces ateliers permettront de découvrir le jeu collectif, d'apprendre à écouter les autres et à jouer ensemble. Selon les participants et les instruments présents, ils pourront évoluer vers la création de petits ensembles musicaux.\n\n"
            . "Préinscription gratuite et sans engagement."],
        ['Cours individuels de musique',
            "MAVKA propose des cours individuels de violon, piano et formation musicale, adaptés au niveau et aux objectifs de chacun.\n\n"
            . "Les cours s'adressent aux débutants ainsi qu'aux personnes souhaitant reprendre la musique après une interruption.\n\n"
            . "Les séances sont organisées chez l'élève, qui doit disposer de l'instrument nécessaire à son apprentissage.\n\n"
            . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante. La préinscription permet de préciser vos besoins, votre instrument et vos disponibilités avant l'organisation du premier cours."],
    ];
    foreach ($ateliers as $i => [$at_titre, $at_description]) {
        if (seed_atelier_existe((int)$snizhana['id'], $at_titre)) {
            $rapport[] = "⏭️ Atelier déjà présent, ignoré : $at_titre";
        } else {
            seed_creer_atelier((int)$snizhana['id'], $at_titre, $at_description, $i);
            $rapport[] = "✅ Atelier créé (page de Snizhana) : $at_titre";
        }
    }
} else {
    $rapport[] = "⚠️ Ateliers de Snizhana NON créés — son profil est introuvable dans Intervenants.";
}

admin_header('Seed activités — septembre 2026', $user, 'activites');
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
  <strong>Pense à supprimer ce fichier (admin/seed-activites-2026-09.php) une fois vérifié.</strong>
</p>
<?php admin_footer(); ?>
