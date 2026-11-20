-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
--
-- "Événementiel" retiré de Type d'activité (format) : ça décrivait en fait l'absence de
-- direction (ex. un festival partenaire à Garat, qui n'est ni Culture ni Éducation ni
-- Bien-être), pas la manière dont l'activité se déroule. Devient une 5e option de
-- Catégorie (Direction) à la place. Les activités déjà marquées format='Événementiel'
-- sont : reclassées en categorie='Événementiel', et leur format repasse à 'Collectif'
-- (un festival/événement partenaire reste un format collectif, pas individuel).
UPDATE activites
SET categorie = 'Événementiel', format = 'Collectif'
WHERE format = 'Événementiel';

ALTER TABLE activites
  MODIFY COLUMN format ENUM('Collectif','Individuel') NULL;
