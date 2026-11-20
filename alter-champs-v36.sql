-- v36 : ajoute "Formation / ressources" comme type de contact (admin/partenaires.php,
-- partenaire-form.php). Pour les réseaux d'accompagnement/formation des associations
-- (Guid'Asso, FCOL-like fédérations...) — distinct des partenaires opérationnels habituels
-- (mairie, centre social...), ce ne sont pas eux qu'on anime des activités avec mais des
-- structures qui forment/outillent MAVKA elle-même.

ALTER TABLE partenaires_organisations
  MODIFY COLUMN type ENUM('mairie','centre_social','fondation','association','entreprise','membre_mavka','formation','autre') NOT NULL DEFAULT 'autre';
