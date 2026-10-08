-- v34 : ajoute "Membre MAVKA" comme type de contact (admin/partenaires.php, partenaire-form.php).
-- Permet de suivre un·e volontaire/membre de l'association avec le même outil que les
-- partenaires externes (mairies, associations...) — rencontres et projets communs, dans les
-- vues transversales /admin/rencontres.php et /admin/projets.php.

ALTER TABLE partenaires_organisations
  MODIFY COLUMN type ENUM('mairie','centre_social','fondation','association','entreprise','membre_mavka','autre') NOT NULL DEFAULT 'autre';
