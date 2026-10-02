<?php
// Fonctions du site public — activités publiées et équipe active, lues depuis la même
// base que l'admin (adresse le besoin : voir dans la vitrine ce qui a été saisi en admin).
require_once __DIR__ . '/auth.php';

// true si un·e bénévole/admin est connecté·e (n'importe quel rôle — voir includes/auth.php).
// Dans ce cas les pages publiques montrent TOUTES les activités via activites_toutes (brouillons
// compris, même sans lien d'inscription, même pas encore acceptées par tou·te·s les
// intervenant·e·s) — pour se projeter sur le site "fini" et donner envie de finir les démarches
// (Charte, acceptation des activités...). Un visiteur anonyme continue de voir exactement ce qui
// est prêt (activites_publiques), comme avant.
function site_previsualisation_active(): bool {
    return auth_user() !== null;
}

// Bandeau discret en haut des pages publiques, uniquement en mode aperçu — sans lui, une
// personne connectée pourrait croire que tout ce qu'elle voit est déjà visible du public.
function render_apercu_banner(): string {
    if (!site_previsualisation_active()) {
        return '';
    }
    return '<div class="apercu-banner"><div class="wrap apercu-banner__inner">'
        . '🔍 Mode aperçu — vous voyez aussi les brouillons et les activités pas encore finalisées. Le public ne les voit pas encore.'
        . '</div></div>';
}

// $formats_exclus retire les formats donnés (ex. teaser de l'accueil : Individuel, pour ne pas y
// montrer les cours individuels) ; null = tous formats, comme sur la page Agenda. Bug corrigé
// (sept. 2026) : filtrer par "format IN ('Collectif')" excluait aussi tout ce qui n'a pas de
// format renseigné (ex. un événement comme "Festival du jeu Garat") — pas seulement les cours
// individuels visés au départ. On exclut désormais explicitement, plutôt que de restreindre.
// $mis_en_avant_dabord (teaser de l'accueil uniquement) : les activités cochées "Mettre en avant
// sur l'accueil" passent en premier, les places restantes se comblent par date la plus proche —
// un seul ORDER BY suffit (mis_en_avant DESC place les 1 avant les 0), pas besoin d'une 2e requête.
function site_activites_a_venir(?int $limit = null, ?array $formats_exclus = null, bool $mis_en_avant_dabord = false): array {
    $vue = site_previsualisation_active() ? 'activites_toutes' : 'activites_publiques';
    $sql = "SELECT * FROM $vue
            WHERE statut_activite NOT IN ('annule','termine')";
    $params = [];
    if ($formats_exclus) {
        $sql .= ' AND (format IS NULL OR format NOT IN (' . implode(',', array_fill(0, count($formats_exclus), '?')) . '))';
        $params = $formats_exclus;
    }
    $sql .= $mis_en_avant_dabord
        ? ' ORDER BY mis_en_avant DESC, (date_debut IS NULL) ASC, date_debut ASC, ordre ASC'
        : ' ORDER BY (date_debut IS NULL) ASC, date_debut ASC, ordre ASC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int)$limit;
    }
    if ($params) {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    return db()->query($sql)->fetchAll();
}

// $formats_exclus : même principe que site_activites_a_venir() (ex. Individuel, réservé aux
// pages volontaire — voir site_activites_intervenant()).
function site_activites_par_categorie(string $categorie, ?array $formats_exclus = null): array {
    $vue = site_previsualisation_active() ? 'activites_toutes' : 'activites_publiques';
    $sql = "SELECT * FROM $vue
        WHERE statut_activite NOT IN ('annule','termine') AND categorie = ?";
    $params = [$categorie];
    if ($formats_exclus) {
        $sql .= ' AND (format IS NULL OR format NOT IN (' . implode(',', array_fill(0, count($formats_exclus), '?')) . '))';
        $params = array_merge($params, $formats_exclus);
    }
    $sql .= ' ORDER BY (date_debut IS NULL) ASC, date_debut ASC, ordre ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Ordre d'affichage (liste "L'équipe" et aperçus) : Larysa (présidente/Mavka-admin) d'abord,
// puis les intervenant·e·s qui animent réellement un atelier (activite_intervenant ou
// intervenant_ateliers), puis les bénévoles qui ont signé la Charte du bénévole, puis le
// reste — un seul classement, pas de sections séparées sur la page.
function site_intervenant_rang(array $iv, array $enseigne): int {
    if ($iv['email'] === 'mas.larysa@gmail.com' || stripos((string)($iv['role_titre'] ?? ''), 'président') !== false) return 0;
    if (isset($enseigne[$iv['id']])) return 1;
    if (!empty($iv['charte_benevolat_lien']) || !empty($iv['charte_benevolat_fichier'])) return 2;
    return 3;
}

// Étiquette affichée en haut de la page publique d'un·e intervenant·e (remplace le fixe
// "Membre de l'équipe") — selon les documents signés, pas selon ce qui est programmé :
// contrat d'intervention signé = collaboration rémunérée établie (dernière étape du
// Parcours MAVKA) ; charte du bénévole seule = engagement bénévole officiel ; rien encore
// signé = "Découverte", pas "Volontaire" — ce dernier terme désigne en France un statut
// juridique encadré (volontariat associatif, service civique...) avec contrat et agrément
// d'État, qu'une personne à ce stade (juste un profil, aucun engagement pris) n'a pas.
function site_intervenant_statut_label(array $iv): string {
    if (!empty($iv['contrat_intervention_lien']) || !empty($iv['contrat_intervention_fichier'])) return 'Intervenant·e';
    if (!empty($iv['charte_benevolat_lien']) || !empty($iv['charte_benevolat_fichier'])) return 'Bénévole';
    return 'Découverte';
}

function site_intervenants_actifs(): array {
    $intervenants = db()->query('SELECT * FROM intervenants WHERE actif = 1')->fetchAll();
    $enseigne = array_fill_keys(array_merge(
        db()->query('SELECT DISTINCT intervenant_id FROM intervenant_ateliers')->fetchAll(PDO::FETCH_COLUMN),
        db()->query('SELECT DISTINCT intervenant_id FROM activite_intervenant')->fetchAll(PDO::FETCH_COLUMN)
    ), true);
    usort($intervenants, fn($a, $b) =>
        site_intervenant_rang($a, $enseigne) <=> site_intervenant_rang($b, $enseigne) ?: strcmp($a['nom'], $b['nom']));
    return $intervenants;
}

function site_intervenant_ateliers(int $intervenant_id): array {
    $stmt = db()->prepare('SELECT * FROM intervenant_ateliers WHERE intervenant_id = ? ORDER BY ordre ASC');
    $stmt->execute([$intervenant_id]);
    return $stmt->fetchAll();
}

// Photos de la galerie publique (réalisations, atelier en images) — vide si rien n'a été
// ajouté, auquel cas le bloc "Galerie" n'apparaît pas du tout sur la page (voir intervenant.php).
function site_intervenant_galerie(int $intervenant_id): array {
    $stmt = db()->prepare('SELECT * FROM intervenant_galerie WHERE intervenant_id = ? ORDER BY ordre ASC, id ASC');
    $stmt->execute([$intervenant_id]);
    return $stmt->fetchAll();
}

// Activités réelles et réservables d'un·e volontaire, affichées sur SA page — que ces
// activités apparaissent ou non dans la grille de l'accueil (activites_publiques ne
// garde que visible_accueil = 1, donc on interroge `activites` directement ici, sans
// passer par la vue). Sert par ex. à une proposition "à l'essai" pour sonder l'intérêt
// avant d'en parler à une mairie, montrée sur la page du·de la volontaire concerné·e
// sans encombrer l'accueil de plusieurs cartes par personne.
// texte_bouton = 'Voir sa page' exclu : c'est justement la carte-vitrine dont le seul rôle
// est de renvoyer vers CETTE page — l'y afficher aussi la rendrait auto-référentielle.
// N'affiche que ce qui est déjà public "en vrai" (toutes les personnes liées ont accepté, pas
// seulement celle-ci) — sa propre page ne doit pas montrer une activité que ses co-intervenant·e·s
// n'ont pas encore acceptée. Sauf en mode aperçu (site_previsualisation_active()) : là, tout
// s'affiche, brouillons et non-acceptées comprises — voir activites_toutes.
function site_activites_intervenant(int $intervenant_id): array {
    $sql = "SELECT a.* FROM activites a
        JOIN activite_intervenant ai ON ai.activite_id = a.id
        WHERE ai.intervenant_id = ? AND a.statut_activite NOT IN ('annule','termine')
          AND a.texte_bouton != 'Voir sa page'";
    if (!site_previsualisation_active()) {
        $sql .= " AND a.statut = 'publie'
          AND a.lien_inscription IS NOT NULL AND a.lien_inscription != ''
          AND NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 0)";
    }
    $sql .= ' ORDER BY (a.date_debut IS NULL) ASC, a.date_debut ASC, a.ordre ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$intervenant_id]);
    return $stmt->fetchAll();
}

// Habillage visuel par catégorie quand l'activité n'a pas de photo : couleur de fond
// et mascotte, choisies une fois pour toutes par catégorie (pas au hasard par carte). Couleurs
// alignées sur SITE_CATEGORIE_AVATAR_FOND : jaune=Culture, violet=Éducation, vert clair=Bien-être.
function site_categorie_habillage(string $categorie): array {
    $map = [
        'Culture'                  => ['cover' => 'sun',   'mascot' => 'm-magnify', 'vb' => '0 0 552 756'],
        'Éducation'                => ['cover' => 'lilac', 'mascot' => 'm-read',    'vb' => '0 0 549 767'],
        'Bien-être'                => ['cover' => 'mint',  'mascot' => 'm-stand',   'vb' => '0 0 310 769'],
        'Initiatives'               => ['cover' => 'lilac', 'mascot' => 'm-jump',    'vb' => '0 0 555 846'],
    ];
    return $map[$categorie] ?? ['cover' => 'mint', 'mascot' => 'm-wave', 'vb' => '0 0 626 722'];
}

// Correspondance catégorie -> colonne avatar sur intervenants (voir admin/intervenant-form.php,
// $domaine_avatar_champs, et alter-champs-v23.sql).
const SITE_CATEGORIE_AVATAR_CHAMP = [
    'Culture'     => 'avatar_domaine_culture',
    'Éducation'   => 'avatar_domaine_education',
    'Bien-être'   => 'avatar_domaine_bien_etre',
    'Initiatives' => 'avatar_domaine_initiatives',
];

// Fond derrière l'avatar "par direction" (voir render_event_card()). Bien-être est en blanc,
// pas en vert clair comme prévu au départ : la plupart des sections où vivent ces cartes ont
// déjà un fond vert pâle (.sand, voir assets/site.css), donc un fond de carte vert s'y fondait
// et perdait tout contraste — seuls Culture (jaune) et Éducation (violet) tranchent sur ce vert.
const SITE_CATEGORIE_AVATAR_FOND = [
    'Culture'     => 'sun',
    'Éducation'   => 'lilac',
    'Bien-être'   => '',
    'Initiatives' => '',
];

// Avatar "par direction" du·de la 1er·ère intervenant·e lié·e à l'activité (par id, le plus
// simple — pas de règle de priorité si plusieurs), pour la catégorie de l'ACTIVITÉ elle-même.
// Utilisé comme image par défaut quand l'activité n'a pas de photo (render_event_card()) : si
// cette personne n'a pas d'avatar pour cette direction, on retombe sur la mascotte générique,
// pas de recherche plus loin parmi les autres intervenant·e·s lié·e·s.
function site_activite_avatar_defaut(int $activite_id, string $categorie): ?string {
    $champ = SITE_CATEGORIE_AVATAR_CHAMP[$categorie] ?? null;
    if (!$champ) {
        return null;
    }
    $stmt = db()->prepare("SELECT dossier, `$champ` AS avatar FROM activite_intervenant ai
        JOIN intervenants iv ON iv.id = ai.intervenant_id
        WHERE ai.activite_id = ? ORDER BY ai.intervenant_id ASC LIMIT 1");
    $stmt->execute([$activite_id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['avatar']) || empty($row['dossier'])) {
        return null;
    }
    return '/assets/uploads/intervenants/' . rawurlencode($row['dossier']) . '/' . rawurlencode($row['avatar']);
}

const SITE_MOIS_FR = [1=>'jan',2=>'fév',3=>'mars',4=>'avr',5=>'mai',6=>'juin',7=>'juil',8=>'août',9=>'sept',10=>'oct',11=>'nov',12=>'déc'];

// Bandeau jaune pâle (.strip, assets/event-card.css) : pavé carré jaune vif (date/heure ou
// récurrence), puis lieu/ville sur le pâle. Utilisé tel quel par le site ; l'admin réutilise
// les mêmes classes autour de champs modifiables.
function render_event_strip(array $a): string {
    if (!empty($a['date_debut'])) {
        $ts = strtotime($a['date_debut']);
        $jour = date('d', $ts);
        $mois = SITE_MOIS_FR[(int)date('n', $ts)];
        $heure = htmlspecialchars($a['heure'] ?? '');
        $badge = '<div class="strip__badge">'
            . '<span class="strip__date">' . $jour . '<span class="strip__date-unit">' . $mois . '</span></span>'
            . ($heure !== '' ? '<span class="strip__time">' . $heure . '</span>' : '')
            . '</div>';
    } else {
        // Pas de date : "Événement régulier" affiche son jour de récurrence (ou "Régulier" si pas
        // encore précisé). Toute autre carte sans date (ex. Préinscription gratuite — date libre,
        // liste pour la mairie) affiche "Dates à venir" : "Régulier" impliquerait à tort une
        // récurrence pour un événement qui n'a simplement pas encore de date fixée.
        // "Dates à venir" sur deux lignes de tailles différentes (même principe que jour/mois
        // ci-dessus, via .strip__date-unit) plutôt qu'une seule ligne large qui casse la forme
        // carrée du badge.
        $principal = $a['texte_bouton'] === 'Événement régulier'
            ? htmlspecialchars($a['recurrence'] ?: 'Régulier')
            : 'Dates<span class="strip__date-unit">à venir</span>';
        $secondaire = htmlspecialchars($a['heure'] ?: '');
        $badge = '<div class="strip__badge strip__badge--wide">'
            . '<span class="strip__date strip__date--text">' . $principal . '</span>'
            . ($secondaire !== '' ? '<span class="strip__time">' . $secondaire . '</span>' : '')
            . '</div>';
    }
    // La ville est saisie tantôt en minuscules, tantôt en majuscules selon la personne qui
    // remplit la fiche (Soyaux, GARAT...) — plutôt que d'uniformiser la saisie en admin, un span
    // dédié + text-transform:uppercase (event-card.css) l'affiche toujours en capitales, sans
    // toucher à la donnée telle qu'enregistrée ni au lieu (qui garde sa casse normale).
    $lieu = trim($a['lieu'] ?? '');
    $ville = trim($a['ville'] ?? '');
    $parts = array_filter([
        $lieu !== '' ? htmlspecialchars($lieu) : '',
        $ville !== '' ? '<span class="strip__ville">' . htmlspecialchars($ville) . '</span>' : '',
    ], fn($p) => $p !== '');
    $loc = '<div class="strip__loc">' . implode(', ', $parts) . '</div>';
    return '<div class="strip">' . $badge . $loc . '</div>';
}

function render_event_card(array $a): string {
    $habillage = site_categorie_habillage($a['categorie']);
    // corner--tl : sous-titre affiché uniquement (ex. "Nouveau cours") — n'affiche plus la
    // catégorie (Culture/Éducation/Bien-être) en repli ; vide = pas de plashka (retiré définitivement).
    // Collectif est désormais l'immense majorité des cartes publiques (Individuel réservé aux
    // pages volontaire, voir index.php) — l'étiquette n'apporte plus rien, seul Individuel reste
    // affiché là où les deux formats se côtoient encore (page volontaire).
    $formatLabel = $a['format'] === 'Collectif' ? '' : ($a['format'] ?? '');
    $corners = '<span class="corner corner--tl">' . htmlspecialchars($a['categorie_display'] ?? '') . '</span>'
        . '<span class="corner corner--tr">' . htmlspecialchars($formatLabel) . '</span>'
        . '<span class="corner corner--br">' . htmlspecialchars($a['public'] ?? '') . '</span>'
        . '<span class="corner corner--br2">' . ($a['nombre_places'] !== null && $a['nombre_places'] !== '' ? htmlspecialchars($a['nombre_places'] . ' places') : '') . '</span>';
    // Sans photo, on essaie d'abord l'avatar "par direction" du·de la 1er·ère intervenant·e
    // lié·e (posé une fois pour toutes dans son profil, voir admin/intervenant-form.php) — fond
    // teinté selon la direction (SITE_CATEGORIE_AVATAR_FOND, blanc pour Initiatives), image
    // entière visible par-dessus. Seulement si personne n'en a pour cette catégorie : mascotte
    // générique + fond pastel, comme avant.
    $avatarDefaut = null;
    if (empty($a['photo']) && !empty($a['id'])) {
        // '_avatar_defaut' précalculé en lot par site_enrichir_avec_photo_intervenant() (voir
        // plus haut) — sinon (page pas encore enrichie) une requête au cas par cas, comme avant.
        $avatarDefaut = array_key_exists('_avatar_defaut', $a)
            ? $a['_avatar_defaut']
            : site_activite_avatar_defaut((int)$a['id'], $a['categorie']);
    }
    if (!empty($a['photo'])) {
        // "Voir sa page" = carte-vitrine pointant vers la page d'un·e volontaire (photo = son
        // portrait, pas une photo d'activité) : fond teinté par catégorie comme les autres
        // cartes sans photo, plutôt que le blanc réservé aux vraies photos d'activité.
        $fondPhoto = $a['texte_bouton'] === 'Voir sa page' ? (SITE_CATEGORIE_AVATAR_FOND[$a['categorie']] ?? '') : '';
        $cover = '<div class="cover photo-cover' . ($fondPhoto ? ' ' . $fondPhoto : '') . '"><img src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '" alt="">' . $corners . '</div>';
    } elseif ($avatarDefaut) {
        $fondAvatar = SITE_CATEGORIE_AVATAR_FOND[$a['categorie']] ?? '';
        $cover = '<div class="cover photo-cover' . ($fondAvatar ? ' ' . $fondAvatar : '') . '"><img src="' . htmlspecialchars($avatarDefaut) . '" alt="">' . $corners . '</div>';
    } else {
        $cover = '<div class="cover ' . $habillage['cover'] . '">'
            . '<svg class="mascot" viewBox="' . $habillage['vb'] . '"><use href="#' . $habillage['mascot'] . '"/></svg>' . $corners . '</div>';
    }

    $avatars = '';
    foreach ($a['intervenant_photo_urls'] ?? [] as $url) {
        $avatars .= '<img class="event-avatar" src="' . htmlspecialchars($url) . '" alt="">';
    }
    $avatars = $avatars !== '' ? '<div class="event-avatars">' . $avatars . '</div>' : '';

    $desc = htmlspecialchars($a['description'] ?? '');
    $btnLabel = htmlspecialchars($a['texte_bouton'] ?: 'En savoir plus');
    $btnHref = $a['lien_inscription'] ?: '#contact';
    $btnClass = in_array($a['texte_bouton'], ['En savoir plus', 'Voir sa page'], true) ? 'btn-ghost' : 'btn-primary';

    return '<div class="event">' . $cover . render_event_strip($a) . '<div class="event-row"><div class="event-body">'
        . '<div class="event-title-row">' . $avatars . '<h3>' . htmlspecialchars($a['titre']) . '</h3></div>'
        . ($desc !== '' ? '<p>' . $desc . '</p>' : '')
        . '<a class="btn ' . $btnClass . ' btn-sm" href="' . htmlspecialchars($btnHref) . '">' . $btnLabel . '</a>'
        . '</div></div></div>';
}

// Photos de tou·te·s les intervenant·e·s lié·e·s à chaque activité (pour les avatars sur la
// carte) — une requête séparée plutôt qu'une modification de la vue activites_publiques, pour
// rester une amélioration réversible sans migration de base.
function site_activites_intervenant_photos(array $activiteIds): array {
    $activiteIds = array_values(array_unique(array_map('intval', $activiteIds)));
    if (!$activiteIds) return [];
    $placeholders = implode(',', array_fill(0, count($activiteIds), '?'));
    $stmt = db()->prepare("SELECT ai.activite_id, iv.photo, iv.dossier,
            iv.avatar_domaine_culture, iv.avatar_domaine_education, iv.avatar_domaine_bien_etre, iv.avatar_domaine_initiatives
        FROM activite_intervenant ai
        JOIN intervenants iv ON iv.id = ai.intervenant_id
        WHERE ai.activite_id IN ($placeholders)
        ORDER BY ai.activite_id, iv.id");
    $stmt->execute($activiteIds);
    $parActivite = [];
    foreach ($stmt->fetchAll() as $row) {
        $parActivite[$row['activite_id']][] = $row;
    }
    return $parActivite;
}

// Ajoute 'intervenant_photo_urls' (tableau) et '_avatar_defaut' à chaque activité, à partir
// d'UNE requête batch (site_activites_intervenant_photos()) plutôt qu'une requête par carte —
// render_event_card() appelait site_activite_avatar_defaut() séparément pour chaque activité
// sans photo, ce qui devenait lent à mesure que le nombre d'activités augmentait (une page
// Activités avec 30 cartes = 30 requêtes en plus). '_avatar_defaut' est absent (pas juste null)
// quand une activité n'est pas passée par ici (ex. mes-activites.php) : render_event_card()
// retombe alors sur l'ancienne requête au cas par cas, plutôt que de perdre l'avatar.
function site_enrichir_avec_photo_intervenant(array $activites): array {
    $photos = site_activites_intervenant_photos(array_column($activites, 'id'));
    foreach ($activites as &$a) {
        $a['intervenant_photo_urls'] = [];
        $premier = $photos[$a['id']][0] ?? null;
        foreach ($photos[$a['id']] ?? [] as $iv) {
            if ($iv['photo'] && $iv['dossier']) {
                $a['intervenant_photo_urls'][] = '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']);
            }
        }
        $champ = SITE_CATEGORIE_AVATAR_CHAMP[$a['categorie']] ?? null;
        $avatar = $champ && $premier ? ($premier[$champ] ?? null) : null;
        $a['_avatar_defaut'] = ($avatar && $premier['dossier'])
            ? '/assets/uploads/intervenants/' . rawurlencode($premier['dossier']) . '/' . rawurlencode($avatar)
            : null;
    }
    unset($a);
    return $activites;
}

function render_events_grid(array $activites, string $extraClass = ''): string {
    if (!$activites) {
        return '<p class="lede">Les premières activités sont en préparation. Découvrez les propositions et <a href="#contact">indiquez-nous</a> celles qui vous intéressent.</p>';
    }
    $html = '<div class="events ' . htmlspecialchars($extraClass) . '">';
    foreach ($activites as $a) {
        $html .= render_event_card($a);
    }
    return $html . '</div>';
}

function render_person_card(array $iv): string {
    $photoUrl = ($iv['photo'] && $iv['dossier'])
        ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo'])
        : '/assets/site-img/img-01-017fac3c9d.webp';
    $tags = '';
    foreach (array_filter(array_map('trim', explode(',', (string)($iv['domaine'] ?? '')))) as $d) {
        $tags .= '<span>' . htmlspecialchars($d) . '</span>';
    }
    $resume = htmlspecialchars($iv['resume'] ?? '');
    return '<a class="person" href="' . htmlspecialchars(intervenant_page_url((int)$iv['id'])) . '">'
        . '<div class="person-cover"><img src="' . htmlspecialchars($photoUrl) . '" alt=""></div>'
        . '<b>' . htmlspecialchars($iv['nom']) . ' <span class="arrow">→</span></b>'
        . ($tags !== '' ? '<div class="person-tags">' . $tags . '</div>' : '')
        . ($resume !== '' ? '<span>' . $resume . '</span>' : '')
        . '</a>';
}
