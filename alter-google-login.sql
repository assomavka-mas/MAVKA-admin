-- Виконати ОДИН РАЗ у phpMyAdmin (вкладка SQL), якщо schema.sql вже був імпортований раніше.
-- Робить пароль необов'язковим, бо тепер вхід іде через Google.
ALTER TABLE admins MODIFY password_hash VARCHAR(255) NULL;
