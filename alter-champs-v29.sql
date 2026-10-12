-- v29 : champ "genre" séparé pour les contacts partenaires, plutôt que dupliquer chaque titre
-- de fonction au masculin ET au féminin (source du bug repéré par Larysa : "adjoint" avait sa
-- paire oubliée alors que les autres titres l'avaient). Sert aussi à accorder correctement les
-- emails/courriers rédigés pour une rencontre ("Cher Monsieur le Maire" / "Chère Madame le
-- Maire"). Les suggestions de fonction (admin/partenaire-form.php) passent à l'écriture
-- inclusive avec point médian déjà utilisée dans tout le projet (président·e, trésorier·ère...).

ALTER TABLE partenaires_contacts
  ADD COLUMN genre ENUM('M','Mme','non_precise') NOT NULL DEFAULT 'non_precise' AFTER nom;
