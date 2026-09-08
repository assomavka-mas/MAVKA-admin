-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Додає поля профілю волонтера (Parcours MAVKA) і таблицю для скріншотів документів.
-- Якщо ти вже виконувала попередню версію цього файлу (з колонкою statut) —
-- нічого страшного, просто пропусти цей запуск, колонка більше не використовується.

ALTER TABLE intervenants
  ADD COLUMN domaine VARCHAR(100) NULL AFTER role_titre,
  ADD COLUMN adresse VARCHAR(255) NULL AFTER domaine,
  ADD COLUMN secteur_intervention VARCHAR(255) NULL AFTER specialite,
  ADD COLUMN charte_benevolat_lien VARCHAR(500) NULL AFTER bio,
  ADD COLUMN contrat_intervention_lien VARCHAR(500) NULL AFTER charte_benevolat_lien,
  ADD COLUMN date_signee DATE NULL AFTER contrat_intervention_lien,
  ADD COLUMN cv_lien VARCHAR(500) NULL AFTER date_signee,
  ADD COLUMN documents_pro_lien VARCHAR(500) NULL AFTER cv_lien,
  ADD COLUMN projet_developpement TEXT NULL AFTER documents_pro_lien,
  ADD COLUMN objectifs_mavka TEXT NULL AFTER projet_developpement;

CREATE TABLE IF NOT EXISTS intervenant_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  label VARCHAR(150) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
