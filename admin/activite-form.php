<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$a = [
    'titre' => '', 'categorie' => 'Culture', 'categorie_display' => '',
    'format' => '', 'public' => '', 'description' => '',
    'date_debut' => '', 'heure' => '', 'recurrence' => '',
    'lieu' => '', 'nombre_places' => '', 'ville' => '', 'texte_bouton' => 'Préinscription gratuite',
    'lien_inscription' => '', 'photo' => null, 'statut' => 'publie', 'statut_activite' => 'ouvert', 'ordre' => 0,
];
$selected_intervenants = [];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM activites WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Activité introuvable.'); }
    $a = $found;
    $stmt = db()->prepare('SELECT intervenant_id FROM activite_intervenant WHERE activite_id = ?');
    $stmt->execute([$id]);
    $selected_intervenants = array_column($stmt->fetchAll(), 'intervenant_id');
}

$intervenants = db()->query('SELECT * FROM intervenants WHERE actif = 1 ORDER BY nom')->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a['titre'] = trim($_POST['titre'] ?? '');
    $a['categorie'] = trim($_POST['categorie'] ?? '');
    $a['categorie_display'] = trim($_POST['categorie_display'] ?? '');
    $a['format'] = trim($_POST['format'] ?? '') ?: null;
    $a['public'] = trim($_POST['public'] ?? '') ?: null;
    $a['description'] = trim($_POST['description'] ?? '');
    $a['date_debut'] = $_POST['date_debut'] ?: null;
    $a['heure'] = trim($_POST['heure'] ?? '');
    $a['lieu'] = trim($_POST['lieu'] ?? '');
    $a['nombre_places'] = $_POST['nombre_places'] !== '' ? (int)$_POST['nombre_places'] : null;
    $a['ville'] = trim($_POST['ville'] ?? '');
    $a['texte_bouton'] = trim($_POST['texte_bouton'] ?? '') ?: 'Préinscription gratuite';
    // La récurrence n'a de sens que pour un "Événement régulier" — pour tout autre
    // texte de bouton le champ est masqué côté formulaire, donc on ignore aussi
    // toute valeur envoyée pour ne pas garder une récurrence fantôme en base.
    $a['recurrence'] = $a['texte_bouton'] === 'Événement régulier' ? trim($_POST['recurrence'] ?? '') : '';
    $a['statut'] = $_POST['statut'] === 'brouillon' ? 'brouillon' : 'publie';
    $a['statut_activite'] = in_array($_POST['statut_activite'] ?? '', ['ouvert', 'complet', 'annule', 'termine'])
        ? $_POST['statut_activite'] : 'ouvert';
    $a['ordre'] = (int)($_POST['ordre'] ?? 0);
    $posted_intervenants = array_map('intval', $_POST['intervenants'] ?? []);

    // "Voir sa page" renvoie vers la page "Notre équipe" du·de la première personne cochée
    // ci-dessous, plutôt qu'un lien saisi à la main — pas encore de lien pour "Événement régulier"
    // ou "Gratuit" par exemple, seul ce bouton a besoin d'une page volontaire précise.
    if ($a['texte_bouton'] === 'Voir sa page') {
        $premier_intervenant = null;
        foreach ($intervenants as $iv) {
            if (in_array($iv['id'], $posted_intervenants, true)) { $premier_intervenant = $iv; break; }
        }
        $a['lien_inscription'] = $premier_intervenant ? intervenant_page_url((int)$premier_intervenant['id']) : '';
    } else {
        $a['lien_inscription'] = trim($_POST['lien_inscription'] ?? '');
    }

    $new_photo = handle_upload('photo', 'activites');
    if ($new_photo) {
        $a['photo'] = $new_photo;
    }

    if ($a['titre'] === '') {
        $error = 'Le titre est obligatoire.';
    } else {
        if ($id) {
            $stmt = db()->prepare('UPDATE activites SET titre=?, categorie=?, categorie_display=?, format=?, public=?, description=?, date_debut=?, heure=?, recurrence=?, lieu=?, nombre_places=?, ville=?, texte_bouton=?, lien_inscription=?, photo=?, statut=?, statut_activite=?, ordre=? WHERE id=?');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['ordre'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO activites (titre, categorie, categorie_display, format, public, description, date_debut, heure, recurrence, lieu, nombre_places, ville, texte_bouton, lien_inscription, photo, statut, statut_activite, ordre) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$a['titre'], $a['categorie'], $a['categorie_display'], $a['format'], $a['public'], $a['description'], $a['date_debut'], $a['heure'], $a['recurrence'], $a['lieu'], $a['nombre_places'], $a['ville'], $a['texte_bouton'], $a['lien_inscription'], $a['photo'], $a['statut'], $a['statut_activite'], $a['ordre']]);
            $id = (int)db()->lastInsertId();
        }

        db()->prepare('DELETE FROM activite_intervenant WHERE activite_id = ?')->execute([$id]);
        $ins = db()->prepare('INSERT INTO activite_intervenant (activite_id, intervenant_id) VALUES (?, ?)');
        foreach ($posted_intervenants as $iid) {
            $ins->execute([$id, $iid]);
        }

        header('Location: /admin/activites.php?ok=1');
        exit;
    }
}

admin_header($id ? 'Modifier l\'activité' : 'Nouvelle activité', $user, 'activites');
?>
<h1><?= $id ? "Modifier l'activité" : 'Nouvelle activité' ?></h1>
<?php if ($error): ?><?php flash('err', $error); ?><?php endif; ?>

<div class="mavka-activite-layout">
<form method="post" enctype="multipart/form-data" class="mavka-form" style="display:contents;">

  <div class="mavka-activite-col mavka-activite-col--card">
  <aside class="mavka-activite-preview">
    <div class="mavka-activite-preview__label">Carte publique — modifiable directement ici</div>

    <div class="ap-tags" title="Info interne — n'apparaît pas sur la carte, seulement ici pour vérifier d'un coup d'œil">
      <span id="pv_badge_categorie" class="ap-tag"></span>
      <span id="pv_badge_format" class="ap-tag"></span>
      <span id="pv_badge_public" class="ap-tag"></span>
      <span id="pv_badge_places" class="ap-tag"></span>
    </div>

    <div class="ap-event">
      <label for="f_photo" class="ap-cover">
        <img id="pv_photo" class="ap-cover__img" alt=""
             <?= !empty($a['photo']) ? 'src="/assets/uploads/activites/' . htmlspecialchars($a['photo']) . '"' : 'hidden' ?>>
        <div id="pv_date_badge" class="ap-date"></div>
        <span class="ap-cover__pencil" title="Changer la photo">✎</span>
      </label>
      <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp" hidden>

      <div class="ap-event-row">
        <textarea id="f_titre" name="titre" rows="1" class="ap-titre" placeholder="Titre de l'activité" required><?= htmlspecialchars($a['titre']) ?></textarea>

        <span class="ap-meta">
          <img id="pv_meta_avatar" class="ap-meta__avatar" alt="" hidden>
          <span id="pv_meta_intervenants"></span>
          <span id="pv_meta_sep" hidden>·</span>
          <span class="ap-meta__loc">
            <input type="text" id="f_lieu" name="lieu" class="ap-meta__input" placeholder="Lieu" value="<?= htmlspecialchars($a['lieu']) ?>">,
            <input type="text" id="f_ville" name="ville" class="ap-meta__input" placeholder="Ville" value="<?= htmlspecialchars($a['ville']) ?>">
          </span>
        </span>

        <div class="ap-date-inputs">
          <span>📅</span>
          <input type="date" id="f_date_debut" name="date_debut" value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>">
          <input type="text" id="f_heure" name="heure" placeholder="18:00" value="<?= htmlspecialchars($a['heure']) ?>">
          <input type="text" id="f_recurrence" name="recurrence" placeholder='Récurrence, ex. "Le jeudi"' value="<?= htmlspecialchars($a['recurrence']) ?>" <?= $a['texte_bouton'] === 'Événement régulier' ? '' : 'hidden' ?>>
        </div>

        <textarea id="f_description" name="description" class="ap-desc" placeholder="Description de l'activité"><?= htmlspecialchars($a['description']) ?></textarea>

        <?php
          $boutons = ['Préinscription gratuite', 'Préinscription', 'Gratuit', 'Événement régulier', 'En savoir plus', 'Voir sa page'];
          if ($a['texte_bouton'] && !in_array($a['texte_bouton'], $boutons)) {
              array_unshift($boutons, $a['texte_bouton']); // garde l'ancienne valeur personnalisée si elle ne fait pas partie de la liste
          }
        ?>
        <label class="mavka-activite-preview__btn-label">Texte du bouton<sup class="mavka-footnote-ref">2</sup></label>
        <select id="f_texte_bouton" name="texte_bouton" class="ap-btn ap-btn--primary">
          <?php foreach ($boutons as $b): ?>
          <option value="<?= htmlspecialchars($b) ?>" <?= $a['texte_bouton'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </aside>

  <div class="mavka-activite-actions">
    <button type="submit" class="mavka-btn mavka-btn--primary">Enregistrer</button>
    <a href="/admin/activites.php" class="mavka-btn">Annuler</a>
  </div>
  </div>

  <div class="mavka-activite-col mavka-activite-col--details">
  <details class="mavka-form-section mavka-form-section--parametres" open>
    <summary class="mavka-form-section__header" style="cursor:pointer;">
      <h3 class="mavka-form-section__title">⚙️ Détails supplémentaires</h3>
      <svg class="mavka-form-section__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="mavka-form-section__body">
    <p class="mavka-form-section__hint">Classement, capacité, inscription — n'apparaissent pas dans la carte ci-contre, modifiable directement.</p>

    <div class="row">
      <div>
        <label>Catégorie</label>
        <select id="f_categorie" name="categorie">
          <?php foreach (['Culture', 'Éducation', 'Bien-être', 'Développement personnel'] as $cat): ?>
          <option value="<?= $cat ?>" <?= $a['categorie'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Sous-titre affiché (facultatif)</label>
        <input type="text" id="f_categorie_display" name="categorie_display" placeholder="Bien-être · Art-thérapie" value="<?= htmlspecialchars($a['categorie_display'] ?? '') ?>">
      </div>
    </div>

    <div class="row">
      <div>
        <label>Type d'activité</label>
        <select id="f_format" name="format">
          <option value="">—</option>
          <?php foreach (['Collectif', 'Individuel', 'Événementiel'] as $f): ?>
          <option value="<?= $f ?>" <?= $a['format'] === $f ? 'selected' : '' ?>><?= $f ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Public</label>
        <select id="f_public" name="public">
          <option value="">—</option>
          <?php foreach (['Enfant', 'Familial', 'Adultes'] as $p): ?>
          <option value="<?= $p ?>" <?= $a['public'] === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Statut de l'activité</label>
        <select name="statut_activite">
          <?php $statuts = ['ouvert' => 'Ouvert', 'complet' => 'Complet', 'annule' => 'Annulé', 'termine' => 'Terminé']; ?>
          <?php foreach ($statuts as $val => $label): ?>
          <option value="<?= $val ?>" <?= $a['statut_activite'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Statut de publication</label>
        <select name="statut">
          <option value="publie" <?= $a['statut'] === 'publie' ? 'selected' : '' ?>>Publié</option>
          <option value="brouillon" <?= $a['statut'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
        </select>
      </div>
    </div>

    <div class="row mavka-row--nombres">
      <div>
        <label>Nombre de places <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(vide = illimité)</span></label>
        <input type="number" min="0" id="f_nombre_places" class="mavka-input-court" name="nombre_places" value="<?= htmlspecialchars((string)($a['nombre_places'] ?? '')) ?>">
      </div>
      <div>
        <label>Ordre d'affichage <span class="mavka-form-section__hint" style="margin:0; font-weight:400;">(0 = premier)</span><sup class="mavka-footnote-ref">1</sup></label>
        <input type="number" class="mavka-input-court" name="ordre" value="<?= (int)$a['ordre'] ?>">
      </div>
    </div>

    <div>
      <div id="f_lien_inscription_wrap">
        <label>Lien d'inscription</label>
        <input type="url" id="f_lien_inscription" name="lien_inscription" placeholder="https://helloasso.com/..." value="<?= htmlspecialchars($a['lien_inscription']) ?>">
      </div>
      <div id="f_lien_equipe_hint" class="mavka-form-section__hint" style="margin:14px 0 0; display:none;">
        <label>Lien d'inscription</label>
        Généré automatiquement — page "Notre équipe" de la première personne cochée ci-dessous.
      </div>
    </div>

    <label>Intervenant·e·s</label>
    <div class="mavka-picklist">
      <?php if (!$intervenants): ?>
      <div style="padding:10px 12px; color:var(--mavka-color-text-muted); font-size:13.5px;">
        Aucun intervenant enregistré — <a href="/admin/intervenants.php">en ajouter un</a>.
      </div>
      <?php endif; ?>
      <?php foreach ($intervenants as $iv):
        $iv_photo_url = ($iv['photo'] && $iv['dossier']) ? '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($iv['photo']) : '';
      ?>
      <label class="mavka-picklist__item">
        <input type="checkbox" name="intervenants[]" value="<?= $iv['id'] ?>" data-nom="<?= htmlspecialchars($iv['nom']) ?>" data-photo="<?= htmlspecialchars($iv_photo_url) ?>"
          <?= in_array($iv['id'], $selected_intervenants) ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($iv['nom']) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    </div>
  </details>

  <div class="mavka-activite-footnotes">
    <p><sup class="mavka-footnote-ref">1</sup> L'ordre détermine qui apparaît en premier sur le site (0, puis 1, 2...) ; à ordre égal, la date la plus proche passe devant. Aucun calcul automatique — c'est une priorité manuelle.</p>
    <p><sup class="mavka-footnote-ref">2</sup> Critère de choix du texte du bouton :</p>
    <ul>
      <li><strong>Préinscription gratuite</strong> — date libre, sert à constituer une liste pour la mairie (typiquement 2 séances test).</li>
      <li><strong>Préinscription</strong> — date fixée, places limitées : on compte les inscrits (payant ou gratuit selon la description/HelloAsso).</li>
      <li><strong>Gratuit</strong> — entrée libre, grandes salles / événements ouverts, pas de liste à tenir.</li>
      <li><strong>Événement régulier</strong> — pas de date unique (ex. "1er et 3e mercredi du mois") — voir Récurrence.</li>
      <li><strong>En savoir plus</strong> — partenaires / événements ouverts qui intéressent l'association.</li>
      <li><strong>Voir sa page</strong> — renvoie vers la page du volontaire/intervenant·e plutôt que vers une inscription.</li>
    </ul>
  </div>
  </div>

</form>
</div>

<style>
.mavka-activite-layout { display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
.mavka-input-court { max-width: 90px; }
.mavka-activite-col { display: flex; flex-direction: column; gap: 20px; }
.mavka-activite-col--card { width: 560px; flex-shrink: 0; }
.mavka-activite-col--details { flex: 1 1 380px; max-width: 640px; }
.mavka-activite-col--details .mavka-form-section { margin-bottom: 0; }
.mavka-activite-actions { display: flex; gap: 10px; }
.mavka-footnote-ref { color: var(--mavka-color-danger-text); font-weight: 700; margin-left: 1px; }
.mavka-activite-footnotes {
  font-size: 12.5px; color: var(--mavka-color-text-muted); line-height: 1.6;
  border-top: 1.5px solid var(--mavka-color-cream-soft); padding-top: 14px;
}
.mavka-activite-footnotes p { margin: 0 0 6px; }
.mavka-activite-footnotes ul { margin: 0; padding-left: 20px; }
.mavka-activite-footnotes li { margin-bottom: 3px; }
.mavka-activite-footnotes strong { color: var(--mavka-color-text); }
.mavka-activite-preview__btn-label {
  display: block; font-size: 12.5px; font-weight: 700; color: var(--mavka-color-text-muted); margin: 0 0 6px;
}
.mavka-form-section--parametres { --section-color: var(--mavka-color-purple-dark); }

/* La carte publique reprend, telle quelle, la palette et les classes réelles du site
   (assets/site.css) — au lieu d'un style "provisoire" maison qui a fini par diverger du
   vrai rendu public. Les tokens sont redéclarés ici, sous .mavka-activite-preview, pour ne
   pas mélanger le design system du site avec celui de l'admin (--mavka-color-*). */
@import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600&display=swap');
.mavka-activite-preview {
  width: 100%;
  --ap-ground: #fff; --ap-mint: #E6F6F0; --ap-card: #fff; --ap-line: #D9EFE8;
  --ap-ink: #1E2A3A; --ap-ink-2: #4B5A68; --ap-ink-3: #7C8994;
  --ap-teal: #1FAE93; --ap-teal-deep: #158A74;
  --ap-sun: #FFD84D; --ap-violet: #7B4FB5; --ap-violet-tint: #EBDFF7;
  --ap-r: 22px; --ap-display: "Bricolage Grotesque", var(--mavka-font-display), sans-serif; --ap-body: Figtree, var(--mavka-font-body), sans-serif;
}
.mavka-activite-preview__label { font-weight: 700; font-size: 13.5px; color: var(--mavka-color-text-muted); margin-bottom: 10px; }

/* Info interne (Catégorie/Format/Public/Places) : n'existe pas sur la vraie carte, donc plus
   question de la poser dessus comme avant — un simple bandeau de pastilles au-dessus, pour
   vérifier d'un coup d'œil en remplissant, sans faire croire que c'est ce que voit le public. */
.ap-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.ap-tag {
  display: inline-block; font-family: var(--ap-body); font-size: 11.5px; font-weight: 700;
  letter-spacing: .02em; padding: 4px 10px; border-radius: 999px;
  background: var(--ap-violet-tint); color: var(--ap-violet);
}
.ap-tag:empty { display: none; }

.ap-event {
  font-family: var(--ap-body); background: var(--ap-card); border: 2px solid var(--ap-mint);
  border-radius: var(--ap-r); overflow: hidden;
}
.ap-cover {
  display: block; cursor: pointer; position: relative; aspect-ratio: 16/9; background: var(--ap-mint); overflow: hidden;
}
.ap-cover__img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
.ap-cover__pencil {
  position: absolute; right: 10px; bottom: 10px; z-index: 2; width: 30px; height: 30px; border-radius: 50%;
  background: var(--ap-teal); color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 13px; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(30,42,58,.25);
}
.ap-cover:hover .ap-cover__pencil { background: var(--ap-teal-deep); }
.ap-date {
  position: absolute; left: 14px; bottom: 14px; z-index: 1;
  display: grid; align-content: start; gap: 2px; min-width: 74px; font-family: var(--ap-display);
  line-height: 1; text-align: center; padding: 9px 8px; border-radius: 14px; background: var(--ap-sun);
  color: var(--ap-ink); box-shadow: 0 4px 0 rgba(0,0,0,.08);
}
.ap-date:empty { display: none; }
.ap-date b { display: block; font-size: 1.4rem; font-weight: 800; }
.ap-date span { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
.ap-date small { display: block; font-size: .72rem; color: var(--ap-ink-2); margin-top: 3px; font-family: var(--ap-body); font-weight: 600; }
.ap-date.ap-date--weekly { background: var(--ap-violet-tint); color: var(--ap-violet); }
.ap-date.ap-date--weekly b { font-size: .85rem; font-weight: 700; }

.ap-event-row { display: grid; gap: 8px; align-content: start; padding: 22px; }
/* Spécificité : .mavka-form input/select/textarea (admin.css) est (0,1,1) — chaque règle
   ci-dessous est préfixée par .mavka-activite-preview pour monter à (0,2,0) et gagner,
   au lieu de se faire silencieusement écraser (bug déjà rencontré plusieurs fois sur cette page). */
.mavka-activite-preview .ap-titre {
  font-family: var(--ap-display); font-weight: 700; font-size: 1.15rem; line-height: 1.2;
  color: var(--ap-ink); margin: 0; padding: 2px 4px; border: none; border-radius: 8px; background: transparent; width: 100%;
  resize: none; overflow: hidden; min-height: 0;
}
.ap-meta {
  font-size: .9rem; color: var(--ap-ink-3); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.ap-meta__avatar { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; flex: none; background: var(--ap-mint); }
.ap-meta__loc { display: inline-flex; align-items: center; gap: 3px; }
.mavka-activite-preview .ap-meta__input {
  font: inherit; font-size: .9rem; color: var(--ap-ink-3); border: none; border-radius: 5px; background: transparent;
  padding: 2px 3px; width: 90px; min-width: 0;
}
.mavka-activite-preview .ap-meta__input:hover, .mavka-activite-preview .ap-meta__input:focus { background: var(--ap-mint); outline: none; }
.ap-date-inputs {
  display: flex; align-items: center; gap: 6px; padding: 6px 4px 2px; font-size: .85rem; color: var(--ap-ink-3);
}
.mavka-activite-preview .ap-date-inputs input {
  font: inherit; font-size: .85rem; border: none; border-radius: 5px; background: transparent; padding: 2px 4px; color: var(--ap-ink); width: auto;
}
.ap-date-inputs input[type="date"] { flex: 1.1; }
.ap-date-inputs input[type="text"] { width: 70px; }
.mavka-activite-preview .ap-date-inputs input:hover, .mavka-activite-preview .ap-date-inputs input:focus { background: var(--ap-mint); outline: none; }
.mavka-activite-preview .ap-desc {
  font-family: var(--ap-body); color: var(--ap-ink-2); font-size: .95rem; line-height: 1.5;
  border: none; border-radius: 8px; background: transparent; padding: 4px; margin: 4px 0 0; width: 100%;
  min-height: 130px; resize: vertical;
}
.mavka-activite-preview .ap-desc:hover, .mavka-activite-preview .ap-desc:focus,
.mavka-activite-preview .ap-titre:hover, .mavka-activite-preview .ap-titre:focus { background: var(--ap-mint); outline: none; }

.mavka-activite-preview .ap-btn {
  appearance: none; width: fit-content; margin-top: 6px; justify-self: start;
  display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 999px;
  font-family: var(--ap-body); font-weight: 700; font-size: .92rem; border: 1.5px solid transparent; cursor: pointer;
}
.mavka-activite-preview .ap-btn--primary { background: var(--ap-teal); color: #fff; }
.mavka-activite-preview .ap-btn--primary:hover { background: var(--ap-teal-deep); }
</style>

<script>
(function () {
  function $(id) { return document.getElementById(id); }

  // Le titre est un <textarea> (pas <input>) pour pouvoir s'afficher sur 2 lignes comme sur
  // la vraie carte publique — sa hauteur suit le texte tapé, et Entrée valide plutôt que
  // d'insérer un retour à la ligne (le titre reste une seule ligne logique, juste enroulée).
  var titreInput = $('f_titre');
  function autoGrowTitre() {
    titreInput.style.height = 'auto';
    titreInput.style.height = titreInput.scrollHeight + 'px';
  }
  titreInput.addEventListener('input', autoGrowTitre);
  titreInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') e.preventDefault();
  });
  autoGrowTitre();

  // Récurrence n'a de sens que pour le bouton "Événement régulier" — le champ
  // n'apparaît que dans ce cas, et n'est pas soumis (disabled) sinon pour ne
  // jamais laisser traîner une valeur qui ne correspond plus au bouton choisi.
  var recurrenceInput = $('f_recurrence');
  var boutonSelect = $('f_texte_bouton');

  function applyRecurrenceVisibility() {
    var estRegulier = boutonSelect.value === 'Événement régulier';
    recurrenceInput.hidden = !estRegulier;
    recurrenceInput.disabled = !estRegulier;
  }

  // "Voir sa page" calcule le lien côté serveur à partir de l'intervenant·e coché·e —
  // le champ manuel n'a plus de sens dans ce cas, on le remplace par une explication.
  var lienInput = $('f_lien_inscription');
  var lienWrap = $('f_lien_inscription_wrap');
  var lienHint = $('f_lien_equipe_hint');

  function applyLienInscriptionMode() {
    var versSaPage = boutonSelect.value === 'Voir sa page';
    lienWrap.hidden = versSaPage;
    lienInput.disabled = versSaPage;
    lienHint.style.display = versSaPage ? 'block' : 'none';
  }

  function applyBoutonMode() {
    applyRecurrenceVisibility();
    applyLienInscriptionMode();
  }
  boutonSelect.addEventListener('change', applyBoutonMode);
  applyBoutonMode();

  $('f_photo').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (ev) {
      $('pv_photo').src = ev.target.result;
      $('pv_photo').hidden = false;
    };
    reader.readAsDataURL(file);
  });

  // Plashka provisoire sur la photo — reprend le sous-titre affiché, sinon la catégorie.
  var badgeCategorie = $('pv_badge_categorie');
  function updateBadgeCategorie() {
    var sousTitre = $('f_categorie_display').value.trim();
    badgeCategorie.textContent = sousTitre || $('f_categorie').value;
  }
  $('f_categorie').addEventListener('change', updateBadgeCategorie);
  $('f_categorie_display').addEventListener('input', updateBadgeCategorie);
  updateBadgeCategorie();

  // Même principe pour Type d'activité, Public (sur la photo) et Nombre de places (dans
  // l'encadré date/lieu) — vide si le champ n'est pas rempli, la plashka disparaît d'elle-même.
  var badgeFormat = $('pv_badge_format');
  $('f_format').addEventListener('change', function () { badgeFormat.textContent = this.value; });
  badgeFormat.textContent = $('f_format').value;

  var badgePublic = $('pv_badge_public');
  $('f_public').addEventListener('change', function () { badgePublic.textContent = this.value; });
  badgePublic.textContent = $('f_public').value;

  var badgePlaces = $('pv_badge_places');
  function updateBadgePlaces() {
    var places = $('f_nombre_places').value.trim();
    badgePlaces.textContent = places ? places + ' places' : '';
  }
  $('f_nombre_places').addEventListener('input', updateBadgePlaces);
  updateBadgePlaces();

  // Plashka de date sur la photo : reproduit exactement render_event_date_badge() côté PHP
  // (includes/site_functions.php), pour que ce qu'on voit ici soit ce que voit le public.
  var MOIS_FR = {1:'jan',2:'fév',3:'mars',4:'avr',5:'mai',6:'juin',7:'juil',8:'août',9:'sept',10:'oct',11:'nov',12:'déc'};
  var dateBadge = $('pv_date_badge');
  var dateDebutInput = $('f_date_debut');
  var heureInput = $('f_heure');
  var villeInput = $('f_ville');
  function updateDateBadge() {
    var dateDebut = dateDebutInput.value;
    var heure = heureInput.value.trim();
    dateBadge.classList.remove('ap-date--weekly');
    if (dateDebut) {
      var parts = dateDebut.split('-');
      var jour = parts[2];
      var mois = MOIS_FR[parseInt(parts[1], 10)] || '';
      dateBadge.innerHTML = '<b>' + jour + '</b><span>' + mois + '</span>' + (heure ? '<small>' + heure + '</small>' : '');
    } else {
      var principal = recurrenceInput.value.trim() || 'Régulier';
      var secondaire = heure || villeInput.value.trim() || 'Sur demande';
      dateBadge.classList.add('ap-date--weekly');
      dateBadge.innerHTML = '<b>' + principal + '</b><small>' + secondaire + '</small>';
    }
  }
  dateDebutInput.addEventListener('input', updateDateBadge);
  heureInput.addEventListener('input', updateDateBadge);
  recurrenceInput.addEventListener('input', updateDateBadge);
  villeInput.addEventListener('input', updateDateBadge);
  boutonSelect.addEventListener('change', updateDateBadge);
  updateDateBadge();

  // Aperçu intervenant·e dans la ligne meta (avatar + nom) : reflète la première case cochée
  // dans la liste "Intervenant·e·s" ci-contre — c'est elle qui fait foi, pas un champ séparé.
  var metaAvatar = $('pv_meta_avatar');
  var metaNom = $('pv_meta_intervenants');
  var metaSep = $('pv_meta_sep');
  var casesIntervenants = document.querySelectorAll('input[name="intervenants[]"]');
  function updateMetaIntervenant() {
    var premiere = null;
    casesIntervenants.forEach(function (c) { if (!premiere && c.checked) premiere = c; });
    if (premiere) {
      metaNom.textContent = premiere.dataset.nom;
      metaSep.hidden = false;
      if (premiere.dataset.photo) {
        metaAvatar.src = premiere.dataset.photo;
        metaAvatar.hidden = false;
      } else {
        metaAvatar.hidden = true;
      }
    } else {
      metaNom.textContent = '';
      metaSep.hidden = true;
      metaAvatar.hidden = true;
    }
  }
  casesIntervenants.forEach(function (c) { c.addEventListener('change', updateMetaIntervenant); });
  updateMetaIntervenant();
})();
</script>
<?php admin_footer(); ?>
