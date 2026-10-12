-- v32 : archive interne des documents officiels signés (Règlement intérieur, PV de réunion,
-- politiques internes...) — jamais publique, contrairement à assets/docs/*.pdf. Sert à garder
-- une trace des versions successives (date, notes) au fil des mises à jour, plutôt que de
-- remplacer silencieusement le fichier public sans historique. Voir admin/archives.php.

CREATE TABLE IF NOT EXISTS archives_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  date_document DATE NULL,       -- date de signature/adoption, pas la date d'ajout ici
  fichier VARCHAR(255) NOT NULL, -- ім'я файлу dans /assets/uploads/archives/
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
