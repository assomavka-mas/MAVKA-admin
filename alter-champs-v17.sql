-- v17 : sépare la description publique du projet de développement, du lien/fichier détaillé.
--
-- "Mon projet de développement" (projet_developpement) était pensé comme un LIEN (VARCHAR,
-- même modèle que Charte/Contrat) -- pas un texte libre. Le bloc "Projet personnel" sur la
-- page publique du·de la volontaire (voir intervenant.php) affichait donc le lien brut
-- (ex. "https://docs.google.com/...") comme s'il s'agissait d'une description -- pas
-- présentable. Cette colonne accueille le vrai texte court, le lien/fichier existants
-- devenant le "document complet" consultable en plus.

ALTER TABLE intervenants
  ADD COLUMN projet_developpement_description TEXT NULL AFTER projet_developpement_fichier;
