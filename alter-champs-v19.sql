-- v19 : mode aperçu pour les visiteurs connecté·e·s (bénévole/admin) sur le site public.
--
-- Quand quelqu'un est connecté (Espace bénévole ou admin), les pages publiques (accueil, page
-- volontaire) montrent TOUTES les activités — brouillons compris, sans lien d'inscription, pas
-- encore acceptées par tou·te·s les intervenant·e·s — pour se projeter sur le site "fini" et
-- donner envie de finir les démarches (Charte, acceptation des activités...). Un visiteur anonyme
-- continue de voir exactement ce qui est prêt, comme avant (activites_publiques, inchangée).
--
-- activites_toutes est le miroir sans filtre de activites_publiques ; le choix entre les deux
-- se fait côté PHP (includes/site_functions.php → site_previsualisation_active()).

CREATE OR REPLACE VIEW activites_toutes AS
SELECT
  a.id, a.titre, a.heure, a.lieu, a.ville, a.format, a.public, a.nombre_places,
  a.statut_activite, a.description, a.categorie, a.categorie_display,
  a.lien_inscription, a.texte_bouton, a.photo, a.date_debut, a.recurrence, a.ordre,
  (SELECT GROUP_CONCAT(iv.nom SEPARATOR ', ')
     FROM activite_intervenant ai JOIN intervenants iv ON iv.id = ai.intervenant_id
    WHERE ai.activite_id = a.id) AS intervenants_noms
FROM activites a
ORDER BY a.ordre ASC, a.date_debut ASC;
