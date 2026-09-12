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

// Надсилання листів через SMTP Gmail — надійніше за звичайний mail() на хостингу (той часто
// або взагалі не працює, або лист іде в спам, бо в домену сайту нема запису SPF/DKIM).
// Як отримати "пароль застосунку" (App Password) для SMTP_PASS:
//   1. На аккаунті Google, з якого будуть іти листи (можна саме CONTACT_EMAIL вище), увімкни
//      двоетапну перевірку: myaccount.google.com/security
//   2. Створи пароль застосунку: myaccount.google.com/apppasswords (з'явиться тільки якщо
//      двоетапна перевірка вже увімкнена) — вибери "Пошта" і будь-який пристрій, Google
//      покаже 16-значний пароль. Скопіюй його сюди як SMTP_PASS (це НЕ пароль від акаунта).
//   3. SMTP_USER — повна адреса Gmail, з якої йдуть листи (зазвичай = CONTACT_EMAIL).
// Якщо ці 3 константи не заповнені (лишити як тут, "ЗАМІНИ_..."), сайт продовжує пробувати
// звичайний mail() — повідомлення в будь-якому разі завжди зберігається в /admin/messages.php.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ЗАМІНИ_НА_ТВІЙ_EMAIL@gmail.com');
define('SMTP_PASS', 'ЗАМІНИ_НА_ПАРОЛЬ_ЗАСТОСУНКУ');
define('SMTP_FROM_NAME', 'MAVKA');

// Client ID з Google Cloud Console (APIs & Services → Credentials) — не секретний, можна показувати
define('GOOGLE_CLIENT_ID', 'ЗАМІНИ_НА_CLIENT_ID.apps.googleusercontent.com');

// Використовується для генерації посилань і cookie-сесій
define('SITE_URL', 'https://dev.mavka16.fr');
