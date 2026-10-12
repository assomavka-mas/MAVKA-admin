-- v18 : galerie publique (photos de réalisations / de l'atelier) sur la page volontaire.
--
-- Bloc "Galerie" sur intervenant.php : affiche les photos ajoutées ici, dans l'ordre.
-- Aucune photo ajoutée = le bloc n'apparaît pas du tout sur la page.

CREATE TABLE IF NOT EXISTS intervenant_galerie (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  ordre INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
