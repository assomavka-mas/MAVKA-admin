<?php
// MAVKA — приклад конфігурації. Скопіюй цей файл у config.php і заповни своїми реальними
// даними. config.php НЕ зберігається в git (навмисно, у .gitignore) — тому оновлення
// з git ніколи не затре твій робочий пароль на сервері.

define('DB_HOST', 'localhost');
define('DB_NAME', 'ЗАМІНИ_НА_НАЗВУ_БАЗИ');
define('DB_USER', 'ЗАМІНИ_НА_КОРИСТУВАЧА');
define('DB_PASS', 'ЗАМІНИ_НА_ПАРОЛЬ');

// Email, куди приходитимуть повідомлення з контактної форми
define('CONTACT_EMAIL', 'asso.mavka@gmail.com');

// Client ID з Google Cloud Console (APIs & Services → Credentials) — не секретний, можна показувати
define('GOOGLE_CLIENT_ID', 'ЗАМІНИ_НА_CLIENT_ID.apps.googleusercontent.com');

// Використовується для генерації посилань і cookie-сесій
define('SITE_URL', 'https://dev.mavka16.fr');
