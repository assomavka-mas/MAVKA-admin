<?php
// Outil à usage unique : ouvrir cette page une fois (connectée en admin), vérifier le rapport,
// puis supprimer ce fichier — ce n'est pas une page d'administration permanente.
//
// Corrige une confusion des deux précédents scripts de seed (seed-activites-2026-09.php et
// seed-activites-2026-09-musiciennes.php) : ils avaient mis un contenu "atelier précis, avec
// ville et bouton Préinscription" dans intervenant_ateliers ("Ce que je propose") — une table
// qui n'a ni lien d'inscription ni bouton, donc ce contenu n'était jamais réellement réservable.
//
// Nouvelle répartition, décidée avec Larysa :
//   - "Ce que je propose" (intervenant_ateliers) redevient ce que son nom dit : les grands
//     domaines/savoir-faire généraux de la personne, informatif, sans bouton — ses "premiers
//     pas" dans ces directions, pas forcément programmés.
//   - Les 2 ateliers collectifs (Garat/Soyaux, pour sonder l'intérêt avant d'en parler aux
//     mairies) et le cours individuel deviennent de VRAIES Activités (table `activites`),
//     avec un vrai bouton de préinscription — mais avec visible_accueil=0 (nouveau champ,
//     alter-champs-v11.sql) pour ne pas encombrer l'accueil : elles n'apparaissent que sur la
//     page du·de la volontaire concerné·e (intervenant.php → site_activites_intervenant()).
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

function mig_trouver_intervenant(string $nom): ?array {
    $stmt = db()->prepare('SELECT id, nom FROM intervenants WHERE LOWER(nom) = LOWER(?) LIMIT 1');
    $stmt->execute([$nom]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mig_supprimer_atelier(int $intervenantId, string $titre): bool {
    $stmt = db()->prepare('DELETE FROM intervenant_ateliers WHERE intervenant_id = ? AND titre = ?');
    $stmt->execute([$intervenantId, $titre]);
    return $stmt->rowCount() > 0;
}

function mig_creer_atelier_si_absent(int $intervenantId, string $titre, string $description, int $ordre): bool {
    $stmt = db()->prepare('SELECT id FROM intervenant_ateliers WHERE intervenant_id = ? AND titre = ? LIMIT 1');
    $stmt->execute([$intervenantId, $titre]);
    if ($stmt->fetch()) return false;
    db()->prepare('INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (?,?,?,?)')
        ->execute([$intervenantId, $titre, $description, $ordre]);
    return true;
}

function mig_creer_activite_si_absente(array $a, int $intervenantId): bool {
    $stmt = db()->prepare('SELECT id FROM activites WHERE titre = ? LIMIT 1');
    $stmt->execute([$a['titre']]);
    if ($stmt->fetch()) return false;
    $stmt = db()->prepare('INSERT INTO activites
        (titre, categorie, categorie_display, format, public, description, recurrence, ville, texte_bouton, statut, statut_activite, visible_accueil, ordre)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $a['titre'], $a['categorie'], $a['categorie_display'] ?? null, $a['format'] ?? null, $a['public'] ?? null,
        $a['description'], $a['recurrence'], $a['ville'], $a['texte_bouton'],
        'publie', 'ouvert', 0, 0,
    ]);
    $id = (int)db()->lastInsertId();
    db()->prepare('INSERT INTO activite_intervenant (activite_id, intervenant_id) VALUES (?, ?)')->execute([$id, $intervenantId]);
    return true;
}

/**
 * @param string[] $ancienTitres Titres exacts des 3 anciens "ateliers" à retirer (contenu déplacé vers activites).
 * @param array<int,array{0:string,1:string}> $nouveauxAteliers [titre, description] du nouveau "Ce que je propose" général.
 * @param array<int,array> $nouvellesActivites Chacune : titre, categorie, categorie_display, format, public, description, recurrence, ville, texte_bouton.
 */
function mig_volontaire(string $nom, array $ancienTitres, array $nouveauxAteliers, array $nouvellesActivites, array &$rapport): void {
    $iv = mig_trouver_intervenant($nom);
    $rapport[] = $iv
        ? "✅ Intervenant·e trouvé·e : $nom (id={$iv['id']})"
        : "⚠️ Intervenant·e INTROUVABLE : $nom — rien ne sera fait pour elle.";
    if (!$iv) return;
    $ivId = (int)$iv['id'];

    foreach ($ancienTitres as $titre) {
        $rapport[] = mig_supprimer_atelier($ivId, $titre)
            ? "🗑️ Ancien atelier retiré (contenu déplacé vers Activités) : $titre"
            : "⏭️ Ancien atelier déjà absent : $titre";
    }

    foreach ($nouveauxAteliers as $i => [$titre, $description]) {
        $rapport[] = mig_creer_atelier_si_absent($ivId, $titre, $description, $i)
            ? "✅ « Ce que je propose » créé : $titre"
            : "⏭️ « Ce que je propose » déjà présent, ignoré : $titre";
    }

    foreach ($nouvellesActivites as $a) {
        $rapport[] = mig_creer_activite_si_absente($a, $ivId)
            ? "✅ Activité créée (visible seulement sur sa page) : {$a['titre']}"
            : "⏭️ Activité déjà présente, ignorée : {$a['titre']}";
    }
}

$rapport = [];

// ============================================================================
// Snizhana Zhuravlova
// ============================================================================
mig_volontaire(
    'Snizhana Zhuravlova',
    ['Atelier de musique d\'ensemble à Garat', 'Atelier de musique d\'ensemble à Soyaux', 'Cours individuels de musique'],
    [
        ['Pratique musicale en groupe',
            "Des ateliers permettant de découvrir la musique d'ensemble et d'apprendre à jouer avec les autres.\n"
            . "Selon les participants et leurs instruments, ces ateliers pourront évoluer vers la création de petits ensembles musicaux."],
        ['Éveil et formation musicale',
            "Une approche progressive pour développer l'écoute, le rythme, la compréhension de la musique et la créativité.\n"
            . "Les activités peuvent être adaptées à l'âge et au niveau des participants."],
        ['Chant & choral',
            "Un atelier collectif pour découvrir ou développer le plaisir de chanter ensemble.\n"
            . "La chorale permet de travailler la voix, l'écoute, le rythme et la musicalité, tout en développant la confiance en soi et le plaisir de faire partie d'un groupe.\n"
            . "Aucune expérience musicale particulière n'est nécessaire."],
        ['Cours individuels',
            "Des cours adaptés au niveau et aux objectifs de chacun autour notamment du violon, du piano et de la formation musicale.\n"
            . "L'accompagnement peut convenir aussi bien aux débutants qu'aux personnes souhaitant reprendre une pratique musicale après une interruption."],
    ],
    [
        [
            'titre' => "Atelier de musique d'ensemble à Garat", 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif',
            'description' => "Vous pratiquez déjà un instrument et souhaitez apprendre à jouer avec d'autres musiciens ?\n\n"
                . "MAVKA envisage la création d'un atelier de musique d'ensemble à Garat.\n\n"
                . "Ces ateliers permettront de découvrir le jeu collectif, d'apprendre à écouter les autres et à jouer ensemble. Selon les participants et les instruments présents, ils pourront évoluer vers la création de petits ensembles musicaux.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Garat', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => "Atelier de musique d'ensemble à Soyaux", 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif',
            'description' => "Vous pratiquez déjà un instrument et souhaitez apprendre à jouer avec d'autres musiciens ?\n\n"
                . "MAVKA envisage la création d'un atelier de musique d'ensemble à Soyaux.\n\n"
                . "Ces ateliers permettront de découvrir le jeu collectif, d'apprendre à écouter les autres et à jouer ensemble. Selon les participants et les instruments présents, ils pourront évoluer vers la création de petits ensembles musicaux.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Soyaux', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => 'Cours individuels de musique', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Individuel',
            'description' => "MAVKA propose des cours individuels de violon, piano et formation musicale, adaptés au niveau et aux objectifs de chacun.\n\n"
                . "Les cours s'adressent aux débutants ainsi qu'aux personnes souhaitant reprendre la musique après une interruption.\n\n"
                . "Les séances sont organisées chez l'élève, qui doit disposer de l'instrument nécessaire à son apprentissage.\n\n"
                . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante. La préinscription permet de préciser vos besoins, votre instrument et vos disponibilités avant l'organisation du premier cours.",
            'recurrence' => 'Sur rendez-vous', 'ville' => 'Grand Angoulême', 'texte_bouton' => 'Préinscription',
        ],
    ],
    $rapport
);

// ============================================================================
// Nataliia Veremeienko
// ============================================================================
mig_volontaire(
    'Nataliia Veremeienko',
    ['Découverte du saxophone et de la flûte à Garat', 'Éveil musical et rencontres interculturelles à Soyaux', 'Cours individuels de saxophone et de flûte'],
    [
        ['Découverte du saxophone et de la flûte',
            "Une première approche de ces instruments, ouverte aux débutants comme aux personnes ayant déjà une expérience musicale.\n"
            . "L'occasion de découvrir leur sonorité et de faire ses premiers pas, sans pression."],
        ['Éveil musical pour enfants',
            "Une approche progressive de la musique, pensée pour les plus jeunes.\n"
            . "Les activités peuvent être adaptées à l'âge et au niveau de chacun."],
        ['Pratique musicale collective',
            "Des temps de pratique en groupe pour apprendre à jouer ensemble, s'écouter et progresser à plusieurs."],
        ['Rencontres interculturelles autour de la musique',
            "La musique comme terrain d'échange : expression artistique et rencontres entre cultures, au fil des ateliers."],
    ],
    [
        [
            'titre' => 'Découverte du saxophone et de la flûte à Garat', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif',
            'description' => "Vous êtes curieux(se) de découvrir le saxophone ou la flûte, seul(e) ou en famille ?\n\n"
                . "MAVKA envisage la création d'un atelier de découverte musicale à Garat, ouvert aux débutants comme aux personnes ayant déjà une expérience.\n\n"
                . "Cet atelier collectif permettra une première approche des instruments, dans une ambiance conviviale et bienveillante, avec des temps de pratique en groupe.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Garat', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => 'Éveil musical et rencontres interculturelles à Soyaux', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif',
            'description' => "MAVKA envisage la création d'un atelier d'éveil musical à Soyaux, pensé pour les enfants comme pour les adultes.\n\n"
                . "Au programme : découverte d'instruments, expression artistique à travers la musique, et rencontres interculturelles autour de la culture musicale.\n\n"
                . "Cet atelier collectif est ouvert à tous les niveaux.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Soyaux', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => 'Cours individuels de saxophone et de flûte', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Individuel',
            'description' => "Nataliia Veremeienko propose des cours individuels de saxophone et de flûte, adaptés au niveau et aux objectifs de chacun.\n\n"
                . "Les cours s'adressent aux débutants ainsi qu'aux personnes souhaitant progresser ou reprendre la pratique d'un instrument.\n\n"
                . "Les séances sont organisées à Grand Angoulême, selon les disponibilités.\n\n"
                . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante.",
            'recurrence' => 'Sur rendez-vous', 'ville' => 'Grand Angoulême', 'texte_bouton' => 'Préinscription',
        ],
    ],
    $rapport
);

// ============================================================================
// Nadiia Denysenko
// ============================================================================
mig_volontaire(
    'Nadiia Denysenko',
    ['Découverte des instruments traditionnels (domra et mandoline) à Garat', 'Éveil musical et créativité pour enfants à Soyaux', 'Cours individuels de piano, guitare et chant'],
    [
        ['Découverte des instruments traditionnels',
            "La domra et la mandoline, deux instruments traditionnels peu connus en France : une invitation à la découverte, ouverte à tous les niveaux."],
        ['Piano et guitare pour les enfants',
            "Des premiers pas au piano (dès 6 ans) ou à la guitare (dès 6 ans), dans un cadre adapté à l'âge de chacun."],
        ['Chant moderne et variété',
            "Pour les débutants qui souhaitent découvrir ou développer leur voix, sur des répertoires actuels."],
        ['Éveil musical et créativité',
            "Une approche progressive pour développer l'écoute, le rythme et la créativité, adaptée à l'âge et au niveau de chacun."],
    ],
    [
        [
            'titre' => 'Découverte des instruments traditionnels (domra et mandoline) à Garat', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif',
            'description' => "Envie de découvrir des instruments traditionnels peu connus, la domra et la mandoline ?\n\n"
                . "MAVKA envisage la création d'un atelier de découverte musicale à Garat, ouvert aux débutants comme aux personnes déjà initiées à la musique.\n\n"
                . "Cet atelier collectif permettra une première approche de ces instruments, dans une ambiance conviviale, avec des temps de pratique en groupe.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Garat', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => 'Éveil musical et créativité pour enfants à Soyaux', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Collectif', 'public' => 'Enfant',
            'description' => "MAVKA envisage la création d'un atelier d'éveil musical à Soyaux, pensé pour les enfants (piano dès 6 ans, guitare dès 6 ans) qui souhaitent développer leur créativité à travers la musique.\n\n"
                . "Au programme : premiers pas au piano ou à la guitare, chant, et jeux musicaux en groupe.\n\n"
                . "Cet atelier collectif est adapté à l'âge et au niveau de chacun.\n\n"
                . "Préinscription gratuite et sans engagement.",
            'recurrence' => 'Selon la demande', 'ville' => 'Soyaux', 'texte_bouton' => 'Préinscription gratuite',
        ],
        [
            'titre' => 'Cours individuels de piano, guitare et chant', 'categorie' => 'Culture', 'categorie_display' => 'Culture · Musique', 'format' => 'Individuel',
            'description' => "Nadiia Denysenko propose des cours individuels de piano (6–14 ans), de guitare (débutants dès 6 ans) et de chant moderne / variété, adaptés à l'âge, au niveau et aux objectifs de chacun.\n\n"
                . "Les séances sont organisées à Grand Angoulême, selon les disponibilités.\n\n"
                . "Les inscriptions et les règlements sont effectués auprès de l'association MAVKA, qui organise les séances avec l'enseignante.",
            'recurrence' => 'Sur rendez-vous', 'ville' => 'Grand Angoulême', 'texte_bouton' => 'Préinscription',
        ],
    ],
    $rapport
);

admin_header('Migration ateliers → activités', $user, 'activites');
?>
<h1>Résultat</h1>
<div class="mavka-form-section__body" style="background:#fff; border-radius:12px; padding:20px 24px; max-width:760px;">
  <ul style="line-height:1.9; font-size:14.5px; margin:0; padding-left:20px;">
    <?php foreach ($rapport as $ligne): ?>
    <li><?= htmlspecialchars($ligne) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<p style="max-width:760px; margin-top:16px;">
  <a href="/admin/activites.php">→ Voir les activités</a> ·
  <strong>Pense à supprimer ce fichier (admin/migrate-ateliers-vers-activites-2026-09.php) une fois vérifié.</strong>
</p>
<?php admin_footer(); ?>
