-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Додає публічні поля "Parcours" і "Ma vision" (для сторінки волонтера, окремо
-- від внутрішніх projet_developpement/objectifs_mavka) і список можливих ательє.

ALTER TABLE intervenants
  ADD COLUMN parcours_personnel TEXT NULL AFTER bio,
  ADD COLUMN vision TEXT NULL AFTER parcours_personnel;

CREATE TABLE IF NOT EXISTS intervenant_ateliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  titre VARCHAR(255) NOT NULL,
  description TEXT NULL,
  ordre INT NOT NULL DEFAULT 0,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
