-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Прибирає secteur_intervention (незрозуміле, дублювало Spécialité), розширює
-- domaine (тепер може містити кілька значень через кому), і перетворює
-- "Mon projet de développement" з опису на файл/посилання, як інші документи.

ALTER TABLE intervenants
  DROP COLUMN secteur_intervention,
  MODIFY COLUMN domaine VARCHAR(255) NULL,
  MODIFY COLUMN projet_developpement VARCHAR(500) NULL,
  ADD COLUMN projet_developpement_fichier VARCHAR(255) NULL AFTER projet_developpement;
