-- MAVKA — схема бази даних для u568973923_mavka_dev
-- Виконати один раз через phpMyAdmin (вкладка "SQL") на цій базі.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS intervenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  dossier VARCHAR(255) NULL,             -- "id-nom-slug", nom du dossier dans /assets/uploads/intervenants/
  role_titre VARCHAR(255) NULL,          -- напр. "Présidente", "Bénévole"
  resume VARCHAR(300) NULL,              -- courte description affichée sur la carte (sous le rôle)
  domaine VARCHAR(255) NULL,             -- plusieurs valeurs possibles, séparées par des virgules
  adresse VARCHAR(255) NULL,
  specialite VARCHAR(255) NULL,          -- напр. "Musique", "Art-thérapie"
  bio TEXT NULL,                         -- public : onglet "Présentation"
  parcours_personnel TEXT NULL,          -- public : onglet "Parcours" (récit personnel, pas le suivi interne)
  vision TEXT NULL,                      -- public : onglet "Ma vision"
  charte_benevolat_lien VARCHAR(500) NULL,     -- lien Google Drive
  charte_benevolat_fichier VARCHAR(255) NULL,  -- fichier téléversé (image ou PDF)
  contrat_intervention_lien VARCHAR(500) NULL, -- lien Google Drive
  contrat_intervention_fichier VARCHAR(255) NULL,
  date_signee DATE NULL,
  cv_lien VARCHAR(500) NULL,                   -- lien Google Drive
  cv_fichier VARCHAR(255) NULL,
  rib_lien VARCHAR(500) NULL,                  -- lien Google Drive : RIB (coordonnées bancaires)
  rib_fichier VARCHAR(255) NULL,
  assurance_lien VARCHAR(500) NULL,            -- lien Google Drive : assurance professionnelle (= RC Pro)
  assurance_fichier VARCHAR(255) NULL,
  assurance_date DATE NULL,                    -- date d'expiration : déclenche l'alerte RC Pro (voir plus bas)
  -- Dossier Prestataire (privé, jamais public) — vérifications administratives demandées pour
  -- rester en règle vis-à-vis des organismes de contrôle. Voir alter-champs-v24.sql.
  numero_siret VARCHAR(20) NULL,               -- SIREN ou SIRET, au choix (14 ou 9 chiffres)
  piece_identite_fichier VARCHAR(255) NULL,
  piece_identite_date_verification DATE NULL,
  droit_exercer_necessaire TINYINT(1) NOT NULL DEFAULT 0,  -- coché seulement si l'activité l'exige
  droit_exercer_fichier VARCHAR(255) NULL,
  avis_sirene_fichier VARCHAR(255) NULL,
  b3_presente TINYINT(1) NOT NULL DEFAULT 0,   -- casier judiciaire (bulletin n°3) présenté
  b3_date DATE NULL,                           -- auto-rempli quand la case passe à cochée
  b3_verifie_par VARCHAR(255) NULL,            -- auto-rempli (email de l'admin qui a coché)
  date_entree_prestataire DATE NULL,
  dossier_prestataire_maj_le DATETIME NULL,    -- auto, jamais saisi à la main (voir intervenant-form.php)
  assurance_alerte_envoyee_le DATE NULL,       -- anti-spam de l'alerte email RC Pro, voir admin/alertes-documents.php
  projet_developpement VARCHAR(500) NULL,        -- lien Google Drive : document détaillé, public (page volontaire)
  projet_developpement_fichier VARCHAR(255) NULL, -- fichier téléversé, alternative/complément au lien, public
  projet_developpement_description TEXT NULL,    -- court texte public, affiché dans le bloc "Projet personnel"
  projet_developpement_nom VARCHAR(255) NULL,    -- titre du projet, public (v25) — sert de h2 sur la page volontaire
  objectifs_mavka TEXT NULL,             -- interne : idem
  statut_qualifications ENUM('non_requis','a_verifier','verifie','a_completer') NOT NULL DEFAULT 'non_requis',
    -- interne, jamais public : coché à la main par Larysa — ne se déduit pas des lignes
    -- intervenant_qualifications ci-dessous. Sert pour les activités qui exigent une
    -- qualification professionnelle vérifiée (pas toutes) — voir alter-champs-v22.sql.
  photo VARCHAR(255) NULL,               -- ім'я файлу в /assets/uploads/intervenants/{dossier}/
  avatar_mavka VARCHAR(255) NULL,        -- illustration (PNG transparent), bloc "Qui est [Nom]" de sa page publique
  avatar_domaine_culture VARCHAR(255) NULL,      -- affiché automatiquement en fond de carte d'activité
  avatar_domaine_education VARCHAR(255) NULL,    -- (site_activite_avatar_defaut(), includes/site_functions.php)
  avatar_domaine_bien_etre VARCHAR(255) NULL,    -- quand l'activité de cette catégorie n'a pas de photo —
  avatar_domaine_initiatives VARCHAR(255) NULL,  -- un visuel par domaine coché pour la personne
  email VARCHAR(255) NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Diplômes/attestations/certifications déclarés pour un·e volontaire — interne, jamais affiché
-- sur la page publique. Une personne peut avoir plusieurs lignes. La vérification (passage à
-- 'verifie', date_verification, verifie_par, note_admin) est réservée à super_admin — voir
-- admin/intervenant-form.php. Fichiers stockés comme les autres documents administratifs
-- (RIB, Assurance...) : nom de fichier aléatoire, jamais lié depuis une page publique.
CREATE TABLE IF NOT EXISTS intervenant_qualifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  type_justificatif ENUM('diplome','attestation','certification','reconnaissance','autorisation','autre') NOT NULL,
  intitule VARCHAR(255) NOT NULL,
  organisme VARCHAR(255) NULL,
  pays VARCHAR(100) NULL,
  annee_obtention YEAR NULL,
  fichier VARCHAR(255) NULL,             -- ім'я файлу в /assets/uploads/intervenants/{dossier}/qualifications/
  statut ENUM('a_verifier','verifie','a_completer') NOT NULL DEFAULT 'a_verifier',
  date_verification DATE NULL,
  verifie_par VARCHAR(255) NULL,         -- email de l'admin (auto-rempli à la vérification)
  note_admin TEXT NULL,                  -- privé, réservé à super_admin
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intervenant_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,           -- ім'я файлу в /assets/uploads/intervenants/{dossier}/documents/
  label VARCHAR(150) NULL,               -- напр. "CV", "Charte signée"
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intervenant_document_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  champ VARCHAR(50) NOT NULL,            -- напр. "cv_fichier", "rib_fichier"
  fichier VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intervenant_ateliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  titre VARCHAR(255) NOT NULL,           -- "Ce que je propose" — atelier possible, pas forcément programmé
  description TEXT NULL,
  ordre INT NOT NULL DEFAULT 0,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Galerie publique (ses réalisations, son atelier en images) affichée sur sa page volontaire.
-- Vide = le bloc "Galerie" n'apparaît pas du tout sur la page (voir intervenant.php).
CREATE TABLE IF NOT EXISTS intervenant_galerie (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,           -- ім'я файлу в /assets/uploads/intervenants/{dossier}/galerie/
  ordre INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  categorie VARCHAR(100) NOT NULL,       -- Culture / Éducation / Bien-être / Initiatives / Événementiel
  categorie_display VARCHAR(150) NULL,   -- напр. "Bien-être · Art-thérapie" — короткий підзаголовок для карток
  format ENUM('Collectif','Individuel') NULL,
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
  visible_accueil TINYINT(1) NOT NULL DEFAULT 0,  -- coché à la main : plus simple d'activer les quelques activités prêtes que de désactiver toutes les autres
  mis_en_avant TINYINT(1) NOT NULL DEFAULT 0,     -- coché = prioritaire dans le bandeau des 6 prochaines (accueil) ; le reste des places se comble par date la plus proche
  ordre INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Acceptation par PERSONNE, pas par activité : une activité avec 3 intervenant·e·s lié·e·s
-- a besoin des 3 accords, chacun distinct — même quand l'un·e des 3 est aussi super_admin,
-- son propre accord ne vaut pas pour les 2 autres (pas d'exception, même pour Larysa elle-même).
CREATE TABLE IF NOT EXISTS activite_intervenant (
  activite_id INT NOT NULL,
  intervenant_id INT NOT NULL,
  accepte TINYINT(1) NOT NULL DEFAULT 0,
  accepte_le DATETIME NULL,
  PRIMARY KEY (activite_id, intervenant_id),
  FOREIGN KEY (activite_id) REFERENCES activites(id) ON DELETE CASCADE,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  nom VARCHAR(255) NULL,                 -- pour l'afficher dans la liste des accès (surtout si pas lié à un intervenant)
  password_hash VARCHAR(255) NULL,       -- non utilisé : connexion via Google
  role ENUM('super_admin','mavka_admin','benevole','partenaire') NOT NULL DEFAULT 'benevole',
  intervenant_id INT NULL,               -- прив'язка до свого профілю в intervenants, якщо benevole
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  derniere_connexion DATETIME NULL,      -- mise à jour à chaque connexion Google (v26)
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activité des bénévoles visible pour Larysa (v26) : connexions + actions qu'un·e bénévole fait
-- lui/elle-même (profil, photos d'activité) — jamais les actions d'un super_admin/mavka_admin,
-- pas un audit-trail général, juste de quoi voir si les bénévoles s'engagent.
CREATE TABLE IF NOT EXISTS journal_activite (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  detail VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
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
-- Ne montre jamais une activité sans lien d'inscription, ni une activité liée à au moins un·e
-- intervenant·e qui ne l'a pas encore acceptée — TOU·TE·S les intervenant·e·s lié·e·s doivent
-- avoir accepté, pas juste un·e (activite_intervenant.accepte, par personne) — ces deux cas
-- restent visibles uniquement dans l'admin et dans l'espace du·de la volontaire concerné·e.
CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.mis_en_avant, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie' AND a.visible_accueil = 1
  AND a.lien_inscription IS NOT NULL AND a.lien_inscription != ''
  AND NOT EXISTS (SELECT 1 FROM activite_intervenant ai2 WHERE ai2.activite_id = a.id AND ai2.accepte = 0)
ORDER BY a.ordre ASC, a.date_debut ASC;

-- Comme activites_publiques, mais SANS aucun des 4 filtres (brouillons compris, sans lien
-- d'inscription, pas encore acceptées par tou·te·s les intervenant·e·s, visible_accueil = 0).
-- Utilisée uniquement pour un visiteur connecté (bénévole/admin, voir
-- site_previsualisation_active() dans includes/site_functions.php) : se projeter sur le site
-- "fini" pour donner envie de finir les démarches (Charte, acceptation des activités...).
-- Jamais utilisée pour un visiteur anonyme.
CREATE OR REPLACE VIEW activites_toutes AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.mis_en_avant, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
ORDER BY a.ordre ASC, a.date_debut ASC;

-- Mini-CRM "Partenaires" (v27) — mairies, centres sociaux, fondations... Hiérarchie
-- Organisation > Contacts > Rencontres (négociations) > Projets/Accords (résultats qui
-- découlent des rencontres, jamais l'inverse). Réservé à super_admin/mavka_admin.
CREATE TABLE IF NOT EXISTS partenaires_organisations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  type ENUM('mairie','centre_social','fondation','association','entreprise','autre') NOT NULL DEFAULT 'autre',
  ville VARCHAR(255) NULL,
  adresse VARCHAR(500) NULL,
  site_web VARCHAR(500) NULL,
  email_general VARCHAR(255) NULL,
  telephone VARCHAR(50) NULL,
  statut ENUM('potentiel','actif','partenaire','inactif','en_pause') NOT NULL DEFAULT 'potentiel', -- v31 : "partenaire" = relation établie
  notes TEXT NULL,
  dossier_drive_lien VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaires_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  organisation_id INT NOT NULL,
  nom VARCHAR(255) NOT NULL,
  genre ENUM('M','Mme','non_precise') NOT NULL DEFAULT 'non_precise', -- v29 : sert à accorder les titres/emails, plutôt que dupliquer chaque fonction au masculin et au féminin
  fonction VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  telephone VARCHAR(50) NULL,
  langue VARCHAR(100) NULL,
  niveau_influence ENUM('decideur_final','decideur_delegue','consultatif','administratif','inconnu') NOT NULL DEFAULT 'inconnu', -- v28, remplace decideur (trop grossier)
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organisation_id) REFERENCES partenaires_organisations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- etape_parcours reprend les 6 étapes déjà publiques du "Parcours de partenariat" (page
-- Collectivités) plutôt que d'inventer un statut différent.
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
