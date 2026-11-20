-- v33 : ajoute l'heure et le lieu à une rencontre partenaire (admin/partenaire-form.php,
-- onglet Rencontres). Le lieu était sinon noté dans "sujet" en texte libre, ce qui empêche
-- toute recherche fiable plus tard — champ dédié à la place.

ALTER TABLE partenaires_rencontres
  ADD COLUMN heure_rencontre TIME NULL AFTER date_rencontre,
  ADD COLUMN lieu VARCHAR(255) NULL AFTER heure_rencontre;
