-- v14 : l'acceptation devient une décision PAR PERSONNE, pas par activité.
--
-- Défaut trouvé avec v13 : accepte_intervenant était un seul drapeau sur `activites`, donc
-- pour une activité liée à plusieurs intervenant·e·s, N'IMPORTE LEQUEL des accords (y compris
-- celui d'un compte super_admin qui est aussi intervenant·e) rendait l'activité "acceptée" —
-- sans l'accord des autres personnes liées. Corrigé : l'acceptation vit maintenant sur
-- `activite_intervenant` (une ligne par personne liée), et une activité n'est publique que
-- si CHACUNE des personnes liées a accepté — sans exception, même pour Larysa elle-même.

ALTER TABLE activite_intervenant
  ADD COLUMN accepte TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN accepte_le DATETIME NULL;

ALTER TABLE activites
  DROP COLUMN accepte_intervenant,
  DROP COLUMN accepte_le;

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
  AND NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 0)
ORDER BY a.ordre ASC, a.date_debut ASC;
