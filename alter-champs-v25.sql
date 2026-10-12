-- v25 : nom du projet personnel (bloc "Projet personnel" de la page volontaire) — jusqu'ici
-- seuls une description et un lien/fichier existaient, sans titre. Sert aussi de titre affiché
-- sur la page publique (à la place du texte fixe "En cours de construction") et permettra de
-- lister les projets par leur nom ailleurs sur le site (ex. bloc "chiffres" de l'accueil).

ALTER TABLE intervenants
  ADD COLUMN projet_developpement_nom VARCHAR(255) NULL AFTER projet_developpement_description;
