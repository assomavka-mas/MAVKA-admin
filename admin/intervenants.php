<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = auth_require(['super_admin', 'mavka_admin', 'partenaire']);

if (isset($_GET['delete']) && peut_editer($user)) {
    db()->prepare('DELETE FROM intervenants WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/intervenants.php');
    exit;
}

$intervenants = db()->query('SELECT * FROM intervenants ORDER BY nom')->fetchAll();

// Colonnes configurables : clé => [libellé, visible par défaut, type d'affichage].
// type : text (valeur brute) · fill (rempli ou non, un seul champ) · fill2 (rempli ou non, lien OU fichier) · bool · date
$colonnes = [
    'statut'              => ['Statut', true, 'statut'],
    'resume'               => ['Résumé', true, 'text'],
    'email'                => ['Email', true, 'text'],
    'actif'                => ['Actif', true, 'bool'],
    'domaine'              => ['Domaine', false, 'text'],
    'adresse'              => ['Adresse', false, 'text'],
    'bio'                  => ['Bio', false, 'fill'],
    'parcours_personnel'   => ['Parcours', false, 'fill'],
    'vision'                => ['Ma vision', false, 'fill'],
    'charte'                => ['Charte bénévolat', false, 'fill2'],
    'contrat'               => ["Contrat d'intervention", false, 'fill2'],
    'cv'                    => ['CV', false, 'fill2'],
    'rib'                   => ['RIB', false, 'fill2'],
    'assurance'             => ['Assurance', false, 'fill2'],
    'projet_developpement'  => ['Projet développement', false, 'fill'],
    'objectifs_mavka'       => ['Objectifs MAVKA', false, 'fill'],
    'created_at'            => ['Créé le', false, 'date'],
];
// champs (lien, fichier) derrière chaque colonne "fill2"
$champs_fill2 = [
    'charte'    => ['charte_benevolat_lien', 'charte_benevolat_fichier'],
    'contrat'   => ['contrat_intervention_lien', 'contrat_intervention_fichier'],
    'cv'        => ['cv_lien', 'cv_fichier'],
    'rib'       => ['rib_lien', 'rib_fichier'],
    'assurance' => ['assurance_lien', 'assurance_fichier'],
];
// champs éditables directement dans le tableau (liste blanche identique côté serveur, cf. intervenant-inline-update.php)
$champs_editables = ['resume' => true, 'adresse' => true, 'email' => true, 'actif' => true];

function iv_est_rempli(array $iv, string $col, array $champsFill2): bool {
    if (isset($champsFill2[$col])) {
        [$lien, $fichier] = $champsFill2[$col];
        return !empty($iv[$lien]) || !empty($iv[$fichier]);
    }
    return trim((string)($iv[$col] ?? '')) !== '';
}

// Aperçu d'un champ texte (Bio, Parcours...) : un extrait du vrai contenu plutôt qu'une coche,
// pour repérer les manques ET relire le fond sans ouvrir la fiche complète.
function iv_apercu_texte(string $texte, int $max = 70): string {
    $texte = trim(preg_replace('/\s+/', ' ', $texte));
    if ($texte === '') return '<span class="mavka-fill-no">—</span>';
    $court = mb_strlen($texte) > $max ? mb_substr($texte, 0, $max) . '…' : $texte;
    return '<span class="mavka-fill-yes" title="' . htmlspecialchars($texte) . '">' . htmlspecialchars($court) . '</span>';
}

// Aperçu d'un document (lien Drive ou fichier téléversé) : miniature pour une image, badge pour
// un PDF, lien pour un Drive — cliquable directement, sans passer par la fiche complète.
function iv_apercu_document(array $iv, string $lienChamp, string $fichierChamp): string {
    $fichier = $iv[$fichierChamp] ?? '';
    if ($fichier && $iv['dossier']) {
        $url = '/assets/uploads/intervenants/' . rawurlencode($iv['dossier']) . '/' . rawurlencode($fichier);
        $ext = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return '<a href="' . htmlspecialchars($url) . '" target="_blank" title="Voir le fichier">'
                . '<img src="' . htmlspecialchars($url) . '" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;display:block;">'
                . '</a>';
        }
        return '<a class="mavka-fill-yes" href="' . htmlspecialchars($url) . '" target="_blank">📄 ' . htmlspecialchars(strtoupper($ext ?: 'Fichier')) . '</a>';
    }
    $lien = $iv[$lienChamp] ?? '';
    if ($lien) {
        return '<a class="mavka-fill-yes" href="' . htmlspecialchars($lien) . '" target="_blank">🔗 Lien</a>';
    }
    return '<span class="mavka-fill-no">—</span>';
}

function iv_sort_value(array $iv, string $col, array $champsFill2) {
    if ($col === 'statut') return implode(' ', intervenant_statuts($iv));
    if ($col === 'actif') return (int)$iv['actif'];
    if (in_array($col, ['bio', 'parcours_personnel', 'vision', 'projet_developpement', 'objectifs_mavka'], true)
        || isset($champsFill2[$col])) {
        return iv_est_rempli($iv, $col, $champsFill2) ? 1 : 0;
    }
    return mb_strtolower((string)($iv[$col] ?? ''));
}

$tri = $_GET['sort'] ?? '';
$sens = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
if ($tri !== '' && ($tri === 'nom' || isset($colonnes[$tri]))) {
    usort($intervenants, function ($a, $b) use ($tri, $sens, $champs_fill2) {
        $va = $tri === 'nom' ? mb_strtolower($a['nom']) : iv_sort_value($a, $tri, $champs_fill2);
        $vb = $tri === 'nom' ? mb_strtolower($b['nom']) : iv_sort_value($b, $tri, $champs_fill2);
        $cmp = $va <=> $vb;
        return $sens === 'desc' ? -$cmp : $cmp;
    });
}

function tri_lien(string $col, string $libelle, string $triActuel, string $sensActuel): string {
    $prochainSens = ($triActuel === $col && $sensActuel === 'asc') ? 'desc' : 'asc';
    $fleche = $triActuel === $col ? ($sensActuel === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="?sort=' . urlencode($col) . '&dir=' . $prochainSens . '">' . htmlspecialchars($libelle) . $fleche . '</a>';
}

admin_header('Intervenants', $user, 'intervenants');
?>
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
  <h1>Intervenants / bénévoles</h1>
  <div style="display:flex; gap:10px; align-items:center;">
    <div class="mavka-colpicker-wrap">
      <button type="button" id="colPickerBtn" class="mavka-btn mavka-btn--sm">⚙ Colonnes</button>
      <div class="mavka-colpicker" id="colPicker" hidden>
        <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type]): ?>
        <label><input type="checkbox" data-col-toggle="<?= htmlspecialchars($cle) ?>" <?= $defaut ? 'checked' : '' ?>> <?= htmlspecialchars($libelle) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if (peut_editer($user)): ?>
    <a href="/admin/intervenant-form.php" class="mavka-btn mavka-btn--primary">+ Nouvel intervenant</a>
    <?php endif; ?>
  </div>
</div>
<?php if (isset($_GET['ok'])): ?><?php flash('ok', 'Enregistré avec succès.'); ?><?php endif; ?>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 0;">Clique un titre de colonne pour trier · clique une cellule (Résumé, Adresse, Email, Actif) pour la corriger directement ici.</p>

<table class="mavka-table" style="margin-top:12px;">
  <tr>
    <th></th>
    <th><?= tri_lien('nom', 'Nom', $tri, $sens) ?></th>
    <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type]): ?>
    <th data-col="<?= htmlspecialchars($cle) ?>"><?= tri_lien($cle, $libelle, $tri, $sens) ?></th>
    <?php endforeach; ?>
    <th></th>
  </tr>
  <?php foreach ($intervenants as $iv): ?>
  <tr>
    <td>
      <?php if ($iv['photo'] && $iv['dossier']): ?>
      <img src="/assets/uploads/intervenants/<?= htmlspecialchars($iv['dossier']) ?>/<?= htmlspecialchars($iv['photo']) ?>" alt="" style="width:36px; height:36px; border-radius:50%; object-fit:cover; display:block;">
      <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($iv['nom']) ?></td>
    <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type]): ?>
    <td data-col="<?= htmlspecialchars($cle) ?>">
      <?php if ($type === 'statut'): ?>
        <?= htmlspecialchars(implode(' + ', intervenant_statuts($iv))) ?>
      <?php elseif ($type === 'bool'): ?>
        <?php if (peut_editer($user)): ?>
        <input type="checkbox" data-editable-bool data-id="<?= $iv['id'] ?>" data-field="actif" <?= $iv['actif'] ? 'checked' : '' ?>>
        <?php else: ?>
        <?= $iv['actif'] ? '✓' : '—' ?>
        <?php endif; ?>
      <?php elseif ($type === 'fill'): ?>
        <?= iv_apercu_texte((string)($iv[$cle] ?? '')) ?>
      <?php elseif ($type === 'fill2'): ?>
        <?= iv_apercu_document($iv, ...$champs_fill2[$cle]) ?>
      <?php elseif ($type === 'date'): ?>
        <?= $iv[$cle] ? htmlspecialchars(date('d/m/Y', strtotime($iv[$cle]))) : '' ?>
      <?php elseif (isset($champs_editables[$cle]) && peut_editer($user)): ?>
        <span class="mavka-editable" data-editable data-id="<?= $iv['id'] ?>" data-field="<?= htmlspecialchars($cle) ?>"><?= htmlspecialchars($iv[$cle] ?? '') ?></span>
      <?php else: ?>
        <?= htmlspecialchars($iv[$cle] ?? '') ?>
      <?php endif; ?>
    </td>
    <?php endforeach; ?>
    <td style="white-space:nowrap;">
      <?php if (peut_editer($user)): ?>
      <a href="/admin/intervenant-form.php?id=<?= $iv['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/intervenants.php?delete=<?= $iv['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer ?');">Supprimer</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$intervenants): ?>
  <tr><td colspan="<?= count($colonnes) + 3 ?>" style="color:var(--mavka-color-text-muted);">Aucun intervenant pour l'instant.</td></tr>
  <?php endif; ?>
</table>

<script>
(function(){
  var STORAGE_KEY = 'mavka-intervenants-colonnes';
  var cases = document.querySelectorAll('[data-col-toggle]');
  function colonnesVisibles(){
    var v = [];
    cases.forEach(function(c){ if (c.checked) v.push(c.dataset.colToggle); });
    return v;
  }
  function appliquer(){
    var visibles = colonnesVisibles();
    document.querySelectorAll('[data-col]').forEach(function(el){
      el.hidden = visibles.indexOf(el.dataset.col) === -1;
    });
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(visibles)); } catch(e){}
  }
  try {
    var sauvegarde = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
    if (sauvegarde) {
      cases.forEach(function(c){ c.checked = sauvegarde.indexOf(c.dataset.colToggle) !== -1; });
    }
  } catch(e){}
  appliquer();
  cases.forEach(function(c){ c.addEventListener('change', appliquer); });

  var btn = document.getElementById('colPickerBtn');
  var panel = document.getElementById('colPicker');
  btn.addEventListener('click', function(e){ e.stopPropagation(); panel.hidden = !panel.hidden; });
  document.addEventListener('click', function(e){
    if (!panel.hidden && !panel.contains(e.target) && e.target !== btn) panel.hidden = true;
  });

  // ---- édition rapide avec confirmation ----
  function envoyer(id, field, value, onOk, onErr){
    fetch('/admin/intervenant-inline-update.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({id: id, field: field, value: value})
    }).then(function(r){ return r.json(); }).then(function(data){
      if (data.ok) onOk(data.value); else onErr(data.error || 'Erreur');
    }).catch(function(){ onErr('Impossible de contacter le serveur.'); });
  }

  document.querySelectorAll('[data-editable]').forEach(function(span){
    span.addEventListener('click', function(){
      if (span.querySelector('input')) return;
      var original = span.textContent;
      var input = document.createElement('input');
      input.type = span.dataset.field === 'email' ? 'email' : 'text';
      input.value = original;
      span.textContent = '';
      span.appendChild(input);
      input.focus();
      input.select();

      function fermer(nouvelleValeur){ span.textContent = nouvelleValeur; }
      function tenterEnregistrer(){
        var val = input.value;
        if (val === original) { fermer(original); return; }
        if (!confirm('Enregistrer « ' + (val || '(vide)') + ' » ?')) { fermer(original); return; }
        envoyer(span.dataset.id, span.dataset.field, val, function(saved){
          fermer(saved);
        }, function(err){
          alert(err);
          fermer(original);
        });
      }
      input.addEventListener('keydown', function(e){
        if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        if (e.key === 'Escape') { input.value = original; input.blur(); }
      });
      input.addEventListener('blur', tenterEnregistrer);
    });
  });

  document.querySelectorAll('[data-editable-bool]').forEach(function(box){
    box.addEventListener('change', function(){
      var nouvelleValeur = box.checked;
      if (!confirm((nouvelleValeur ? 'Marquer actif ?' : 'Marquer inactif ?'))) {
        box.checked = !nouvelleValeur;
        return;
      }
      envoyer(box.dataset.id, box.dataset.field, nouvelleValeur ? '1' : '0', function(){}, function(err){
        alert(err);
        box.checked = !nouvelleValeur;
      });
    });
  });
})();
</script>
<?php admin_footer(); ?>
