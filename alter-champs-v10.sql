-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Історія версій документів: кожне нове завантаження додається сюди, стара версія
-- більше не "губиться" — лишається видимою в списку з датою.

CREATE TABLE IF NOT EXISTS intervenant_document_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  champ VARCHAR(50) NOT NULL,     -- напр. "cv_fichier", "rib_fichier"
  fichier VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
