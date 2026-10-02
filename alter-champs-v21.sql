-- v21 : "Afficher sur la page d'accueil" décochée par défaut pour les NOUVELLES activités.
--
-- Plus simple de cocher les quelques activités prêtes que d'aller décocher toutes les
-- autres une par une. Ne touche pas aux activités déjà enregistrées (leur visible_accueil
-- actuel reste inchangé) — seulement le défaut pour les prochaines créations.

ALTER TABLE activites
  ALTER visible_accueil SET DEFAULT 0;
