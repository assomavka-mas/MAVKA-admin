<?php
/**
 * Plugin Name: MAVKA Activités (custom, sans JetEngine)
 * Description: CPT "Activité" + lien avec les volontaires,
 *              filtre Elementor Loop Grid, accès complet admin.
 * Version: 1.8
 */

/* ============================================================
 * HISTORIQUE DES VERSIONS (à lire en langage simple, sans jargon)
 *
 *    1.8 — Anti-surcharge de la page d'accueil : nouveau champ
 *          "Afficher sur la page d'accueil ?" sur chaque Activité
 *          (Oui par défaut — rien ne change pour les Activités
 *          déjà publiées tant que vous ne le mettez pas sur Non).
 *          Mettre "Non" cache l'Activité de la page d'accueil UNIQUEMENT
 *          — elle reste visible sur la page publique du·de la volontaire
 *          et dans son "Mon Espace". Ajout aussi d'une nouvelle option
 *          de "Texte du bouton" : "Découvrir le·la bénévole", pensée
 *          pour une Activité "vitrine" sans date qui renvoie vers la
 *          page du·de la volontaire plutôt que vers HelloAsso.
 *
 *    1.7 — Ajout de l'affichage automatique des Activités d'UN
 *          volontaire précis sur SA page publique (ex. page de
 *          Hanna Sokha) : nouveau champ "Volontaire associé à
 *          cette page" sur les Pages, + nouveau Query ID Elementor
 *          "activites_intervenant_query" à utiliser dans un Loop
 *          Grid. Rien n'a été supprimé ni modifié dans les
 *          fonctionnalités existantes (versions 1.6 et avant).
 *
 *    1.6 — Version de départ de ce suivi de versions (tout ce qui
 *          existait avant l'ajout de cet historique).
 * ============================================================ */

if (!defined('ABSPATH')) exit; // захист від прямого доступу до файлу

/* ============================================================
 * 0. ЗАХИСТ ВІД ПОДВІЙНОГО ЗАВАНТАЖЕННЯ
 *    Якщо на сервері випадково лишилось кілька копій цього плагіна
 *    (наприклад, стара і нова версія одночасно активні), WordPress
 *    намагається двічі оголосити ті самі функції — і сайт падає з
 *    "Fatal error: Cannot redeclare function". Цей блок це запобігає:
 *    друга копія просто нічого не робить, замість того щоб зламати сайт.
 * ============================================================ */
if (defined('MAVKA_ACTIVITES_CUSTOM_LOADED')) {
    return;
}
define('MAVKA_ACTIVITES_CUSTOM_LOADED', true);

/* ============================================================
 * 0bis. РОЛЬ "MAVKA Admin" + CAPABILITY "mavka_manage_activites"
 *
 *    Ідея: "хто адмініструє сайт WordPress" (плагіни, теми,
 *    користувачі, безпека — право manage_options) і "хто керує
 *    Activités асоціації" — це два РІЗНІ поняття. Замість того
 *    щоб перевіряти скрізь manage_options (що змушує давати
 *    людям повне адмінство WordPress тільки заради Activités),
 *    вводимо окрему можливість mavka_manage_activites.
 *
 *    - Роль Administrator отримує цю можливість автоматично
 *      (ви й надалі бачите все, як зараз, без жодних дій).
 *    - Нова роль "MAVKA Admin" отримує ТІЛЬКИ цю можливість +
 *      мінімум прав для роботи з Activités (створення/редагування/
 *      видалення своїх і чужих Activités, доступ до медіатеки) —
 *      без прав ставити плагіни, міняти теми, видаляти
 *      користувачів тощо.
 *
 *    Призначити роль: Users → All Users → редагувати користувача
 *    → Role → "MAVKA Admin".
 *
 *    Синхронізація прав відбувається при кожному заході в
 *    wp-admin (admin_init) — ідемпотентно (не робить зайвої
 *    роботи, якщо все вже налаштовано), тож не треба деактивувати/
 *    активувати плагін після оновлення файлу вручну через FTP.
 * ============================================================ */
function mavka_ensure_role_and_capability() {
    // 1. Даємо Administrator нову можливість (якщо ще нема)
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('mavka_manage_activites')) {
        $admin_role->add_cap('mavka_manage_activites');
    }

    // 2. Створюємо роль "MAVKA Admin", якщо її ще нема
    if (!get_role('mavka_admin')) {
        add_role('mavka_admin', 'MAVKA Admin', [
            'read'                     => true, // доступ до wp-admin взагалі
            'upload_files'             => true, // додавати зображення до Activités
            'edit_posts'               => true, // бачити пункт меню Activités
            'edit_others_posts'        => true, // редагувати чужі Activités
            'edit_published_posts'     => true,
            'publish_posts'            => true,
            'delete_posts'             => true,
            'delete_others_posts'      => true,
            'delete_published_posts'   => true,
            'manage_categories'        => true, // редагувати таксономії Public / Type d'activité
            'mavka_manage_activites'   => true, // головна можливість: бачити/керувати ВСІМА Activités
        ]);
    }
}
add_action('admin_init', 'mavka_ensure_role_and_capability');
register_activation_hook(__FILE__, 'mavka_ensure_role_and_capability');

/**
 * Одноразове автозаповнення поля badge_ouvert для вже наявних Activités,
 * які були збережені ДО появи цього поля (V24) — щоб не змушувати вручну
 * пересилювати кожну картку. Ідемпотентно: пропускає ті, де поле вже є
 * (навіть порожнє значення "" рахується як "вже оброблено").
 */
function mavka_backfill_badge_ouvert() {
    $activites = get_posts([
        'post_type'      => 'activite',
        'post_status'    => ['publish', 'draft', 'pending', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'badge_ouvert',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);
    foreach ($activites as $activite_id) {
        $texte_bouton = get_post_meta($activite_id, 'texte_bouton', true);
        $badge = ($texte_bouton === 'En savoir plus') ? 'Événement ouvert' : '';
        update_post_meta($activite_id, 'badge_ouvert', $badge);
    }
}
add_action('admin_init', 'mavka_backfill_badge_ouvert');

/**
 * Універсальна перевірка: "чи має ця людина повний доступ до всіх Activités?"
 * OU manage_options (справжній адмін WordPress, про всяк випадок — щоб
 * ніколи не втратити доступ навіть якщо синхронізація ролі ще не відбулась),
 * OU нова можливість mavka_manage_activites (Administrator і MAVKA Admin).
 */
function mavka_user_can_manage_all_activites($user_id) {
    return user_can($user_id, 'manage_options') || user_can($user_id, 'mavka_manage_activites');
}

/* ============================================================
 * 1. CUSTOM POST TYPE "Activité"
 *    Дані самі активності (titre) зберігаються в стандартній
 *    таблиці wp_posts — вона вже нормалізована "з коробки".
 * ============================================================ */
function mavka_register_activite_cpt() {
    register_post_type('activite', [
        'label'        => 'Activités',
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => true,
        'supports'     => ['title'],           // тільки заголовок (titre)
        'menu_icon'    => 'dashicons-calendar-alt',
        'has_archive'  => false,
    ]);
}
add_action('init', 'mavka_register_activite_cpt');

/* ============================================================
 * 1bis. ТАКСОНОМІЇ "Public" та "Type d'activité"
 * ============================================================ */
function mavka_register_taxonomies() {
    register_taxonomy('public', 'activite', [
        'label'        => 'Public',
        'hierarchical' => false,
        'show_ui'      => true,
        'show_in_menu' => true,
    ]);
    register_taxonomy('type_activite', 'activite', [
        'label'        => "Type d'activité",
        'hierarchical' => false,
        'show_ui'      => true,
        'show_in_menu' => true,
    ]);
}
add_action('init', 'mavka_register_taxonomies');


/* ============================================================
 * 2. МЕТА-ПОЛЯ (date, heure, lieu, format, nombre_places, statut)
 * ============================================================ */
function mavka_activite_metabox() {
    add_meta_box(
        'mavka_activite_details',
        'Détails de l\'activité',
        'mavka_activite_metabox_html',
        'activite',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'mavka_activite_metabox');

function mavka_activite_metabox_html($post) {
    wp_nonce_field('mavka_save_activite', 'mavka_activite_nonce');

    $date    = get_post_meta($post->ID, 'date', true);
    $heure   = get_post_meta($post->ID, 'heure', true);
    $lieu    = get_post_meta($post->ID, 'lieu', true);
    $format  = get_post_meta($post->ID, 'format', true);
    $places  = get_post_meta($post->ID, 'nombre_places', true);
    $statut  = get_post_meta($post->ID, 'statut_activite', true);
    $lien    = get_post_meta($post->ID, 'lien_inscription', true);
    $desc    = get_post_meta($post->ID, 'description', true);
    $categ   = get_post_meta($post->ID, 'categorie', true);
    if (!is_array($categ)) $categ = [];
    ?>
    <p><label><strong>Description</strong></label><br>
        <textarea name="mavka_description" rows="4" style="width:100%"><?php echo esc_textarea($desc); ?></textarea></p>

    <p><label><strong>Catégorie</strong> <em>(plusieurs choix possibles)</em></label><br>
        <?php foreach (['Culture', 'Bien-être', 'Éducation', 'Développement personnel'] as $option) : ?>
            <label style="display:block;">
                <input type="checkbox" name="mavka_categorie[]" value="<?php echo esc_attr($option); ?>" <?php checked(in_array($option, $categ)); ?>>
                <?php echo esc_html($option); ?>
            </label>
        <?php endforeach; ?>
    </p>
    <p><label><strong>Date</strong></label><br>
        <input type="date" name="mavka_date" value="<?php echo esc_attr($date); ?>"></p>

    <p><label><strong>Heure</strong></label><br>
        <input type="time" name="mavka_heure" value="<?php echo esc_attr($heure); ?>"></p>

    <p><label><strong>Lieu</strong></label><br>
        <input type="text" name="mavka_lieu" value="<?php echo esc_attr($lieu); ?>" style="width:100%"></p>

    <p><label><strong>Format</strong></label><br>
        <select name="mavka_format">
            <option value="Présentiel" <?php selected($format, 'Présentiel'); ?>>Présentiel</option>
            <option value="En ligne" <?php selected($format, 'En ligne'); ?>>En ligne</option>
        </select></p>

    <p><label><strong>Nombre de places</strong></label><br>
        <input type="number" name="mavka_places" value="<?php echo esc_attr($places); ?>"></p>

    <p><label><strong>Statut</strong></label><br>
        <select name="mavka_statut">
            <option value="proposée" <?php selected($statut, 'proposée'); ?>>Proposée</option>
            <option value="confirmée" <?php selected($statut, 'confirmée'); ?>>Confirmée</option>
            <option value="réalisée" <?php selected($statut, 'réalisée'); ?>>Réalisée</option>
        </select></p>

    <p><label><strong>Afficher publiquement ?</strong></label><br>
        <?php $afficher = get_post_meta($post->ID, 'afficher_publiquement', true); ?>
        <label><input type="radio" name="mavka_afficher_publiquement" value="oui" <?php checked($afficher !== 'non'); ?>> Oui</label>
        <label style="margin-left:16px;"><input type="radio" name="mavka_afficher_publiquement" value="non" <?php checked($afficher === 'non'); ?>> Non (interne uniquement)</label></p>

    <p><label><strong>Afficher sur la page d'accueil ?</strong></label><br>
        <?php $afficher_accueil = get_post_meta($post->ID, 'afficher_accueil', true); ?>
        <label><input type="radio" name="mavka_afficher_accueil" value="oui" <?php checked($afficher_accueil !== 'non'); ?>> Oui</label>
        <label style="margin-left:16px;"><input type="radio" name="mavka_afficher_accueil" value="non" <?php checked($afficher_accueil === 'non'); ?>> Non (visible seulement sur la page du·de la volontaire et son espace)</label></p>
    <p><em>Utile pour éviter de surcharger la page d'accueil quand un·e volontaire a plusieurs Activités : décochez celles qui n'ont pas besoin d'apparaître sur le titre, tout en les gardant visibles sur sa page personnelle.</em></p>

    <hr>
    <p><label><strong>Lien d'inscription (HelloAsso)</strong></label><br>
        <input type="url" name="mavka_lien_inscription" value="<?php echo esc_attr($lien); ?>" style="width:100%" placeholder="https://..."></p>
    <p><em>La réduction (adhérent, découverte, partenaire CAF...) se gère directement dans les tarifs HelloAsso, pas ici.</em></p>

    <p><label><strong>Texte du bouton</strong></label><br>
        <?php
        $texte_bouton = get_post_meta($post->ID, 'texte_bouton', true);
        if (!$texte_bouton) $texte_bouton = 'Préinscription gratuite'; // valeur historique par défaut
        $options_bouton = [
            'Préinscription gratuite', // valeur historique (déjà utilisée sur les cartes existantes)
            'Préinscription',
            'Gratuit',
            'Événement régulier',
            'En savoir plus',
            'Payer la participation',
            'Découvrir le·la bénévole', // pour la carte "vitrine" d'un·e volontaire sur la page d'accueil (sans date, lien vers sa page — pas HelloAsso)
        ];
        ?>
        <select name="mavka_texte_bouton">
            <?php foreach ($options_bouton as $option) : ?>
                <option value="<?php echo esc_attr($option); ?>" <?php selected($texte_bouton, $option); ?>><?php echo esc_html($option); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p><em>Ce texte alimente le "Texte du bouton" dans vos composants Elementor via le champ personnalisé <code>texte_bouton</code> — le lien (URL) reste toujours celui du champ "Lien d'inscription" ci-dessus, quel que soit le texte choisi (utile p. ex. pour "En savoir plus" qui peut pointer vers la page de l'asso ou d'un partenaire plutôt que vers HelloAsso).</em></p>

    <hr>
    <p><strong>Volontaire(s) responsable(s)</strong></p>
    <?php
    global $wpdb;
    $rel_table = $wpdb->prefix . 'mavka_activite_intervenant';
    $linked_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT user_id FROM $rel_table WHERE activite_id = %d", $post->ID)
    );
    $all_users = get_users(['orderby' => 'display_name']);
    foreach ($all_users as $user) {
        $checked = in_array($user->ID, $linked_ids) ? 'checked' : '';
        echo '<label style="display:block;"><input type="checkbox" name="mavka_intervenants[]" value="' . esc_attr($user->ID) . '" ' . $checked . '> ' . esc_html($user->display_name) . '</label>';
    }
    ?>
    <?php
}

function mavka_save_activite_meta($post_id) {
    if (!isset($_POST['mavka_activite_nonce']) ||
        !wp_verify_nonce($_POST['mavka_activite_nonce'], 'mavka_save_activite')) {
        return;
    }
    $fields = [
        'mavka_date'            => 'date',
        'mavka_heure'           => 'heure',
        'mavka_lieu'            => 'lieu',
        'mavka_format'          => 'format',
        'mavka_places'          => 'nombre_places',
        'mavka_statut'          => 'statut_activite',
        'mavka_lien_inscription' => 'lien_inscription',
        'mavka_texte_bouton'    => 'texte_bouton',
    ];
    if (isset($_POST['mavka_description'])) {
        update_post_meta($post_id, 'description', sanitize_textarea_field($_POST['mavka_description']));
    }
    if (isset($_POST['mavka_categorie']) && is_array($_POST['mavka_categorie'])) {
        $categ_clean = array_map('sanitize_text_field', $_POST['mavka_categorie']);
        update_post_meta($post_id, 'categorie', $categ_clean);
        update_post_meta($post_id, 'categorie_display', implode(', ', $categ_clean));
    } else {
        delete_post_meta($post_id, 'categorie');
        delete_post_meta($post_id, 'categorie_display');
    }
    foreach ($fields as $input_name => $meta_key) {
        if (isset($_POST[$input_name])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$input_name]));
        }
    }
    if (!empty($_POST['mavka_date'])) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $_POST['mavka_date']);
        if ($date_obj) {
            update_post_meta($post_id, 'date_display', $date_obj->format('d/m/Y'));
        }
    }

    // Плашка "Événement ouvert" — рахується автоматично із texte_bouton,
    // нічого вручну обирати не треба. Порожньо для всіх інших типів кнопки —
    // Dynamic Tag на картці, привʼязаний до цього поля, сам "зникне", якщо
    // на елемент додати CSS ":empty { display: none; }" (той самий трюк,
    // яким ми вже ховали зайві переноси рядків).
    if (isset($_POST['mavka_texte_bouton'])) {
        $badge = (sanitize_text_field($_POST['mavka_texte_bouton']) === 'En savoir plus')
            ? 'Événement ouvert'
            : '';
        update_post_meta($post_id, 'badge_ouvert', $badge);
    }

    if (isset($_POST['mavka_activite_nonce']) &&
        wp_verify_nonce($_POST['mavka_activite_nonce'], 'mavka_save_activite')) {
        // за замовчуванням "oui", якщо поле не прийшло взагалі (напр. дуже стара форма в кеші браузера)
        $afficher_val = isset($_POST['mavka_afficher_publiquement']) ? sanitize_text_field($_POST['mavka_afficher_publiquement']) : 'oui';
        update_post_meta($post_id, 'afficher_publiquement', $afficher_val);

        $afficher_accueil_val = isset($_POST['mavka_afficher_accueil']) ? sanitize_text_field($_POST['mavka_afficher_accueil']) : 'oui';
        update_post_meta($post_id, 'afficher_accueil', $afficher_accueil_val);
    }

    global $wpdb;
    $rel_table = $wpdb->prefix . 'mavka_activite_intervenant';
    $wpdb->delete($rel_table, ['activite_id' => $post_id]);

    if (!empty($_POST['mavka_intervenants']) && is_array($_POST['mavka_intervenants'])) {
        $noms = [];
        foreach ($_POST['mavka_intervenants'] as $user_id) {
            mavka_link_intervenant($post_id, (int) $user_id);
            $user = get_userdata((int) $user_id);
            if ($user) $noms[] = $user->display_name;
        }
        update_post_meta($post_id, 'intervenants_noms', implode(', ', $noms));
    } else {
        delete_post_meta($post_id, 'intervenants_noms');
    }
}
add_action('save_post_activite', 'mavka_save_activite_meta');


/* ============================================================
 * 3. НОРМАЛІЗОВАНА ТАБЛИЦЯ ЗВʼЯЗКІВ (Many-to-Many)
 *
 *    wp_mavka_activite_intervenant
 *    ├── id             (PK)
 *    ├── activite_id    (FK → wp_posts.ID)
 *    └── user_id        (FK → wp_users.ID)
 * ============================================================ */
function mavka_create_relation_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'mavka_activite_intervenant';
    $charset_collate = $wpdb->get_charset_collate();

    // ВАЖЛИВО: dbDelta вимагає, щоб PRIMARY KEY був окремим рядком
    // і саме з ДВОМА пробілами після "PRIMARY KEY" — інакше при повторному
    // виклику (кожна активація плагіна) вона намагається повторно
    // оголосити ключ і падає з помилкою "Multiple primary key defined".
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        activite_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_pair (activite_id, user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mavka_create_relation_table');


/**
 * Привʼязати волонтера до активності
 */
function mavka_link_intervenant($activite_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'mavka_activite_intervenant';
    $wpdb->replace($table, [
        'activite_id' => $activite_id,
        'user_id'     => $user_id,
    ]);
}

/**
 * Відвʼязати волонтера від активності
 */
function mavka_unlink_intervenant($activite_id, $user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'mavka_activite_intervenant';
    $wpdb->delete($table, [
        'activite_id' => $activite_id,
        'user_id'     => $user_id,
    ]);
}

/* ============================================================
 * 3bis. ПУБЛІЧНИЙ СПИСОК АКТИВНОСТЕЙ (для головної сторінки сайту)
 *       Окрема, проста функція — без концепції "поточний
 *       користувач". Показує тільки те, що адмін позначив
 *       "Afficher publiquement: Oui".
 *
 *       ПОРЯДОК ВИВОДУ — два блоки:
 *       1) Звичайні події з конкретною датою — від найближчої
 *          до найдальшої (сьогодні/завтра/... спершу).
 *       2) "Відкриті" події ("En savoir plus") і "Регулярні"
 *          ("Événement régulier") — ЗАВЖДИ в кінці списку,
 *          незалежно від Блоку 1. Усередині цього блоку — теж
 *          сортування за датою (найближчі спершу); події зовсім
 *          без дати — у самому кінці, тоді за назвою.
 * ============================================================ */

/**
 * Дата відсічення для "минулих" подій на публічному титулі.
 * За задумом: подія ВЧОРАШНЯ (-1 день) ще показується, а
 * ПОЗАПОЗАВЧОРАШНЯ (-2 дні) і старіша — вже ні. Тобто показуємо
 * все, де date >= (сьогодні - 1 день).
 * current_time() враховує часовий пояс сайту (Réglages → Général),
 * а не голий час сервера — так дата "сьогодні" завжди правильна.
 */
function mavka_date_limite_activites_publiques() {
    $aujourdhui = current_time('Y-m-d');
    return date('Y-m-d', strtotime($aujourdhui . ' -1 day'));
}

/**
 * Типи кнопки, які завжди "виштовхуються" в кінець списку —
 * незалежно від дати (бо в них немає сенсу "найближча дата":
 * це або постійно відкрита пропозиція, або регулярна подія).
 */
function mavka_types_bouton_toujours_en_fin() {
    return ['En savoir plus', 'Événement régulier'];
}

/**
 * СПІЛЬНИЙ сортувальник — використовується і титульною сторінкою
 * (activites_publiques_query), і особистим простором викладача
 * (mes_activites_query), щоб порядок видачі був ІДЕНТИЧНИЙ у двох
 * місцях, а не два окремих алгоритми, які з часом розходяться.
 *
 * На вхід — довільний набір ID Activités (уже відфільтрований за
 * бізнес-правилами того місця, де викликається — публічність,
 * привʼязка до викладача тощо). На вихід — той самий набір ID, але
 * впорядкований:
 *   1) Події з конкретною датою (крім "En savoir plus"/"Événement
 *      régulier") — від найближчої до найдальшої.
 *   2) "En savoir plus" / "Événement régulier" / події без дати
 *      взагалі — ЗАВЖДИ в кінці; усередині цього блоку теж за
 *      датою (найближчі спершу), без дати — у самому кінці.
 */
function mavka_order_activite_ids_by_date(array $ids) {
    global $wpdb;
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));

    $ids_datees = $wpdb->get_col($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} mbtn  ON (mbtn.post_id  = p.ID AND mbtn.meta_key  = 'texte_bouton')
        LEFT JOIN {$wpdb->postmeta} mdate ON (mdate.post_id = p.ID AND mdate.meta_key = 'date')
        WHERE p.ID IN ($placeholders)
          AND mdate.meta_value IS NOT NULL AND mdate.meta_value != ''
          AND (mbtn.meta_value IS NULL OR mbtn.meta_value NOT IN ('En savoir plus', 'Événement régulier'))
        ORDER BY mdate.meta_value ASC
    ", $ids));

    $ids_toujours = $wpdb->get_col($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} mbtn  ON (mbtn.post_id  = p.ID AND mbtn.meta_key  = 'texte_bouton')
        LEFT JOIN {$wpdb->postmeta} mdate ON (mdate.post_id = p.ID AND mdate.meta_key = 'date')
        WHERE p.ID IN ($placeholders)
          AND (
                mbtn.meta_value IN ('En savoir plus', 'Événement régulier')
                OR mdate.meta_value IS NULL OR mdate.meta_value = ''
              )
        ORDER BY (mdate.meta_value IS NULL OR mdate.meta_value = ''), mdate.meta_value ASC, p.post_title ASC
    ", $ids));

    $ids_toujours = array_diff($ids_toujours, $ids_datees);

    return array_map('intval', array_merge($ids_datees, $ids_toujours));
}

function mavka_get_activites_publiques() {
    $cutoff = mavka_date_limite_activites_publiques();
    $types_fin = mavka_types_bouton_toujours_en_fin();

    // Блок 1: події з конкретною, ще актуальною датою — сортовані від найближчої
    $activites_datees = get_posts([
        'post_type'      => 'activite',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value',
        'meta_key'       => 'date',
        'order'          => 'ASC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'afficher_publiquement',
                'value'   => 'non',
                'compare' => '!=',
            ],
            [
                'key'     => 'afficher_accueil',
                'value'   => 'non',
                'compare' => '!=',
            ],
            [
                'key'     => 'date',
                'value'   => $cutoff,
                'compare' => '>=',
                'type'    => 'DATE',
            ],
            [
                // "НЕ в списку 'завжди в кінці'" — АБО поле texte_bouton взагалі
                // ще не збережене (типовий випадок для подій, створених до
                // появи цього поля). Без цього OR-обгортання порожнє значення
                // ламало б увесь запит через класичний SQL-капкан
                // "NULL NOT IN (...) = невідомо", а не "так".
                'relation' => 'OR',
                [
                    'key'     => 'texte_bouton',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'texte_bouton',
                    'value'   => $types_fin,
                    'compare' => 'NOT IN',
                ],
            ],
        ],
    ]);

    // Блок 2: "En savoir plus" / "Événement régulier" (+ події без дати взагалі) — завжди в кінці
    $activites_toujours = get_posts([
        'post_type'      => 'activite',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'afficher_publiquement',
                'value'   => 'non',
                'compare' => '!=',
            ],
            [
                'key'     => 'afficher_accueil',
                'value'   => 'non',
                'compare' => '!=',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'texte_bouton',
                    'value'   => $types_fin,
                    'compare' => 'IN',
                ],
                [
                    'key'     => 'date',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'date',
                    'value'   => '',
                    'compare' => '=',
                ],
            ],
        ],
    ]);

    // Уникаємо дублів, якщо подія якимось чином потрапила в обидва блоки
    $ids_datees = wp_list_pluck($activites_datees, 'ID');
    $activites_toujours = array_filter($activites_toujours, function ($a) use ($ids_datees) {
        return !in_array($a->ID, $ids_datees);
    });
    $activites_toujours = array_values($activites_toujours);

    // Сортуємо блок 2 за датою (найближчі спершу), події без дати — в самий кінець
    usort($activites_toujours, function ($a, $b) {
        $date_a = get_post_meta($a->ID, 'date', true);
        $date_b = get_post_meta($b->ID, 'date', true);
        if (empty($date_a) && empty($date_b)) return strcmp(get_the_title($a), get_the_title($b));
        if (empty($date_a)) return 1;  // без дати — в кінець
        if (empty($date_b)) return -1;
        return strcmp($date_a, $date_b); // формат YYYY-MM-DD порівнюється коректно як текст
    });

    return array_merge($activites_datees, $activites_toujours);
}

/* ============================================================
 * 3ter. ФІЛЬТР ДЛЯ ELEMENTOR LOOP GRID "Activités publiques" (ТИТУЛЬНА СТОРІНКА)
 *    Query ID в Elementor: activites_publiques_query
 *
 *    На відміну від mes_activites_query (нижче, розділ 5) — це
 *    НЕ привʼязано до жодного користувача. Показує ВСІМ
 *    відвідувачам сайту (навіть незалогіненим) усі Activités, у
 *    яких поле "Afficher publiquement ?" = Oui (або взагалі не
 *    заповнене), і які ще не "застаріли" (>= дати відсічення —
 *    крім "En savoir plus" / "Événement régulier" / без дати,
 *    вони показуються завжди).
 *
 *    Порядок видачі рахує спільна функція
 *    mavka_order_activite_ids_by_date() (див. розділ 3bis вище) —
 *    та сама, що й для mes_activites_query, щоб два місця сайту
 *    ніколи не розходились у сортуванні.
 * ============================================================ */
add_action('elementor/query/activites_publiques_query', function ($query) {
    global $wpdb;
    $cutoff = mavka_date_limite_activites_publiques();

    // Кандидати: опубліковано, публічно, і (дата ще актуальна АБО
    // це "завжди показувати" тип/без дати — для них дата не має значення)
    $candidate_ids = $wpdb->get_col($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} mpub  ON (mpub.post_id  = p.ID AND mpub.meta_key  = 'afficher_publiquement')
        LEFT JOIN {$wpdb->postmeta} macc  ON (macc.post_id  = p.ID AND macc.meta_key  = 'afficher_accueil')
        LEFT JOIN {$wpdb->postmeta} mbtn  ON (mbtn.post_id  = p.ID AND mbtn.meta_key  = 'texte_bouton')
        LEFT JOIN {$wpdb->postmeta} mdate ON (mdate.post_id = p.ID AND mdate.meta_key = 'date')
        WHERE p.post_type = 'activite'
          AND p.post_status = 'publish'
          AND (mpub.meta_value IS NULL OR mpub.meta_value != 'non')
          AND (macc.meta_value IS NULL OR macc.meta_value != 'non')
          AND (
                mbtn.meta_value IN ('En savoir plus', 'Événement régulier')
                OR mdate.meta_value IS NULL OR mdate.meta_value = ''
                OR mdate.meta_value >= %s
              )
    ", $cutoff));

    $ordered_ids = mavka_order_activite_ids_by_date($candidate_ids);

    $query->set('post_type', 'activite');
    $query->set('post__in', !empty($ordered_ids) ? $ordered_ids : [0]);
    $query->set('orderby', 'post__in'); // ЗБЕРІГАЄМО саме цей, вручну порахований порядок
});

/**
 * Отримати активності конкретного користувача.
 *
 * ВАЖЛИВО: усі, хто має доступ "бачити все" (Administrator АБО
 * нова роль MAVKA Admin — див. mavka_user_can_manage_all_activites())
 * бачать ОДРАЗУ ВСІ активності, без потреби відмічати себе в
 * "Volontaire(s) responsable(s)" кожної події вручну. Для звичайних
 * волонтерів/викладачів — тільки ті активності, до яких їх привʼязано
 * через таблицю звʼязків.
 */
function mavka_get_activites_for_user($user_id) {
    global $wpdb;

    // Повний доступ для Administrator і MAVKA Admin
    if (mavka_user_can_manage_all_activites($user_id)) {
        return get_posts([
            'post_type'      => 'activite',
            'post_status'    => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'date',
            'order'          => 'ASC',
        ]);
    }

    $table = $wpdb->prefix . 'mavka_activite_intervenant';

    $activite_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT activite_id FROM $table WHERE user_id = %d", $user_id)
    );

    if (empty($activite_ids)) {
        return [];
    }

    return get_posts([
        'post_type' => 'activite',
        'post__in'  => $activite_ids,
        'orderby'   => 'meta_value',
        'meta_key'  => 'date',
        'order'     => 'ASC',
    ]);
}


/* ============================================================
 * 4. ШОРТКОД ДЛЯ ВИВОДУ НА СТОРІНЦІ "Mes Activités" (Elementor)
 *    Використання в Text Editor віджеті: [mavka_mes_activites]
 * ============================================================ */
function mavka_mes_activites_shortcode() {
    if (!is_user_logged_in()) {
        return '';
    }

    $activites = mavka_get_activites_for_user(get_current_user_id());

    if (empty($activites)) {
        return "<p>Vous n'avez pas encore d'activité programmée.<br>
                Vous souhaitez proposer un atelier ? Proposez une date et un horaire,
                MAVKA s'occupe de l'organisation.</p>";
    }

    $html = '<div class="mavka-activites-list">';
    foreach ($activites as $activite) {
        $date   = get_post_meta($activite->ID, 'date', true);
        $heure  = get_post_meta($activite->ID, 'heure', true);
        $lieu   = get_post_meta($activite->ID, 'lieu', true);
        $statut = get_post_meta($activite->ID, 'statut_activite', true);
        $desc   = get_post_meta($activite->ID, 'description', true);
        $lien   = get_post_meta($activite->ID, 'lien_inscription', true);

        $html .= '<div class="mavka-activite-card">';
        $html .= '<h3>' . esc_html(get_the_title($activite)) . '</h3>';
        $html .= '<p>' . esc_html($date) . ' — ' . esc_html($heure) . '</p>';
        $html .= '<p>' . esc_html($lieu) . '</p>';
        if ($desc) $html .= '<p>' . esc_html($desc) . '</p>';
        $html .= '<p><em>' . esc_html($statut) . '</em></p>';

        if ($lien) $html .= '<p><a href="' . esc_url($lien) . '" target="_blank">Préinscription</a></p>';

        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('mavka_mes_activites', 'mavka_mes_activites_shortcode');


/* ============================================================
 * 5. ФІЛЬТР ДЛЯ ELEMENTOR LOOP GRID "Mes Activités"
 *    Query ID в Elementor: mes_activites_query
 *
 *    Хто що бачить:
 *      - Administrator / MAVKA Admin — усі Activités (як і раніше).
 *      - Звичайний викладач/волонтер — свої прив'язані Activités
 *        (через таблицю звʼязків) ТА ДОДАТКОВО всі "En savoir plus"
 *        (відкриті події), навіть якщо він до них не привʼязаний —
 *        щоб міг сам приєднатись/зголоситись і прорекламувати себе.
 *        "Événement régulier" сюди НЕ розширюємо (це чиясь власна
 *        регулярна активність, а не відкритий заклик до участі) —
 *        такі й далі бачить тільки той, до кого прив'язано.
 *
 *    ПОРЯДОК ВИДАЧІ: та сама спільна логіка, що й на титульній
 *    сторінці (mavka_order_activite_ids_by_date(), розділ 3bis) —
 *    найближчі за датою спершу, "En savoir plus"/"Événement
 *    régulier"/без дати — завжди в кінці. Це навмисно НЕ приховує
 *    минулі події (на відміну від титулу) — викладачу вони й далі
 *    потрібні, наприклад, для "Mes Rapports".
 * ============================================================ */
add_action('elementor/query/mes_activites_query', function ($query) {
    if (!is_user_logged_in()) {
        $query->set('post__in', [0]); // нікого не показуємо, якщо не залогінені
        return;
    }

    global $wpdb;
    $current_user_id = get_current_user_id();

    // ВАЖЛИВО: тут звертаємось напряму до бази через $wpdb, а НЕ через get_posts()/get_users().
    // get_posts() створює власний, окремий WP_Query всередині — а ми зараз і так
    // виконуємось "всередині" запиту, який будує сам Loop Grid. Два вкладені запити
    // одночасно плутають внутрішній стан WordPress і спричиняють критичну помилку.
    // Пряме звернення до бази — безпечніше саме в цьому місці коду.
    if (mavka_user_can_manage_all_activites($current_user_id)) {
        $ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'activite' AND post_status IN ('publish','draft','pending','future')"
        );
    } else {
        $table = $wpdb->prefix . 'mavka_activite_intervenant';
        $ids = $wpdb->get_col(
            $wpdb->prepare("SELECT activite_id FROM $table WHERE user_id = %d", $current_user_id)
        );

        // + усі відкриті "En savoir plus" — навіть не привʼязані до цього викладача,
        // окрім тих, що позначені "Afficher publiquement: Non"
        $ids_ouvertes = $wpdb->get_col("
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} mbtn ON (mbtn.post_id = p.ID AND mbtn.meta_key = 'texte_bouton')
            LEFT JOIN {$wpdb->postmeta} mpub ON (mpub.post_id = p.ID AND mpub.meta_key = 'afficher_publiquement')
            WHERE p.post_type = 'activite'
              AND p.post_status = 'publish'
              AND mbtn.meta_value = 'En savoir plus'
              AND (mpub.meta_value IS NULL OR mpub.meta_value != 'non')
        ");
        $ids = array_merge($ids, $ids_ouvertes);
    }

    $ordered_ids = mavka_order_activite_ids_by_date($ids);

    $query->set('post_type', 'activite');
    $query->set('post__in', !empty($ordered_ids) ? $ordered_ids : [0]);
    $query->set('orderby', 'post__in'); // ЗБЕРІГАЄМО саме цей, вручну порахований порядок
});


/* ============================================================
 * 6. АВТОМАТИЧНЕ ОЧИЩЕННЯ КЕШУ LITESPEED ПРИ ЗБЕРЕЖЕННІ АКТИВНОСТІ
 *    Без цього сторінка "Mon Espace" може показувати старі дані
 *    ще якийсь час після зміни списку волонтерів/статусу тощо.
 *    Якщо LiteSpeed Cache не встановлено — хук просто нічого не робить.
 * ============================================================ */
function mavka_purge_litespeed_cache($post_id) {
    if (has_action('litespeed_purge_post')) {
        do_action('litespeed_purge_post', $post_id);
    }
    if (function_exists('do_action')) {
        do_action('litespeed_purge_all'); // на випадок, якщо сторінка "Mon Espace" кешується окремо від самого посту
    }
}
add_action('save_post_activite', 'mavka_purge_litespeed_cache', 20);


/* ============================================================
 * 7. ЗАБОРОНА КЕШУВАННЯ ПЕРСОНАЛЬНИХ СТОРІНОК ВИКЛАДАЧІВ
 *
 *    "Сторінка викладача" (author-архів /author/username/) —
 *    ПЕРСОНАЛЬНА: для різних людей вона показує різний вміст
 *    (Événements, Rapports тощо). Кеш-плагіни (LiteSpeed і
 *    подібні) за замовчуванням кешують сторінку ЗА URL —
 *    тобто перший, хто відкриє /author/ivan/ (навіть
 *    незалогінений відвідувач чи бот), "заморожує" HTML для
 *    ВСІХ наступних відвідувачів цієї ж адреси, включно з
 *    самим Іваном і адміністратором.
 *
 *    Це і є найімовірніша причина "порожнього списку" у
 *    адміна та "зламаного" вигляду в викладачів — усі бачать
 *    один і той самий закешований (застарілий) HTML.
 *
 *    Рішення: explicitно забороняємо кеш на цих сторінках.
 * ============================================================ */
function mavka_no_cache_on_author_pages() {
    if (is_author()) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true); // розпізнається LiteSpeed, WP Rocket, W3TC тощо
        }
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        // Явно кажемо LiteSpeed не кешувати цей конкретний запит
        do_action('litespeed_control_set_nocache', 'mavka: page personnelle enseignant·e');
    }
}
add_action('template_redirect', 'mavka_no_cache_on_author_pages');

/**
 * ВАЖЛИВО (додатково зробіть в адмінці):
 * У налаштуваннях LiteSpeed Cache → Cache → Excludes → "Do Not Cache URIs"
 * додайте: /author/.*
 * Це підстрахує на випадок, якщо якийсь інший кеш-шар (сторінковий
 * кеш на рівні сервера/CDN) не читає DONOTCACHEPAGE.
 */


/* ============================================================
 * 8. ШОРТКОД ДІАГНОСТИКИ (тимчасовий, тільки для адміністратора)
 *    Використання: [mavka_debug_activites]
 *    Покаже: хто зараз залогінений, чи є в нього manage_options,
 *    скільки Activité прив'язано, і які саме ID повертає фільтр.
 *    Поставте цей шорткод на сторінці викладача (Text Editor
 *    віджет в Elementor), щоб побачити реальні дані наживо —
 *    потім просто видаліть віджет, коли проблему знайдено.
 * ============================================================ */
function mavka_debug_activites_shortcode() {
    if (!mavka_user_can_manage_all_activites(get_current_user_id())) {
        return ''; // видно тільки Administrator/MAVKA Admin, щоб не світити дані іншим
    }

    global $wpdb;
    $uid = get_current_user_id();
    $user = wp_get_current_user();
    $table = $wpdb->prefix . 'mavka_activite_intervenant';

    $linked_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT activite_id FROM $table WHERE user_id = %d", $uid)
    );

    $all_activites = get_posts(['post_type' => 'activite', 'posts_per_page' => -1, 'fields' => 'ids']);

    $computed = mavka_get_activites_for_user($uid);
    $computed_ids = wp_list_pluck($computed, 'ID');

    ob_start();
    ?>
    <div style="background:#fff3cd;border:1px solid #d4a017;padding:12px;margin:12px 0;font-family:monospace;font-size:13px;">
        <strong>MAVKA DEBUG</strong><br>
        user_id (get_current_user_id): <?php echo esc_html($uid); ?><br>
        login: <?php echo esc_html($user->user_login); ?><br>
        roles: <?php echo esc_html(implode(', ', $user->roles)); ?><br>
        user_can(manage_options): <?php echo user_can($uid, 'manage_options') ? 'OUI' : 'NON'; ?><br>
        user_can(mavka_manage_activites): <?php echo user_can($uid, 'mavka_manage_activites') ? 'OUI' : 'NON'; ?><br>
        is_user_logged_in(): <?php echo is_user_logged_in() ? 'OUI' : 'NON'; ?><br>
        Toutes les Activités (CPT) existantes: <?php echo count($all_activites); ?> — IDs: <?php echo esc_html(implode(', ', $all_activites)); ?><br>
        Activités liées à CET utilisateur (table relation): <?php echo count($linked_ids); ?> — IDs: <?php echo esc_html(implode(', ', $linked_ids)); ?><br>
        Résultat de mavka_get_activites_for_user(): <?php echo count($computed_ids); ?> — IDs: <?php echo esc_html(implode(', ', $computed_ids)); ?><br>
        is_author(): <?php echo is_author() ? 'OUI' : 'NON'; ?><br>
        DONOTCACHEPAGE: <?php echo defined('DONOTCACHEPAGE') && DONOTCACHEPAGE ? 'OUI' : 'NON'; ?><br>
        <br>
        <strong>--- Titre : valeurs brutes du champ "date" (pour vérifier le format) ---</strong><br>
        <?php foreach ($all_activites as $aid) :
            $raw_date = get_post_meta($aid, 'date', true);
            $raw_btn  = get_post_meta($aid, 'texte_bouton', true);
            $raw_badge = get_post_meta($aid, 'badge_ouvert', true);
            $raw_accueil = get_post_meta($aid, 'afficher_accueil', true);
            ?>
            #<?php echo esc_html($aid); ?> «<?php echo esc_html(get_the_title($aid)); ?>» —
            date="<?php echo esc_html($raw_date); ?>" —
            texte_bouton="<?php echo esc_html($raw_btn); ?>" —
            badge_ouvert="<?php echo esc_html($raw_badge); ?>" —
            afficher_accueil="<?php echo esc_html($raw_accueil !== '' ? $raw_accueil : 'oui (par défaut)'); ?>"<br>
        <?php endforeach; ?>
        <br>
        <strong>--- Ordre calculé pour la page d'accueil (mavka_get_activites_publiques) ---</strong><br>
        <?php
        $publiques = mavka_get_activites_publiques();
        foreach ($publiques as $i => $a) {
            echo ($i + 1) . '. #' . esc_html($a->ID) . ' «' . esc_html(get_the_title($a)) . '» — date=' . esc_html(get_post_meta($a->ID, 'date', true)) . '<br>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('mavka_debug_activites', 'mavka_debug_activites_shortcode');


/* ============================================================
 * 9. PAGE ADMIN "Volontaires" — profil et documents des volontaires
 *
 *    ІДЕЯ: волонтер — це вже наявний користувач WordPress (той
 *    самий, кого можна привʼязати до Activité через "Volontaire(s)
 *    responsable(s)"). Ця сторінка ДОДАЄ до кожного такого
 *    користувача набір власних полів MAVKA (domaine, charte
 *    signée, CV...), яких немає "з коробки" в WordPress.
 *
 *    Зберігання: usermeta, з префіксом "mavka_" — щоб ніколи не
 *    конфліктувати з полями інших плагінів на тому самому
 *    користувачі.
 *
 *    Доступ: тільки mavka_manage_activites (Administrator, MAVKA
 *    Admin) — та сама можливість, що й для керування Activités.
 *    Сторінка з'являється в меню "Activités → Volontaires".
 * ============================================================ */

/**
 * Єдиний список простих текстових/textarea полів — одне джерело
 * правди для форми редагування і збереження, щоб не дублювати
 * перелік у двох місцях і не забути десь одне поле.
 */
function mavka_volontaire_champs_texte() {
    return [
        'mavka_domaine'              => ['label' => 'Domaine',                                    'type' => 'text'],
        'mavka_secteur_intervention' => ['label' => "Secteur d'intervention",                      'type' => 'text'],
        'mavka_projet'               => ['label' => 'Projet',                                      'type' => 'text'],
        'mavka_competences'          => ['label' => 'Compétences / spécialités',                   'type' => 'textarea'],
        'mavka_disponibilites'       => ['label' => 'Disponibilités',                              'type' => 'textarea'],
        'mavka_notes_internes'       => ['label' => 'Notes internes (visibles admin uniquement)',  'type' => 'textarea'],
    ];
}

function mavka_volontaires_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=activite',
        'Volontaires',
        'Volontaires',
        'mavka_manage_activites',
        'mavka-volontaires',
        'mavka_volontaires_page_html'
    );
}
add_action('admin_menu', 'mavka_volontaires_admin_menu');

function mavka_volontaires_page_html() {
    if (!current_user_can('mavka_manage_activites')) {
        wp_die("Vous n'avez pas les droits pour accéder à cette page.");
    }

    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Fiche volontaire enregistrée.</p></div>';
    }

    $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

    if ($user_id > 0) {
        mavka_volontaires_page_edition($user_id);
    } else {
        mavka_volontaires_page_liste();
    }
}

function mavka_volontaires_page_liste() {
    $users = get_users(['orderby' => 'display_name']);
    ?>
    <div class="wrap">
        <h1>Volontaires</h1>
        <p>Liste de tous les comptes WordPress. Cliquez sur « Modifier » pour renseigner les informations MAVKA (domaine, charte, CV...).</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>E-mail</th>
                    <th>Domaine</th>
                    <th>Charte signée</th>
                    <th>Contrat signé</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) :
                    $domaine  = get_user_meta($user->ID, 'mavka_domaine', true);
                    $charte   = get_user_meta($user->ID, 'mavka_charte_signee', true) === 'oui';
                    $contrat  = get_user_meta($user->ID, 'mavka_contrat_signee', true) === 'oui';
                    $edit_url = esc_url(add_query_arg(
                        ['page' => 'mavka-volontaires', 'user_id' => $user->ID],
                        admin_url('edit.php?post_type=activite')
                    ));
                ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo esc_html($domaine); ?></td>
                        <td><?php echo $charte ? '✅' : '—'; ?></td>
                        <td><?php echo $contrat ? '✅' : '—'; ?></td>
                        <td><a href="<?php echo $edit_url; ?>" class="button button-small">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function mavka_volontaires_page_edition($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        echo '<div class="wrap"><p>Utilisateur introuvable.</p></div>';
        return;
    }

    $liste_url = esc_url(admin_url('edit.php?post_type=activite&page=mavka-volontaires'));

    $charte   = get_user_meta($user_id, 'mavka_charte_signee', true) === 'oui';
    $contrat  = get_user_meta($user_id, 'mavka_contrat_signee', true) === 'oui';
    $cv_id    = (int) get_user_meta($user_id, 'mavka_cv_id', true);
    $docs_ids = get_user_meta($user_id, 'mavka_documents_ids', true);
    if (!is_array($docs_ids)) $docs_ids = [];
    ?>
    <div class="wrap">
        <h1>Volontaire : <?php echo esc_html($user->display_name); ?></h1>
        <p><a href="<?php echo $liste_url; ?>">&larr; Retour à la liste</a></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('mavka_save_volontaire_' . $user_id, 'mavka_volontaire_nonce'); ?>
            <input type="hidden" name="action" value="mavka_save_volontaire">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">

            <table class="form-table">
                <?php foreach (mavka_volontaire_champs_texte() as $meta_key => $champ) :
                    $valeur = get_user_meta($user_id, $meta_key, true);
                ?>
                    <tr>
                        <th><label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($champ['label']); ?></label></th>
                        <td>
                            <?php if ($champ['type'] === 'textarea') : ?>
                                <textarea id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" rows="4" class="large-text"><?php echo esc_textarea($valeur); ?></textarea>
                            <?php else : ?>
                                <input type="text" id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($valeur); ?>" class="regular-text">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <th>Charte signée</th>
                    <td><label><input type="checkbox" name="mavka_charte_signee" value="oui" <?php checked($charte); ?>> Oui</label></td>
                </tr>
                <tr>
                    <th>Contrat signé</th>
                    <td><label><input type="checkbox" name="mavka_contrat_signee" value="oui" <?php checked($contrat); ?>> Oui</label></td>
                </tr>

                <tr>
                    <th>CV</th>
                    <td>
                        <?php if ($cv_id && get_attached_file($cv_id)) : ?>
                            <p>
                                <a href="<?php echo esc_url(wp_get_attachment_url($cv_id)); ?>" target="_blank"><?php echo esc_html(basename(get_attached_file($cv_id))); ?></a>
                                &nbsp; <label><input type="checkbox" name="mavka_cv_supprimer" value="1"> Supprimer</label>
                            </p>
                        <?php endif; ?>
                        <input type="file" name="mavka_cv" accept=".pdf,.doc,.docx">
                    </td>
                </tr>

                <tr>
                    <th>Documents professionnels</th>
                    <td>
                        <?php if (!empty($docs_ids)) : ?>
                            <ul>
                                <?php foreach ($docs_ids as $doc_id) :
                                    $doc_id = (int) $doc_id;
                                    if (!$doc_id || !get_attached_file($doc_id)) continue;
                                ?>
                                    <li>
                                        <a href="<?php echo esc_url(wp_get_attachment_url($doc_id)); ?>" target="_blank"><?php echo esc_html(basename(get_attached_file($doc_id))); ?></a>
                                        &nbsp; <label><input type="checkbox" name="mavka_documents_supprimer[]" value="<?php echo esc_attr($doc_id); ?>"> Supprimer</label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <input type="file" name="mavka_documents[]" multiple>
                        <p class="description">Les nouveaux fichiers s'ajoutent à la liste ci-dessus (ils ne remplacent pas les documents déjà présents).</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Enregistrer'); ?>
        </form>
    </div>
    <?php
}

function mavka_save_volontaire_handler() {
    if (!current_user_can('mavka_manage_activites')) {
        wp_die("Vous n'avez pas les droits pour effectuer cette action.");
    }

    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    if (!$user_id || !isset($_POST['mavka_volontaire_nonce']) ||
        !wp_verify_nonce($_POST['mavka_volontaire_nonce'], 'mavka_save_volontaire_' . $user_id)) {
        wp_die('Vérification de sécurité échouée (nonce invalide). Revenez en arrière et réessayez.');
    }

    if (!get_userdata($user_id)) {
        wp_die('Utilisateur introuvable.');
    }

    foreach (mavka_volontaire_champs_texte() as $meta_key => $champ) {
        if (isset($_POST[$meta_key])) {
            $valeur = ($champ['type'] === 'textarea')
                ? sanitize_textarea_field($_POST[$meta_key])
                : sanitize_text_field($_POST[$meta_key]);
            update_user_meta($user_id, $meta_key, $valeur);
        }
    }

    update_user_meta($user_id, 'mavka_charte_signee', isset($_POST['mavka_charte_signee']) ? 'oui' : 'non');
    update_user_meta($user_id, 'mavka_contrat_signee', isset($_POST['mavka_contrat_signee']) ? 'oui' : 'non');

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // CV : suppression si demandée, puis remplacement si un nouveau fichier est envoyé
    if (!empty($_POST['mavka_cv_supprimer'])) {
        $ancien_cv_id = (int) get_user_meta($user_id, 'mavka_cv_id', true);
        if ($ancien_cv_id) wp_delete_attachment($ancien_cv_id, true);
        delete_user_meta($user_id, 'mavka_cv_id');
    }
    if (!empty($_FILES['mavka_cv']['name'])) {
        $nouveau_cv_id = media_handle_upload('mavka_cv', 0);
        if (!is_wp_error($nouveau_cv_id)) {
            $ancien_cv_id = (int) get_user_meta($user_id, 'mavka_cv_id', true);
            if ($ancien_cv_id) wp_delete_attachment($ancien_cv_id, true);
            update_user_meta($user_id, 'mavka_cv_id', $nouveau_cv_id);
        }
    }

    // Documents professionnels : suppression des cochés, puis ajout des nouveaux fichiers envoyés
    $docs_ids = get_user_meta($user_id, 'mavka_documents_ids', true);
    if (!is_array($docs_ids)) $docs_ids = [];

    if (!empty($_POST['mavka_documents_supprimer']) && is_array($_POST['mavka_documents_supprimer'])) {
        $a_supprimer = array_map('intval', $_POST['mavka_documents_supprimer']);
        foreach ($a_supprimer as $doc_id) {
            wp_delete_attachment($doc_id, true);
        }
        $docs_ids = array_diff($docs_ids, $a_supprimer);
    }

    if (!empty($_FILES['mavka_documents']) && !empty($_FILES['mavka_documents']['name'][0])) {
        $nb_fichiers = count($_FILES['mavka_documents']['name']);
        // media_handle_upload() ne comprend qu'un seul fichier à la fois (format
        // $_FILES classique) — on reconstruit donc temporairement $_FILES['...']
        // pour chaque fichier de la liste envoyée en une fois par le navigateur.
        for ($i = 0; $i < $nb_fichiers; $i++) {
            if (empty($_FILES['mavka_documents']['name'][$i])) continue;
            $_FILES['mavka_documents_single'] = [
                'name'     => $_FILES['mavka_documents']['name'][$i],
                'type'     => $_FILES['mavka_documents']['type'][$i],
                'tmp_name' => $_FILES['mavka_documents']['tmp_name'][$i],
                'error'    => $_FILES['mavka_documents']['error'][$i],
                'size'     => $_FILES['mavka_documents']['size'][$i],
            ];
            $doc_id = media_handle_upload('mavka_documents_single', 0);
            if (!is_wp_error($doc_id)) {
                $docs_ids[] = $doc_id;
            }
        }
        unset($_FILES['mavka_documents_single']);
    }

    update_user_meta($user_id, 'mavka_documents_ids', array_values(array_unique(array_map('intval', $docs_ids))));

    wp_safe_redirect(add_query_arg(
        ['page' => 'mavka-volontaires', 'user_id' => $user_id, 'updated' => 1],
        admin_url('edit.php?post_type=activite')
    ));
    exit;
}
add_action('admin_post_mavka_save_volontaire', 'mavka_save_volontaire_handler');


/* ============================================================
 * 10. PRIVʼAZKA D'UNE PAGE (Page WordPress) À UN VOLONTAIRE
 *
 *    Chaque page publique d'un intervenant (ex. Hanna Sokha) a besoin
 *    de savoir "à quel utilisateur WordPress j'appartiens", pour que
 *    le Loop Grid Elementor de cette page puisse n'afficher QUE les
 *    Activités liées à CETTE personne (et pas toutes les Activités
 *    du site). On stocke ça dans un simple meta-box sur les Pages —
 *    même logique que pour les Activités, sans dépendance externe.
 * ============================================================ */
function mavka_page_intervenant_metabox() {
    add_meta_box(
        'mavka_page_intervenant',
        'MAVKA — Volontaire associé à cette page',
        'mavka_page_intervenant_metabox_html',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mavka_page_intervenant_metabox');

function mavka_page_intervenant_metabox_html($post) {
    wp_nonce_field('mavka_save_page_intervenant', 'mavka_page_intervenant_nonce');
    $selected = (int) get_post_meta($post->ID, 'mavka_page_intervenant_user_id', true);
    $users = get_users(['orderby' => 'display_name']);
    ?>
    <p>
        <label for="mavka_page_intervenant_user_id"><strong>Volontaire</strong></label><br>
        <select name="mavka_page_intervenant_user_id" id="mavka_page_intervenant_user_id" style="width:100%">
            <option value="">— Aucun —</option>
            <?php foreach ($users as $user) : ?>
                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected, $user->ID); ?>>
                    <?php echo esc_html($user->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p><em>Utilisé par le Loop Grid Elementor "activites_intervenant_query" (voir plus bas) pour afficher automatiquement, sur cette page, les Activités publiques liées à ce volontaire — sans avoir à retaper les dates/lieux/liens HelloAsso.</em></p>
    <?php
}

function mavka_save_page_intervenant_meta($post_id) {
    if (!isset($_POST['mavka_page_intervenant_nonce']) ||
        !wp_verify_nonce($_POST['mavka_page_intervenant_nonce'], 'mavka_save_page_intervenant')) {
        return;
    }
    if (isset($_POST['mavka_page_intervenant_user_id'])) {
        $user_id = (int) $_POST['mavka_page_intervenant_user_id'];
        if ($user_id > 0) {
            update_post_meta($post_id, 'mavka_page_intervenant_user_id', $user_id);
        } else {
            delete_post_meta($post_id, 'mavka_page_intervenant_user_id');
        }
    }
}
add_action('save_post_page', 'mavka_save_page_intervenant_meta');


/* ============================================================
 * 11. ACTIVITÉS PUBLIQUES D'UN VOLONTAIRE PRÉCIS (fonction partagée)
 *
 *    Même filtre que mavka_get_activites_publiques() (publique,
 *    pas encore périmée, ou "toujours affichée" type En savoir
 *    plus/Événement régulier) — mais restreint aux Activités liées
 *    à CE user_id précis via la table de liaison. Le tri final
 *    réutilise mavka_order_activite_ids_by_date() (section 3bis),
 *    pour rester cohérent avec le reste du site.
 * ============================================================ */
function mavka_get_activites_publiques_for_user($user_id) {
    global $wpdb;
    $cutoff = mavka_date_limite_activites_publiques();

    $table = $wpdb->prefix . 'mavka_activite_intervenant';
    $linked_ids = $wpdb->get_col(
        $wpdb->prepare("SELECT activite_id FROM $table WHERE user_id = %d", $user_id)
    );

    if (empty($linked_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($linked_ids), '%d'));

    $candidate_ids = $wpdb->get_col($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} mpub  ON (mpub.post_id  = p.ID AND mpub.meta_key  = 'afficher_publiquement')
        LEFT JOIN {$wpdb->postmeta} mbtn  ON (mbtn.post_id  = p.ID AND mbtn.meta_key  = 'texte_bouton')
        LEFT JOIN {$wpdb->postmeta} mdate ON (mdate.post_id = p.ID AND mdate.meta_key = 'date')
        WHERE p.ID IN ($placeholders)
          AND p.post_type = 'activite'
          AND p.post_status = 'publish'
          AND (mpub.meta_value IS NULL OR mpub.meta_value != 'non')
          AND (
                mbtn.meta_value IN ('En savoir plus', 'Événement régulier')
                OR mdate.meta_value IS NULL OR mdate.meta_value = ''
                OR mdate.meta_value >= %s
              )
    ", array_merge($linked_ids, [$cutoff])));

    return mavka_order_activite_ids_by_date($candidate_ids);
}

/* ============================================================
 * 12. FILTRE POUR ELEMENTOR LOOP GRID "Ateliers de ce volontaire"
 *    Query ID dans Elementor : activites_intervenant_query
 *
 *    À utiliser sur la page publique d'un intervenant (ex. page
 *    "Hanna Sokha"), juste après la section "Ce que je propose".
 *    Le volontaire concerné est déterminé par le champ renseigné
 *    dans le meta-box "Volontaire associé à cette page" (section 10
 *    ci-dessus) — pas besoin de retaper quoi que ce soit ici, tout
 *    vient automatiquement de la table de liaison Activité ↔ Volontaire.
 * ============================================================ */
add_action('elementor/query/activites_intervenant_query', function ($query) {
    $page_id = get_queried_object_id();
    $user_id = (int) get_post_meta($page_id, 'mavka_page_intervenant_user_id', true);

    $ordered_ids = $user_id ? mavka_get_activites_publiques_for_user($user_id) : [];

    $query->set('post_type', 'activite');
    $query->set('post__in', !empty($ordered_ids) ? $ordered_ids : [0]);
    $query->set('orderby', 'post__in'); // ЗБЕРІГАЄМО порядок, порахований вручну
});
