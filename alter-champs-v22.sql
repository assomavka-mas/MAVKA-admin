-- v22 : "Qualifications et justificatifs" — section privée du profil volontaire, pour vérifier
-- les qualifications professionnelles quand une activité l'exige (pas systématique). Jamais
-- affiché sur la page publique. Vérification (statut "Vérifié", date, qui a vérifié, note
-- administrative) réservée à super_admin — voir admin/intervenant-form.php.

ALTER TABLE intervenants
  ADD COLUMN statut_qualifications ENUM('non_requis','a_verifier','verifie','a_completer') NOT NULL DEFAULT 'non_requis' AFTER objectifs_mavka;

CREATE TABLE IF NOT EXISTS intervenant_qualifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intervenant_id INT NOT NULL,
  type_justificatif ENUM('diplome','attestation','certification','reconnaissance','autorisation','autre') NOT NULL,
  intitule VARCHAR(255) NOT NULL,
  organisme VARCHAR(255) NULL,
  pays VARCHAR(100) NULL,
  annee_obtention YEAR NULL,
  fichier VARCHAR(255) NULL,
  statut ENUM('a_verifier','verifie','a_completer') NOT NULL DEFAULT 'a_verifier',
  date_verification DATE NULL,
  verifie_par VARCHAR(255) NULL,
  note_admin TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (intervenant_id) REFERENCES intervenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
