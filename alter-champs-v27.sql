-- v27 : mini-CRM "Partenaires" (mairies, centres sociaux, fondations...) — hiérarchie
-- Organisation > Contacts > Rencontres (négociations) > Projets/Accords (résultats qui
-- découlent des rencontres). Accès admin/partenaires.php + admin/partenaire-form.php, réservé à
-- super_admin/mavka_admin (voir includes/layout.php).

CREATE TABLE IF NOT EXISTS partenaires_organisations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  type ENUM('mairie','centre_social','fondation','association','entreprise','autre') NOT NULL DEFAULT 'autre',
  ville VARCHAR(255) NULL,
  adresse VARCHAR(500) NULL,
  site_web VARCHAR(500) NULL,
  email_general VARCHAR(255) NULL,
  telephone VARCHAR(50) NULL,
  statut ENUM('potentiel','actif','inactif','en_pause') NOT NULL DEFAULT 'potentiel',
  notes TEXT NULL,
  dossier_drive_lien VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaires_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organisation_id INT NOT NULL,
  nom VARCHAR(255) NOT NULL,
  fonction VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  telephone VARCHAR(50) NULL,
  langue VARCHAR(100) NULL,
  decideur TINYINT(1) NOT NULL DEFAULT 0,  -- coche si cette personne a le pouvoir de décision
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organisation_id) REFERENCES partenaires_organisations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "Rencontres" = les négociations/échanges (le "Sделки" évoqué par Larysa) : réunions, appels,
-- emails, courriers. etape_parcours reprend volontairement les 6 étapes déjà publiques du
-- "Parcours de partenariat" (page Collectivités) plutôt que d'inventer un statut différent.
CREATE TABLE IF NOT EXISTS partenaires_rencontres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organisation_id INT NOT NULL,
  contact_id INT NULL,
  type ENUM('rencontre','appel','email','courrier') NOT NULL DEFAULT 'rencontre',
  date_rencontre DATE NOT NULL,
  sujet VARCHAR(255) NULL,
  compte_rendu TEXT NULL,
  etape_parcours ENUM('premiere_rencontre','co_construction','phase_pilote','mise_en_place','faire_evoluer','bilan') NULL,
  prochaine_action VARCHAR(255) NULL,
  date_prochaine_action DATE NULL,
  responsable VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organisation_id) REFERENCES partenaires_organisations(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES partenaires_contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "Projets" = accords, événements communs, projets concrets qui découlent d'une ou plusieurs
-- rencontres — jamais l'inverse. origine_rencontre_id est facultatif (traçabilité seulement).
CREATE TABLE IF NOT EXISTS partenaires_projets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organisation_id INT NOT NULL,
  origine_rencontre_id INT NULL,
  nom VARCHAR(255) NOT NULL,
  description TEXT NULL,
  statut ENUM('en_cours','termine','abandonne') NOT NULL DEFAULT 'en_cours',
  date_debut DATE NULL,
  date_fin DATE NULL,
  dossier_drive_lien VARCHAR(500) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organisation_id) REFERENCES partenaires_organisations(id) ON DELETE CASCADE,
  FOREIGN KEY (origine_rencontre_id) REFERENCES partenaires_rencontres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
