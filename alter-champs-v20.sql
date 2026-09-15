-- v20 : "Mettre en avant sur l'accueil" — case à cocher par activité pour contrôler quelles
-- 6 activités apparaissent dans le bandeau vedette de l'accueil (site_agenda_teaser), plutôt
-- que ce soit automatique (date la plus proche). Si moins de 6 sont cochées, le reste des
-- places se comble par date la plus proche, comme avant — voir includes/site_functions.php.

ALTER TABLE activites
  ADD COLUMN mis_en_avant TINYINT(1) NOT NULL DEFAULT 0 AFTER visible_accueil;

CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.mis_en_avant, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie' AND a.visible_accueil = 1
  AND a.lien_inscription IS NOT NULL AND a.lien_inscription != ''
  AND NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 0)
ORDER BY a.ordre ASC, a.date_debut ASC;

CREATE OR REPLACE VIEW activites_toutes AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.mis_en_avant, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
ORDER BY a.ordre ASC, a.date_debut ASC;
