-- v23 : MAVKA-avatar (bloc "Qui est [Nom]" de la page volontaire) + avatars par direction
-- (bibliothèque personnelle de visuels, un par domaine coché — Larysa les pose ensuite à la
-- main sur les cartes d'activité concernées, aucun affichage automatique).

ALTER TABLE intervenants
  ADD COLUMN avatar_mavka VARCHAR(255) NULL AFTER photo,
  ADD COLUMN avatar_domaine_culture VARCHAR(255) NULL AFTER avatar_mavka,
  ADD COLUMN avatar_domaine_education VARCHAR(255) NULL AFTER avatar_domaine_culture,
  ADD COLUMN avatar_domaine_bien_etre VARCHAR(255) NULL AFTER avatar_domaine_education,
  ADD COLUMN avatar_domaine_initiatives VARCHAR(255) NULL AFTER avatar_domaine_bien_etre;
