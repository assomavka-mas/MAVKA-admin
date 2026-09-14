-- v15 : unifie "Développement personnel" -> "Initiatives".
--
-- Les deux noms désignaient la même direction : l'accueil ("Nos directions") disait déjà
-- "Initiatives", mais la catégorie des activités et le domaine des intervenant·e·s disaient
-- "Développement personnel" -- la même chose sous deux libellés différents. Le code (catégories
-- proposées dans les formulaires, habillage des cartes, page Activités) est déjà passé sur
-- "Initiatives" ; cette migration met à jour les données déjà enregistrées pour rester cohérent.

UPDATE activites
SET categorie = 'Initiatives'
WHERE categorie = 'Développement personnel';

UPDATE intervenants
SET domaine = REPLACE(domaine, 'Développement personnel', 'Initiatives')
WHERE domaine LIKE '%Développement personnel%';
