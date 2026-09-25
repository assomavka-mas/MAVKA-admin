-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Додає читабельну назву папки волонтера і поля для завантаження самих файлів
-- документів (поряд з посиланнями на Google Drive).

ALTER TABLE intervenants
  ADD COLUMN dossier VARCHAR(255) NULL AFTER nom,
  ADD COLUMN charte_benevolat_fichier VARCHAR(255) NULL AFTER charte_benevolat_lien,
  ADD COLUMN contrat_intervention_fichier VARCHAR(255) NULL AFTER contrat_intervention_lien,
  ADD COLUMN cv_fichier VARCHAR(255) NULL AFTER cv_lien,
  ADD COLUMN documents_pro_fichier VARCHAR(255) NULL AFTER documents_pro_lien;
