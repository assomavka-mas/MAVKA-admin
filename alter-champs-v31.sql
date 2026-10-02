-- v31 : ajouter le statut "Partenaire" aux organisations — distingue une relation établie
-- (ex. la mairie de Garat) des nombreux contacts encore au stade "Potentiel" (ex. les petites
-- associations de Garat saisies en lot). Voir admin/partenaires.php et admin/partenaire-form.php.

ALTER TABLE partenaires_organisations
  MODIFY COLUMN statut ENUM('potentiel','actif','partenaire','inactif','en_pause') NOT NULL DEFAULT 'potentiel';
