-- v24 : Dossier Prestataire (privé) — vérifications administratives demandées pour rester en
-- règle vis-à-vis des organismes de contrôle (identité, SIREN/SIRET, avis SIRENE, casier
-- judiciaire B3...). Jamais affiché sur la page publique — voir admin/intervenant-form.php.
-- RC Professionnelle et Contrat-cadre réutilisent les champs assurance_* et
-- contrat_intervention_* déjà existants (pas de doublon).

ALTER TABLE intervenants
  ADD COLUMN numero_siret VARCHAR(20) NULL AFTER assurance_date,
  ADD COLUMN piece_identite_fichier VARCHAR(255) NULL AFTER numero_siret,
  ADD COLUMN piece_identite_date_verification DATE NULL AFTER piece_identite_fichier,
  ADD COLUMN droit_exercer_necessaire TINYINT(1) NOT NULL DEFAULT 0 AFTER piece_identite_date_verification,
  ADD COLUMN droit_exercer_fichier VARCHAR(255) NULL AFTER droit_exercer_necessaire,
  ADD COLUMN avis_sirene_fichier VARCHAR(255) NULL AFTER droit_exercer_fichier,
  ADD COLUMN b3_presente TINYINT(1) NOT NULL DEFAULT 0 AFTER avis_sirene_fichier,
  ADD COLUMN b3_date DATE NULL AFTER b3_presente,
  ADD COLUMN b3_verifie_par VARCHAR(255) NULL AFTER b3_date,
  ADD COLUMN date_entree_prestataire DATE NULL AFTER b3_verifie_par,
  ADD COLUMN dossier_prestataire_maj_le DATETIME NULL AFTER date_entree_prestataire,
  ADD COLUMN assurance_alerte_envoyee_le DATE NULL AFTER dossier_prestataire_maj_le;
