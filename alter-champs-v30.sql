-- v30 : retirer email_secondaire des contacts partenaires — inutile en pratique : l'email
-- général de la mairie est déjà sur l'organisation (partenaires_organisations.email_general),
-- pas besoin de le dupliquer sur chaque contact ; "email" du contact suffit pour son adresse
-- personnelle/directe. Voir aussi admin/partenaire-form.php (passage à l'édition en ligne dans
-- le tableau, comme pour les intervenants, plutôt qu'un grand formulaire par ligne).

ALTER TABLE partenaires_contacts
  DROP COLUMN email_secondaire;
