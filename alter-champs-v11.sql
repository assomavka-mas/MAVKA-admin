-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
--
-- "Afficher sur l'accueil" (visible_accueil) : une Activité peut être réelle et
-- réservable (vraie carte, vrai bouton Préinscription) SANS pour autant apparaître
-- dans la grille de la page d'accueil — utile pour une proposition "à l'essai"
-- présentée sur la page d'un·e volontaire (ex. pour sonder l'intérêt avant d'en
-- parler à une mairie), sans encombrer l'accueil de plusieurs cartes par volontaire.
-- Par défaut à 1 (visible) pour que toutes les Activités déjà créées ne disparaissent
-- pas de l'accueil après cette migration. Même idée que le champ "afficher_publiquement"
-- déjà utilisé côté WordPress (wordpress-plugin/mavka-activites-custom.php).
ALTER TABLE activites
  ADD COLUMN visible_accueil TINYINT(1) NOT NULL DEFAULT 1 AFTER statut_activite;

-- La vue publique (page d'accueil) ne prend désormais que les Activités marquées
-- visible_accueil = 1. Les Activités visible_accueil = 0 restent dans la table
-- `activites`, réelles et interrogeables directement (ex. par intervenant.php),
-- juste absentes de cette vue précise.
CREATE OR REPLACE VIEW activites_publiques AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
WHERE a.statut = 'publie' AND a.visible_accueil = 1
ORDER BY a.ordre ASC, a.date_debut ASC;
