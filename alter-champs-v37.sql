-- v37 : trace de l'envoi d'email pour chaque message du formulaire de contact
-- (messages_contact). Le message est toujours enregistré en base même si l'email échoue
-- (contact-handler.php), mais jusqu'ici l'échec n'était visible que dans error_log() du
-- serveur — inaccessible sur certains hébergements mutualisés (hPanel Hostinger ne l'expose
-- pas). Désormais visible directement dans /admin/messages.php.
-- email_envoye NULL par défaut (pas 0) : les messages déjà en base avant cette migration n'ont
-- jamais été suivis, on ne sait pas s'ils sont partis — NULL = "non suivi", distinct de
-- 0 = "échec confirmé". Avec un défaut à 0, tous les anciens messages afficheraient
-- faussement "email non envoyé".

ALTER TABLE messages_contact
  ADD COLUMN email_envoye TINYINT(1) NULL DEFAULT NULL AFTER message,
  ADD COLUMN email_erreur VARCHAR(255) NULL AFTER email_envoye;
