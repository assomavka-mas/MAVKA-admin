-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Додає нові поля до Activité (public, type, місткість, статус, фото)
-- і в'юшку activites_publiques для майбутньої головної сторінки.

ALTER TABLE activites
  ADD COLUMN format ENUM('Collectif','Individuel','Événementiel') NULL AFTER categorie,
  ADD COLUMN public ENUM('Enfant','Familial','Adultes') NULL AFTER format,
  ADD COLUMN nombre_places INT NULL AFTER lieu,
  ADD COLUMN statut_activite ENUM('ouvert','complet','annule','termine') NOT NULL DEFAULT 'ouvert' AFTER statut,
  ADD COLUMN categorie_display VARCHAR(150) NULL AFTER categorie,
  ADD COLUMN photo VARCHAR(255) NULL AFTER lien_inscription;

-- Переносимо старе булеве поле badge_ouvert у новий статус, потім видаляємо його
UPDATE activites SET statut_activite = IF(badge_ouvert = 1, 'ouvert', 'complet');
ALTER TABLE activites DROP COLUMN badge_ouvert;

-- В'юшка з готовими даними для публічного сайту (тільки опубліковані активності),
-- з полем intervenants_noms, зібраним автоматично зі зв'язаних волонтерів.
CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id,
  a.titre,
  a.heure,
  a.lieu,
  a.ville,
  a.format,
  a.public,
  a.nombre_places,
  a.statut_activite,
  a.description,
  a.categorie,
  a.categorie_display,
  a.lien_inscription,
  a.texte_bouton,
  a.photo,
  a.date_debut,
  a.recurrence,
  a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai
     JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie'
ORDER BY a.ordre ASC, a.date_debut ASC;
