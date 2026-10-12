-- v35 : plusieurs participant·e·s par rencontre / par projet, au lieu d'un seul contact_id.
-- Utile notamment pour rattacher plusieurs volontaires MAVKA (contacts de l'organisation
-- "MAVKA" elle-même, type membre_mavka) à une rencontre ou un projet avec un partenaire.
-- Purement additif : partenaires_rencontres.contact_id n'est pas supprimé (juste plus utilisé
-- par le formulaire), les données existantes sont reprises dans la nouvelle table.

CREATE TABLE IF NOT EXISTS partenaires_rencontres_participants (
  rencontre_id INT NOT NULL,
  contact_id INT NOT NULL,
  PRIMARY KEY (rencontre_id, contact_id),
  FOREIGN KEY (rencontre_id) REFERENCES partenaires_rencontres(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES partenaires_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO partenaires_rencontres_participants (rencontre_id, contact_id)
SELECT id, contact_id FROM partenaires_rencontres WHERE contact_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS partenaires_projets_participants (
  projet_id INT NOT NULL,
  contact_id INT NOT NULL,
  PRIMARY KEY (projet_id, contact_id),
  FOREIGN KEY (projet_id) REFERENCES partenaires_projets(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES partenaires_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
