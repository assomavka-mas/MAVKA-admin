-- v26 : visibilité de l'activité des bénévoles pour Larysa — connexion à l'admin et
-- modifications qu'un·e bénévole fait lui/elle-même (profil, photos d'activité). Rien
-- n'existait jusqu'ici : impossible de savoir si une personne s'est connectée ou a touché à
-- quoi que ce soit. Voir includes/functions.php (journal_logger()), includes/auth.php
-- (auth_login_google()), admin/mon-profil.php, admin/mes-activites.php, admin/dashboard.php.

ALTER TABLE admins
  ADD COLUMN derniere_connexion DATETIME NULL AFTER created_at;

CREATE TABLE IF NOT EXISTS journal_activite (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,   -- 'connexion', 'profil_modifie', 'photo_activite_ajoutee', 'photo_activite_supprimee'
  detail VARCHAR(255) NULL,       -- ex. le titre de l'activité concernée
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
