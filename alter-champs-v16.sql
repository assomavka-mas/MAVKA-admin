-- v16 : dernière trace de "Développement personnel" -> "Initiatives" -- dans le champ
-- resume (texte libre) d'un intervenant, pas dans categorie/domaine (deja corriges par v15).
-- C'est ce texte, affiche sur la carte "L'equipe", qui montrait encore l'ancien nom.

UPDATE intervenants
SET resume = REPLACE(resume, 'Développement personnel', 'Initiatives')
WHERE resume LIKE '%Développement personnel%';
