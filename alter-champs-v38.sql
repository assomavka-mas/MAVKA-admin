-- v38 : suivi interne uniquement pour le "projet de développement" d'un·e intervenant·e.
-- Permet de garder un projet renseigné (pour les échanges internes/documents) sans le publier
-- nulle part sur le site public : ni dans le bloc "Projet personnel" de sa page, ni dans le
-- chiffre "Projets bénévoles en développement" de l'accueil, ni dans le bloc "Nos projets en
-- développement" de la page Collectivités (index.php#collectivites). Utile par exemple quand le
-- projet est à l'arrêt faute de Charte du bénévolat signée — on ne veut pas l'afficher comme
-- actif tant que ce n'est pas le cas, sans pour autant perdre ce qui a été renseigné.

ALTER TABLE intervenants
  ADD COLUMN projet_developpement_interne TINYINT(1) NOT NULL DEFAULT 0 AFTER projet_developpement_nom;
