-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL) на базі u568973923_mavka_dev.
-- Розширює систему ролей з 2 (admin/benevole) до 4:
-- super_admin (ти) / mavka_admin (президент, скарбник, секретар) / benevole / partenaire (тільки читання).

-- Крок 1: розширюємо enum, щоб вмістити і старі, і нові значення одночасно
ALTER TABLE admins MODIFY role ENUM('admin','benevole','super_admin','mavka_admin','partenaire') NOT NULL DEFAULT 'benevole';

-- Крок 2: переносимо існуючих 'admin' у 'super_admin'
UPDATE admins SET role = 'super_admin' WHERE role = 'admin';

-- Крок 3: прибираємо старе значення 'admin' з enum, лишаємо тільки фінальний набір
ALTER TABLE admins MODIFY role ENUM('super_admin','mavka_admin','benevole','partenaire') NOT NULL DEFAULT 'benevole';

-- Ім'я для показу в списку доступів (особливо для партнерів/адмінів без власного профілю волонтера)
ALTER TABLE admins ADD COLUMN nom VARCHAR(255) NULL AFTER email;
