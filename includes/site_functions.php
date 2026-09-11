<?php
// Fonctions du site public — activités publiées et équipe active, lues depuis la même
// base que l'admin (adresse le besoin : voir dans la vitrine ce qui a été saisi en admin).

// $formats restreint aux formats donnés (ex. teaser de l'accueil : Collectif, pour ne pas y
// montrer les cours individuels) ; null = tous formats, comme sur la page Agenda.
function site_activites_a_venir(?int $limit = null, ?array $formats = null): array {
    $sql = "SELECT * FROM activites_publiques
            WHERE statut_activite NOT IN ('annule','termine')";
    $params = [];
    if ($formats) {
        $sql .= ' AND format IN (' . implode(',', array_fill(0, count($formats), '?')) . ')';
        $params = $formats;
    }
    $sql .= ' ORDER BY (date_debut IS NULL) ASC, date_debut ASC, ordre ASC';
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

function site_activites_par_categorie(string $categorie): array {
    $stmt = db()->prepare("SELECT * FROM activites_publiques
        WHERE statut_activite NOT IN ('annule','termine') AND categorie = ?
        ORDER BY (date_debut IS NULL) ASC, date_debut ASC, ordre ASC");
    $stmt->execute([$categorie]);
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
// signé = en cours d'accompagnement.
function site_intervenant_statut_label(array $iv): string {
    if (!empty($iv['contrat_intervention_lien']) || !empty($iv['contrat_intervention_fichier'])) return 'Intervenant·e';
    if (!empty($iv['charte_benevolat_lien']) || !empty($iv['charte_benevolat_fichier'])) return 'Bénévole';
    return 'Volontaire';
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

// Activités réelles et réservables d'un·e volontaire, affichées sur SA page — que ces
// activités apparaissent ou non dans la grille de l'accueil (activites_publiques ne
// garde que visible_accueil = 1, donc on interroge `activites` directement ici, sans
// passer par la vue). Sert par ex. à une proposition "à l'essai" pour sonder l'intérêt
// avant d'en parler à une mairie, montrée sur la page du·de la volontaire concerné·e
// sans encombrer l'accueil de plusieurs cartes par personne.
// texte_bouton = 'Voir sa page' exclu : c'est justement la carte-vitrine dont le seul rôle
// est de renvoyer vers CETTE page — l'y afficher aussi la rendrait auto-référentielle.
function site_activites_intervenant(int $intervenant_id): array {
    $stmt = db()->prepare("SELECT a.* FROM activites a
        JOIN activite_intervenant ai ON ai.activite_id = a.id
        WHERE ai.intervenant_id = ? AND a.statut = 'publie' AND a.statut_activite NOT IN ('annule','termine')
          AND a.texte_bouton != 'Voir sa page'
        ORDER BY (a.date_debut IS NULL) ASC, a.date_debut ASC, a.ordre ASC");
    $stmt->execute([$intervenant_id]);
    return $stmt->fetchAll();
}

// Habillage visuel par catégorie quand l'activité n'a pas de photo : couleur de fond
// et mascotte, choisies une fois pour toutes par catégorie (pas au hasard par carte).
function site_categorie_habillage(string $categorie): array {
    $map = [
        'Culture'                  => ['cover' => 'lilac', 'mascot' => 'm-magnify', 'vb' => '0 0 552 756'],
        'Éducation'                => ['cover' => 'sun',   'mascot' => 'm-read',    'vb' => '0 0 549 767'],
        'Bien-être'                => ['cover' => 'mint',  'mascot' => 'm-stand',   'vb' => '0 0 310 769'],
        'Développement personnel'  => ['cover' => 'lilac', 'mascot' => 'm-jump',    'vb' => '0 0 469 734'],
    ];
    return $map[$categorie] ?? ['cover' => 'mint', 'mascot' => 'm-wave', 'vb' => '0 0 626 722'];
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
        $principal = htmlspecialchars($a['recurrence'] ?: 'Régulier');
        $secondaire = htmlspecialchars($a['heure'] ?: '');
        $badge = '<div class="strip__badge strip__badge--wide">'
            . '<span class="strip__date strip__date--text">' . $principal . '</span>'
            . ($secondaire !== '' ? '<span class="strip__time">' . $secondaire . '</span>' : '')
            . '</div>';
    }
    $lieuVille = trim(($a['lieu'] ?? '') . ($a['ville'] ? ', ' . $a['ville'] : ''), ', ');
    $loc = $lieuVille !== '' ? '<div class="strip__loc">' . htmlspecialchars($lieuVille) . '</div>' : '<div class="strip__loc"></div>';
    return '<div class="strip">' . $badge . $loc . '</div>';
}

function render_event_card(array $a): string {
    $habillage = site_categorie_habillage($a['categorie']);
    // corner--tl : sous-titre affiché uniquement (ex. "Nouveau cours") — n'affiche plus la
    // catégorie (Culture/Éducation/Bien-être) en repli ; vide = pas de plashka (retiré définitivement).
    $corners = '<span class="corner corner--tl">' . htmlspecialchars($a['categorie_display'] ?? '') . '</span>'
        . '<span class="corner corner--tr">' . htmlspecialchars($a['format'] ?? '') . '</span>'
        . '<span class="corner corner--br">' . htmlspecialchars($a['public'] ?? '') . '</span>'
        . '<span class="corner corner--br2">' . ($a['nombre_places'] !== null && $a['nombre_places'] !== '' ? htmlspecialchars($a['nombre_places'] . ' places') : '') . '</span>';
    if (!empty($a['photo'])) {
        $cover = '<div class="cover photo-cover"><img src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '" alt="">' . $corners . '</div>';
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
    $stmt = db()->prepare("SELECT ai.activite_id, iv.photo, iv.dossier
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

// Ajoute 'intervenant_photo_urls' (tableau) à chaque activité, à partir de
// site_activites_intervenant_photos() — un avatar par intervenant·e qui a une photo.
function site_enrichir_avec_photo_intervenant(array $activites): array {
    $photos = site_activites_intervenant_photos(array_column($activites, 'id'));
    foreach ($activites as &$a) {
        $a['intervenant_photo_urls'] = [];
        foreach ($photos[$a['id']] ?? [] as $iv) {
            if ($iv['photo'] && $iv['dossier']) {
                $a['intervenant_photo_urls'][] = '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']);
            }
        }
    }
    unset($a);
    return $activites;
}

function render_events_grid(array $activites, string $extraClass = ''): string {
    if (!$activites) {
        return '<p class="lede">Aucune date pour le moment — revenez bientôt, ou <a href="#contact">écrivez-nous</a> pour être prévenu·e.</p>';
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
