<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = auth_require(['super_admin', 'mavka_admin', 'partenaire']);
$activites = db()->query('SELECT * FROM activites ORDER BY ordre ASC, date_debut ASC')->fetchAll();

// Colonnes configurables : clé => [libellé, visible par défaut, type d'affichage, options].
// type : text (lecture seule) · edit_text · edit_nombre · edit_date (date_debut) ·
//        select (édition rapide, pastille colorée — Statut/État) ·
//        select_plain (édition rapide, texte simple — Catégorie/Format/Public) ·
//        fill (aperçu d'un texte long) · photo · lien · date
$colonnes = [
    'categorie'          => ['Catégorie', true, 'select_plain', ['Culture' => 'Culture', 'Éducation' => 'Éducation', 'Bien-être' => 'Bien-être', 'Développement personnel' => 'Développement personnel']],
    'date'                => ['Date', true, 'edit_date', null],
    'lieu'                => ['Lieu', true, 'edit_text', null],
    'nombre_places'       => ['Places', true, 'edit_nombre', null],
    'statut'              => ['Statut', true, 'select', ['publie' => 'Publié', 'brouillon' => 'Brouillon']],
    'statut_activite'     => ['État', true, 'select', ['ouvert' => 'Ouvert', 'complet' => 'Complet', 'annule' => 'Annulé', 'termine' => 'Terminé']],
    'categorie_display'  => ['Sous-catégorie', false, 'text', null],
    'format'              => ['Format', false, 'select_plain', ['' => '—', 'Collectif' => 'Collectif', 'Individuel' => 'Individuel', 'Événementiel' => 'Événementiel']],
    'public'              => ['Public', false, 'select_plain', ['' => '—', 'Enfant' => 'Enfant', 'Familial' => 'Familial', 'Adultes' => 'Adultes']],
    'ville'               => ['Ville', false, 'edit_text', null],
    'heure'               => ['Heure', false, 'edit_text', null],
    'recurrence'          => ['Récurrence', false, 'edit_text', null],
    'texte_bouton'       => ['Texte du bouton', false, 'edit_text', null],
    'lien_inscription'   => ['Lien', false, 'lien', null],
    'description'         => ['Description', false, 'fill', null],
    'ordre'               => ['Ordre', false, 'edit_nombre', null],
    'created_at'          => ['Créé le', false, 'date', null],
    'updated_at'          => ['Modifié le', false, 'date', null],
];
$champs_editables = ['categorie', 'date_debut', 'lieu', 'ville', 'heure', 'recurrence', 'texte_bouton', 'format', 'public', 'nombre_places', 'ordre', 'statut', 'statut_activite'];

function act_apercu_texte(string $texte, int $max = 70): string {
    $texte = trim(preg_replace('/\s+/', ' ', $texte));
    if ($texte === '') return '<span class="mavka-fill-no">—</span>';
    $court = mb_strlen($texte) > $max ? mb_substr($texte, 0, $max) . '…' : $texte;
    return '<span class="mavka-fill-yes" title="' . htmlspecialchars($texte) . '">' . htmlspecialchars($court) . '</span>';
}

function act_apercu_photo(?string $photo): string {
    if (!$photo) return '<span class="mavka-fill-no">—</span>';
    $url = '/assets/uploads/activites/' . rawurlencode($photo);
    return '<a href="' . htmlspecialchars($url) . '" target="_blank" title="Voir la photo">'
        . '<img src="' . htmlspecialchars($url) . '" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;display:block;"></a>';
}

function act_apercu_lien(?string $lien): string {
    if (!$lien) return '<span class="mavka-fill-no">—</span>';
    return '<a class="mavka-fill-yes" href="' . htmlspecialchars($lien) . '" target="_blank">🔗 Lien</a>';
}

function act_sort_value(array $a, string $col) {
    if ($col === 'date') return $a['date_debut'] ?: '';
    if (in_array($col, ['nombre_places', 'ordre'], true)) return $a[$col] === null ? -1 : (int)$a[$col];
    if (in_array($col, ['description', 'lien_inscription'], true)) {
        return trim((string)($a[$col] ?? '')) !== '' ? 1 : 0;
    }
    return mb_strtolower((string)($a[$col] ?? ''));
}

$tri = $_GET['sort'] ?? '';
$sens = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
if ($tri !== '' && ($tri === 'titre' || isset($colonnes[$tri]))) {
    usort($activites, function ($a, $b) use ($tri, $sens) {
        $va = $tri === 'titre' ? mb_strtolower($a['titre']) : act_sort_value($a, $tri);
        $vb = $tri === 'titre' ? mb_strtolower($b['titre']) : act_sort_value($b, $tri);
        $cmp = $va <=> $vb;
        return $sens === 'desc' ? -$cmp : $cmp;
    });
}

function act_tri_lien(string $col, string $libelle, string $triActuel, string $sensActuel): string {
    $prochainSens = ($triActuel === $col && $sensActuel === 'asc') ? 'desc' : 'asc';
    $fleche = $triActuel === $col ? ($sensActuel === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="?sort=' . urlencode($col) . '&dir=' . $prochainSens . '">' . htmlspecialchars($libelle) . $fleche . '</a>';
}

admin_header('Activités', $user, 'activites');
?>
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
  <h1>Activités</h1>
  <div style="display:flex; gap:10px; align-items:center;">
    <div class="mavka-colpicker-wrap">
      <button type="button" id="colPickerBtn" class="mavka-btn mavka-btn--sm">⚙ Colonnes</button>
      <div class="mavka-colpicker" id="colPicker" hidden>
        <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type, $options]): ?>
        <label><input type="checkbox" data-col-toggle="<?= htmlspecialchars($cle) ?>" <?= $defaut ? 'checked' : '' ?>> <?= htmlspecialchars($libelle) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if (peut_editer($user)): ?>
    <a href="/admin/activite-form.php" class="mavka-btn mavka-btn--primary">+ Nouvelle activité</a>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['ok'])): ?>
  <?php flash('ok', 'Enregistré avec succès.'); ?>
<?php endif; ?>
<p style="font-size:12.5px; color:var(--mavka-color-text-muted); margin:8px 0 0;">Clique un titre de colonne pour trier · clique une cellule (Catégorie, Date, Format, Public, Récurrence, Texte du bouton, Lieu, Ville, Heure, Places, Ordre, Statut, État) pour la corriger directement ici.</p>

<table class="mavka-table" style="margin-top:12px;">
  <tr>
    <th></th>
    <th><?= act_tri_lien('titre', 'Titre', $tri, $sens) ?></th>
    <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type, $options]): ?>
    <th data-col="<?= htmlspecialchars($cle) ?>"><?= act_tri_lien($cle, $libelle, $tri, $sens) ?></th>
    <?php endforeach; ?>
    <th></th>
  </tr>
  <?php foreach ($activites as $a): ?>
  <tr>
    <td><?= act_apercu_photo($a['photo']) ?></td>
    <td><?= htmlspecialchars($a['titre']) ?></td>
    <?php foreach ($colonnes as $cle => [$libelle, $defaut, $type, $options]): ?>
    <td data-col="<?= htmlspecialchars($cle) ?>">
      <?php if ($type === 'edit_date'): ?>
        <?php
          $dateAffichee = $a['date_debut'] ? date('d/m/Y', strtotime($a['date_debut'])) : ($a['recurrence'] ?: '—');
        ?>
        <?php if (peut_editer($user)): ?>
        <span class="mavka-editable" data-editable-date data-id="<?= $a['id'] ?>" data-field="date_debut"
          data-value="<?= htmlspecialchars($a['date_debut'] ?? '') ?>" data-recurrence="<?= htmlspecialchars($a['recurrence'] ?? '') ?>"><?= htmlspecialchars($dateAffichee) ?></span>
        <?php else: ?>
        <?= htmlspecialchars($dateAffichee) ?>
        <?php endif; ?>
      <?php elseif ($type === 'select'): ?>
        <?php if (peut_editer($user)): ?>
        <span class="mavka-editable" data-editable-select data-id="<?= $a['id'] ?>" data-field="<?= htmlspecialchars($cle) ?>"
          data-value="<?= htmlspecialchars($a[$cle]) ?>" data-options='<?= htmlspecialchars(json_encode($options, JSON_UNESCAPED_UNICODE)) ?>'>
          <span class="mavka-badge mavka-badge--<?= in_array($a[$cle], ['publie', 'ouvert'], true) ? 'publie' : 'brouillon' ?>"><?= htmlspecialchars($options[$a[$cle]] ?? $a[$cle]) ?></span>
        </span>
        <?php else: ?>
        <span class="mavka-badge mavka-badge--brouillon"><?= htmlspecialchars($options[$a[$cle]] ?? $a[$cle]) ?></span>
        <?php endif; ?>
      <?php elseif ($type === 'select_plain'): ?>
        <?php
          $valActuelle = (string)($a[$cle] ?? '');
          $libelleActuel = $options[$valActuelle] ?? ($valActuelle !== '' ? $valActuelle : '—');
        ?>
        <?php if (peut_editer($user)): ?>
        <span class="mavka-editable" data-editable-select-plain data-id="<?= $a['id'] ?>" data-field="<?= htmlspecialchars($cle) ?>"
          data-value="<?= htmlspecialchars($valActuelle) ?>" data-options='<?= htmlspecialchars(json_encode($options, JSON_UNESCAPED_UNICODE)) ?>'><?= htmlspecialchars($libelleActuel) ?></span>
        <?php else: ?>
        <?= htmlspecialchars($libelleActuel) ?>
        <?php endif; ?>
      <?php elseif ($type === 'fill'): ?>
        <?= act_apercu_texte((string)($a[$cle] ?? '')) ?>
      <?php elseif ($type === 'lien'): ?>
        <?= act_apercu_lien($a['lien_inscription']) ?>
      <?php elseif ($type === 'date'): ?>
        <?= $a[$cle] ? htmlspecialchars(date('d/m/Y H:i', strtotime($a[$cle]))) : '' ?>
      <?php elseif (($type === 'edit_text' || $type === 'edit_nombre') && peut_editer($user)): ?>
        <span class="mavka-editable" data-editable data-type="<?= $type === 'edit_nombre' ? 'number' : 'text' ?>" data-id="<?= $a['id'] ?>" data-field="<?= htmlspecialchars($cle) ?>"><?= $a[$cle] !== null ? htmlspecialchars((string)$a[$cle]) : '' ?></span>
      <?php else: ?>
        <?= $a[$cle] !== null ? htmlspecialchars((string)$a[$cle]) : '—' ?>
      <?php endif; ?>
    </td>
    <?php endforeach; ?>
    <td style="white-space:nowrap;">
      <?php if (peut_editer($user)): ?>
      <a href="/admin/activite-form.php?id=<?= $a['id'] ?>" class="mavka-btn mavka-btn--sm">Modifier</a>
      <a href="/admin/activite-delete.php?id=<?= $a['id'] ?>" class="mavka-btn mavka-btn--sm mavka-btn--danger"
         onclick="return confirm('Supprimer cette activité ?');">Supprimer</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$activites): ?>
  <tr><td colspan="<?= count($colonnes) + 3 ?>" style="color:var(--mavka-color-text-muted);">Aucune activité pour l'instant.</td></tr>
  <?php endif; ?>
</table>

<script>
(function(){
  var STORAGE_KEY = 'mavka-activites-colonnes';
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
    fetch('/admin/activite-inline-update.php', {
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
      input.type = span.dataset.type === 'number' ? 'number' : 'text';
      input.value = original;
      span.textContent = '';
      span.appendChild(input);
      input.focus();
      input.select();

      function fermer(nouvelleValeur){ span.textContent = nouvelleValeur === null || nouvelleValeur === '' ? '' : nouvelleValeur; }
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

  document.querySelectorAll('[data-editable-select]').forEach(function(span){
    span.addEventListener('click', function(){
      if (span.querySelector('select')) return;
      var original = span.dataset.value;
      var badgeHtml = span.innerHTML;
      var options = JSON.parse(span.dataset.options || 'null');
      var enCours = false; // évite que 'blur' (déclenché par confirm()) n'entre en collision avec 'change'
      var select = document.createElement('select');
      Object.keys(options).forEach(function(val){
        var opt = document.createElement('option');
        opt.value = val; opt.textContent = options[val];
        if (val === original) opt.selected = true;
        select.appendChild(opt);
      });
      span.innerHTML = '';
      span.appendChild(select);
      select.focus();

      select.addEventListener('change', function(){
        enCours = true;
        var val = select.value;
        if (val === original) { span.innerHTML = badgeHtml; return; }
        if (!confirm('Enregistrer « ' + options[val] + ' » ?')) { span.innerHTML = badgeHtml; return; }
        envoyer(span.dataset.id, span.dataset.field, val, function(){
          var classeBadge = (val === 'publie' || val === 'ouvert') ? 'mavka-badge--publie' : 'mavka-badge--brouillon';
          span.dataset.value = val;
          span.innerHTML = '<span class="mavka-badge ' + classeBadge + '">' + options[val] + '</span>';
        }, function(err){
          alert(err);
          span.innerHTML = badgeHtml;
        });
      });
      select.addEventListener('blur', function(){ if (!enCours) span.innerHTML = badgeHtml; });
    });
  });

  // Même principe que [data-editable-select], mais sans pastille colorée (Catégorie/Format/Public).
  document.querySelectorAll('[data-editable-select-plain]').forEach(function(span){
    span.addEventListener('click', function(){
      if (span.querySelector('select')) return;
      var original = span.dataset.value;
      var texteOriginal = span.textContent;
      var options = JSON.parse(span.dataset.options || 'null');
      var enCours = false;
      var select = document.createElement('select');
      Object.keys(options).forEach(function(val){
        var opt = document.createElement('option');
        opt.value = val; opt.textContent = options[val];
        if (val === original) opt.selected = true;
        select.appendChild(opt);
      });
      span.textContent = '';
      span.appendChild(select);
      select.focus();

      select.addEventListener('change', function(){
        enCours = true;
        var val = select.value;
        if (val === original) { span.textContent = texteOriginal; return; }
        if (!confirm('Enregistrer « ' + options[val] + ' » ?')) { span.textContent = texteOriginal; return; }
        envoyer(span.dataset.id, span.dataset.field, val, function(){
          span.dataset.value = val;
          span.textContent = options[val];
        }, function(err){
          alert(err);
          span.textContent = texteOriginal;
        });
      });
      select.addEventListener('blur', function(){ if (!enCours) span.textContent = texteOriginal; });
    });
  });

  // Date (date_debut) : input type=date ; vide = activité régulière, revient à afficher la Récurrence.
  document.querySelectorAll('[data-editable-date]').forEach(function(span){
    span.addEventListener('click', function(){
      if (span.querySelector('input')) return;
      var original = span.dataset.value;
      var texteOriginal = span.textContent;
      var input = document.createElement('input');
      input.type = 'date';
      input.value = original;
      span.textContent = '';
      span.appendChild(input);
      input.focus();

      function fermer(texte){ span.textContent = texte; }
      function formaterDate(iso){
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
      }
      function tenterEnregistrer(){
        var val = input.value;
        if (val === original) { fermer(texteOriginal); return; }
        var libelle = val ? formaterDate(val) : (span.dataset.recurrence || '—');
        if (!confirm('Enregistrer « ' + (val ? libelle : '(vide, activité régulière)') + ' » ?')) { fermer(texteOriginal); return; }
        envoyer(span.dataset.id, span.dataset.field, val, function(saved){
          span.dataset.value = saved || '';
          fermer(saved ? formaterDate(saved) : (span.dataset.recurrence || '—'));
        }, function(err){
          alert(err);
          fermer(texteOriginal);
        });
      }
      input.addEventListener('keydown', function(e){
        if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        if (e.key === 'Escape') { input.value = original; input.blur(); }
      });
      input.addEventListener('blur', tenterEnregistrer);
    });
  });
})();
</script>
<?php admin_footer(); ?>
