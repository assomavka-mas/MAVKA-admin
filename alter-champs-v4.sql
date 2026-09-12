-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Додає коротке резюме для картки волонтера (окремо від довгого Bio).

ALTER TABLE intervenants
  ADD COLUMN resume VARCHAR(300) NULL AFTER role_titre;
