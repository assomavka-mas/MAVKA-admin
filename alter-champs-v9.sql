-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- "Documents professionnels" був занадто загальним — розділяю на RIB (банківські
-- реквізити) і Assurance professionnelle (страховка), з датою для страховки.

ALTER TABLE intervenants
  CHANGE COLUMN documents_pro_lien rib_lien VARCHAR(500) NULL,
  CHANGE COLUMN documents_pro_fichier rib_fichier VARCHAR(255) NULL,
  ADD COLUMN assurance_lien VARCHAR(500) NULL AFTER rib_fichier,
  ADD COLUMN assurance_fichier VARCHAR(255) NULL AFTER assurance_lien,
  ADD COLUMN assurance_date DATE NULL AFTER assurance_fichier;
