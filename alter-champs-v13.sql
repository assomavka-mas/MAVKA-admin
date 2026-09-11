-- v13 : acceptation des activités par l'intervenant·e lié·e.
--
-- Règle métier (officielle après la fusion) : une activité liée à un·e intervenant·e doit
-- être acceptée par cette personne (conditions, description...) ET avoir un lien d'inscription
-- pour apparaître sur le site public. Tant que l'un des deux manque, l'activité reste visible
-- uniquement dans l'admin et dans l'espace du·de la volontaire concerné·e, mise en évidence
-- en rouge. Les activités déjà en ligne aujourd'hui sont considérées acceptées (elles ont été
-- convenues avant la mise en place de ce workflow) : seules les activités créées ou modifiées
-- à partir de maintenant repassent par une acceptation explicite.

ALTER TABLE activites
  ADD COLUMN accepte_intervenant TINYINT(1) NOT NULL DEFAULT 0 AFTER lien_inscription,
  ADD COLUMN accepte_le DATETIME NULL AFTER accepte_intervenant;

UPDATE activites SET accepte_intervenant = 1, accepte_le = NOW();

CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie' AND a.visible_accueil = 1
  AND a.lien_inscription IS NOT NULL AND a.lien_inscription != ''
  AND (
    a.accepte_intervenant = 1
    OR NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id)
  )
ORDER BY a.ordre ASC, a.date_debut ASC;
