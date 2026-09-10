<?php
// Fonctions du site public — activités publiées et équipe active, lues depuis la même
// base que l'admin (adresse le besoin : voir dans la vitrine ce qui a été saisi en admin).

function site_activites_a_venir(?int $limit = null): array {
    $sql = "SELECT * FROM activites_publiques
            WHERE statut_activite NOT IN ('annule','termine')
            ORDER BY (date_debut IS NULL) ASC, date_debut ASC, ordre ASC";
    if ($limit) {
        $sql .= ' LIMIT ' . (int)$limit;
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

function site_intervenants_actifs(): array {
    return db()->query('SELECT * FROM intervenants WHERE actif = 1 ORDER BY nom ASC')->fetchAll();
}

function site_intervenant_ateliers(int $intervenant_id): array {
    $stmt = db()->prepare('SELECT * FROM intervenant_ateliers WHERE intervenant_id = ? ORDER BY ordre ASC');
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

function render_event_date_badge(array $a): string {
    if (!empty($a['date_debut'])) {
        $ts = strtotime($a['date_debut']);
        $jour = date('d', $ts);
        $mois = SITE_MOIS_FR[(int)date('n', $ts)];
        $heure = htmlspecialchars($a['heure'] ?? '');
        return '<div class="date"><b>' . $jour . '</b><span>' . $mois . '</span>'
            . ($heure !== '' ? '<small>' . $heure . '</small>' : '') . '</div>';
    }
    $principal = htmlspecialchars($a['recurrence'] ?: 'Régulier');
    $secondaire = htmlspecialchars($a['heure'] ?: ($a['ville'] ?: 'Sur demande'));
    return '<div class="date weekly"><b>' . $principal . '</b><small>' . $secondaire . '</small></div>';
}

function render_event_card(array $a): string {
    $habillage = site_categorie_habillage($a['categorie']);
    if (!empty($a['photo'])) {
        $cover = '<div class="cover photo-cover"><img src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '" alt="">'
            . render_event_date_badge($a) . '</div>';
    } else {
        $cover = '<div class="cover ' . $habillage['cover'] . '">'
            . '<svg class="mascot" viewBox="' . $habillage['vb'] . '"><use href="#' . $habillage['mascot'] . '"/></svg>'
            . render_event_date_badge($a) . '</div>';
    }

    $avatar = !empty($a['intervenant_photo_url'])
        ? '<img class="event-avatar" src="' . htmlspecialchars($a['intervenant_photo_url']) . '" alt="">'
        : '';
    $lieuParts = array_filter([$a['intervenants_noms'] ?? null, trim(($a['lieu'] ?? '') . ($a['ville'] ? ', ' . $a['ville'] : ''), ', ')]);
    $meta = htmlspecialchars(implode(' · ', $lieuParts));
    $desc = htmlspecialchars($a['description'] ?? '');
    $btnLabel = htmlspecialchars($a['texte_bouton'] ?: 'En savoir plus');
    $btnHref = $a['lien_inscription'] ?: '#contact';
    $btnClass = in_array($a['texte_bouton'], ['En savoir plus', 'Voir sa page'], true) ? 'btn-ghost' : 'btn-primary';

    return '<div class="event">' . $cover . '<div class="event-row"><div class="event-body">'
        . '<h3>' . htmlspecialchars($a['titre']) . '</h3>'
        . ($meta !== '' ? '<span class="meta">' . $avatar . $meta . '</span>' : '')
        . ($desc !== '' ? '<p>' . $desc . '</p>' : '')
        . '<a class="btn ' . $btnClass . ' btn-sm" href="' . htmlspecialchars($btnHref) . '">' . $btnLabel . '</a>'
        . '</div></div></div>';
}

// Photo du·de la premier·ère intervenant·e lié·e à chaque activité (pour l'avatar dans la carte) —
// une requête séparée plutôt qu'une modification de la vue activites_publiques, pour rester
// une amélioration réversible sans migration de base.
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
    $parPremiere = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!isset($parPremiere[$row['activite_id']])) {
            $parPremiere[$row['activite_id']] = $row;
        }
    }
    return $parPremiere;
}

// Ajoute 'intervenant_photo_url' à chaque activité, à partir de site_activites_intervenant_photos().
function site_enrichir_avec_photo_intervenant(array $activites): array {
    $photos = site_activites_intervenant_photos(array_column($activites, 'id'));
    foreach ($activites as &$a) {
        $iv = $photos[$a['id']] ?? null;
        $a['intervenant_photo_url'] = ($iv && $iv['photo'] && $iv['dossier'])
            ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo'])
            : null;
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
    $domaine = htmlspecialchars(explode(',', (string)($iv['domaine'] ?? ''))[0] ?? '');
    $resume = htmlspecialchars($iv['resume'] ?? '');
    return '<div class="person">'
        . '<img src="' . htmlspecialchars($photoUrl) . '" alt="' . htmlspecialchars($iv['nom']) . '">'
        . '<b>' . htmlspecialchars($iv['nom']) . '</b>'
        . ($resume !== '' ? '<span>' . $resume . '</span>' : '')
        . ($domaine !== '' ? '<span class="meta">' . $domaine . '</span>' : '')
        . '<a href="' . htmlspecialchars(intervenant_page_url((int)$iv['id'])) . '">Voir sa page →</a>'
        . '</div>';
}
