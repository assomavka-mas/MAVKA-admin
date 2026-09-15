<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

// Pas encore de vraie logique derrière (pas de formulaires, rien n'est enregistré) — sert à
// montrer à quoi ces sections ressembleront une fois construites, avec les bonnes cartes selon
// le statut réel de la personne (Bénévole vs Intervenant·e rémunéré·e, déjà calculé ailleurs
// dans l'app via les documents signés — voir intervenant_statuts()). Tant qu'il n'y a pas
// d'activité pleinement acceptée, rien à déclarer : c'est normal, pas un bug.
$user = auth_require();

$stmt = db()->prepare('SELECT intervenant_id FROM admins WHERE id = ?');
$stmt->execute([$user['id']]);
$intervenant_id = $stmt->fetchColumn();

if (!$intervenant_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM intervenants WHERE id = ?');
$stmt->execute([$intervenant_id]);
$iv = $stmt->fetch();

$remunere = !empty($iv['contrat_intervention_lien']) || !empty($iv['contrat_intervention_fichier']);

$stmt = db()->prepare("SELECT COUNT(*) c FROM activites a
    JOIN activite_intervenant ai ON ai.activite_id = a.id
    WHERE ai.intervenant_id = ? AND a.lien_inscription IS NOT NULL AND a.lien_inscription != ''
      AND NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 0)");
$stmt->execute([$intervenant_id]);
$a_une_activite = (int)$stmt->fetchColumn() > 0;

function mavka_rapport_card(string $titre, string $description, bool $a_une_activite): void {
    ?>
    <div class="mavka-card mavka-rapport-card">
      <div class="mavka-rapport-card__titre"><?= htmlspecialchars($titre) ?></div>
      <p class="mavka-rapport-card__desc"><?= htmlspecialchars($description) ?></p>
      <p class="mavka-rapport-card__statut">
        <?= $a_une_activite ? '🔧 Bientôt disponible' : '— Rien à déclarer pour l\'instant' ?>
      </p>
    </div>
    <?php
}

admin_header('Mes rapports', $user, 'mes-rapports');
?>
<h1>Mes rapports</h1>
<p style="color:var(--mavka-color-text-muted); font-size:13.5px; margin:-4px 0 20px;">
  <?php if ($a_une_activite): ?>
    Compte rendu, frais et bilan pour tes activités — ces sections arrivent bientôt.
  <?php else: ?>
    Rien à déclarer pour l'instant : ces sections s'activeront dès ta première activité pleinement acceptée — <a href="/admin/mes-activites.php">voir Mes activités</a>.
  <?php endif; ?>
</p>

<span class="mavka-badge mavka-badge--publie" style="text-transform:uppercase; letter-spacing:.03em;"><?= $remunere ? 'Intervenant·e rémunéré·e' : 'Bénévole' ?></span>

<div class="mavka-rapports-grid">
  <?php if ($remunere): ?>
    <?php mavka_rapport_card('Facturation', 'Transmission de tes factures et documents de facturation.', $a_une_activite); ?>
    <?php mavka_rapport_card('Informations contractuelles', 'Retrouve les informations et documents liés à ton intervention.', true); ?>
    <?php mavka_rapport_card("Compte rendu d'activité", 'Après chaque activité, un court compte rendu à compléter ici.', $a_une_activite); ?>
    <?php mavka_rapport_card('Bilan mensuel', 'Un résumé de ton activité au sein de MAVKA, chaque mois.', $a_une_activite); ?>
  <?php else: ?>
    <?php mavka_rapport_card("Compte rendu d'activité", 'Après chaque activité, un court compte rendu à compléter ici.', $a_une_activite); ?>
    <?php mavka_rapport_card('Frais de déplacement', 'Demande de remboursement des frais de déplacement pour une activité MAVKA.', $a_une_activite); ?>
    <?php mavka_rapport_card('Frais de matériel', 'Demande de remboursement des achats nécessaires à une activité.', $a_une_activite); ?>
    <?php mavka_rapport_card('Bilan mensuel', 'Un court bilan de ton activité au sein de MAVKA, chaque mois.', $a_une_activite); ?>
  <?php endif; ?>
</div>

<style>
  .mavka-rapports-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 14px; }
  .mavka-rapport-card { display: flex; flex-direction: column; gap: 8px; }
  .mavka-rapport-card__titre { font-family: var(--mavka-font-display); font-size: 17px; color: var(--mavka-color-ink); }
  .mavka-rapport-card__desc { font-size: 13px; color: var(--mavka-color-text-muted); margin: 0; flex-grow: 1; }
  .mavka-rapport-card__statut { font-size: 12.5px; font-weight: 700; color: var(--mavka-color-text-muted); margin: 0; }
</style>
<?php admin_footer(); ?>
