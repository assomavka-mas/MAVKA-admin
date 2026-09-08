-- MAVKA — схема бази даних для u568973923_mavka_dev
-- Виконати один раз через phpMyAdmin (вкладка "SQL") на цій базі.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS intervenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  dossier VARCHAR(255) NULL,             -- "id-nom-slug", nom du dossier dans /assets/uploads/intervenants/
  role_titre VARCHAR(255) NULL,          -- напр. "Présidente", "Bénévole"
  resume VARCHAR(300) NULL,              -- courte description affichée sur la carte (sous le rôle)
  domaine VARCHAR(100) NULL,             -- Culture / Éducation / Bien-être / Développement personnel
  adresse VARCHAR(255) NULL,
  specialite VARCHAR(255) NULL,          -- напр. "Musique", "Art-thérapie"
  secteur_intervention VARCHAR(255) NULL,
  bio TEXT NULL,
  charte_benevolat_lien VARCHAR(500) NULL,     -- lien Google Drive
  charte_benevolat_fichier VARCHAR(255) NULL,  -- fichier téléversé (image ou PDF)
  contrat_intervention_lien VARCHAR(500) NULL, -- lien Google Drive
  contrat_intervention_fichier VARCHAR(255) NULL,
  date_signee DATE NULL,
  cv_lien VARCHAR(500) NULL,                   -- lien Google Drive
  cv_fichier VARCHAR(255) NULL,
  documents_pro_lien VARCHAR(500) NULL,        -- lien Google Drive
  documents_pro_fichier VARCHAR(255) NULL,
  projet_developpement TEXT NULL,
  objectifs_mavka TEXT NULL,
  photo VARCHAR(255) NULL,               -- ім'я файлу в /assets/uploads/intervenants/{dossier}/
  email VARCHAR(255) NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intervenant_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,           -- ім'я файлу в /assets/uploads/intervenants/{dossier}/documents/
  label VARCHAR(150) NULL,               -- напр. "CV", "Charte signée"
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  categorie VARCHAR(100) NOT NULL,       -- Culture / Éducation / Bien-être / Développement personnel
  categorie_display VARCHAR(150) NULL,   -- напр. "Bien-être · Art-thérapie" — короткий підзаголовок для карток
  format ENUM('Collectif','Individuel','Événementiel') NULL,
  public ENUM('Enfant','Familial','Adultes') NULL,
  description TEXT NULL,
  date_debut DATE NULL,                  -- NULL якщо активність регулярна (не разова)
  heure VARCHAR(50) NULL,                -- "18:00"
  recurrence VARCHAR(100) NULL,          -- "Le jeudi", "Hebdomadaire" — якщо регулярна
  lieu VARCHAR(255) NULL,
  nombre_places INT NULL,                -- NULL = без обмеження
  ville VARCHAR(100) NULL,
  texte_bouton VARCHAR(100) NOT NULL DEFAULT 'En savoir plus',
  lien_inscription VARCHAR(500) NULL,
  photo VARCHAR(255) NULL,               -- ім'я файлу в /assets/uploads/activites/
  statut ENUM('publie','brouillon') NOT NULL DEFAULT 'publie',
  statut_activite ENUM('ouvert','complet','annule','termine') NOT NULL DEFAULT 'ouvert',
  ordre INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activite_intervenant (
  activite_id INT NOT NULL,
  intervenant_id INT NOT NULL,
  PRIMARY KEY (activite_id, intervenant_id),
  FOREIGN KEY (activite_id) REFERENCES activites(id) ON DELETE CASCADE,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,       -- non utilisé : connexion via Google
  role ENUM('admin','benevole') NOT NULL DEFAULT 'benevole',
  intervenant_id INT NULL,               -- прив'язка до свого профілю в intervenants, якщо benevole
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages_contact (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  sujet VARCHAR(255) NULL,
  message TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- В'юшка з готовими даними для публічного сайту (тільки опубліковані активності),
-- з полем intervenants_noms, зібраним автоматично зі зв'язаних волонтерів.
CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie'
ORDER BY a.ordre ASC, a.date_debut ASC;
