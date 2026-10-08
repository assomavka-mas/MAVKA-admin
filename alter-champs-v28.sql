-- v28 : affiner les contacts partenaires — un second email (ex. mairie + personnel) et une
-- échelle d'influence à la place du simple "décideur oui/non", pensée pour être utilisable sans
-- connaître toutes les nuances des titres français (maire, adjoint délégué, conseiller
-- communautaire...) : voir admin/partenaire-form.php.

ALTER TABLE partenaires_contacts
  ADD COLUMN email_secondaire VARCHAR(255) NULL AFTER email,
  ADD COLUMN niveau_influence ENUM('decideur_final','decideur_delegue','consultatif','administratif','inconnu') NOT NULL DEFAULT 'inconnu' AFTER langue,
  DROP COLUMN decideur;
