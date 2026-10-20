<?php
// Envoi d'email via SMTP (Gmail) — remplace la fonction mail() de PHP, peu fiable sur
// hébergement mutualisé : souvent mal configurée côté serveur, ou le message part mais atterrit
// en spam faute d'enregistrement SPF/DKIM pour le domaine du site. Passer par le SMTP de Gmail
// (avec un "mot de passe d'application") envoie le message depuis une vraie adresse Gmail,
// que Gmail lui-même ne va pas suspecter.
//
// Implémentation minimale sans dépendance externe (pas de Composer/vendor sur ce déploiement
// Git direct) — juste assez pour un envoi simple type formulaire de contact : une connexion,
// STARTTLS, AUTH LOGIN, un message, terminé.

// Une ligne de réponse SMTP fait "CODE texte\r\n" (dernière ligne d'une réponse) ou
// "CODE-texte\r\n" (ligne intermédiaire, à continuer de lire) — jamais l'inverse.
function mavka_smtp_lire_reponse($socket): string {
    $reponse = '';
    do {
        $ligne = fgets($socket, 515);
        if ($ligne === false) break;
        $reponse .= $ligne;
    } while (isset($ligne[3]) && $ligne[3] === '-');
    return $reponse;
}

function mavka_smtp_commande($socket, string $commande): string {
    fwrite($socket, $commande . "\r\n");
    return mavka_smtp_lire_reponse($socket);
}

function mavka_smtp_code(string $reponse): string {
    return substr($reponse, 0, 3);
}

// Retourne true si l'email est parti, false sinon (jamais d'exception — un échec d'envoi ne
// doit pas empêcher d'enregistrer le message en base, voir contact-handler.php).
// $erreur (optionnel, par référence) : rempli avec la raison de l'échec — jusqu'ici seulement
// visible dans error_log() du serveur, inaccessible sur certains hébergements mutualisés
// (ex. Hostinger ne l'expose pas dans hPanel). contact-handler.php l'enregistre maintenant
// directement dans messages_contact pour qu'elle soit visible dans /admin/messages.php, sans
// avoir besoin d'accès aux logs serveur.
function mavka_smtp_envoyer(string $to, string $subject, string $body, string $replyTo = '', ?string &$erreur = null): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        $erreur = 'SMTP non configuré (SMTP_HOST/SMTP_USER/SMTP_PASS absents de config.php)';
        return false;
    }
    $host = SMTP_HOST;
    $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = defined('SMTP_FROM') ? SMTP_FROM : $user;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'MAVKA';
    $hostnameLocal = defined('SITE_URL') ? (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost') : 'localhost';

    $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 15);
    if (!$socket) {
        $erreur = "Connexion échouée à $host:$port — $errstr (l'hébergeur bloque peut-être les connexions SMTP sortantes)";
        error_log("SMTP: $erreur");
        return false;
    }
    stream_set_timeout($socket, 15);

    try {
        mavka_smtp_lire_reponse($socket); // bannière du serveur (220 ...)

        mavka_smtp_commande($socket, "EHLO $hostnameLocal");

        $reponse = mavka_smtp_commande($socket, 'STARTTLS');
        if (mavka_smtp_code($reponse) !== '220') {
            $erreur = 'STARTTLS refusé — ' . trim($reponse);
            error_log("SMTP: $erreur");
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $erreur = 'La négociation TLS a échoué';
            error_log("SMTP: $erreur");
            return false;
        }
        // Un serveur SMTP peut changer ce qu'il annonce une fois la connexion chiffrée —
        // reEHLO obligatoire après STARTTLS.
        mavka_smtp_commande($socket, "EHLO $hostnameLocal");

        $reponse = mavka_smtp_commande($socket, 'AUTH LOGIN');
        if (mavka_smtp_code($reponse) !== '334') {
            $erreur = 'AUTH LOGIN non proposé — ' . trim($reponse);
            error_log("SMTP: $erreur");
            return false;
        }
        mavka_smtp_commande($socket, base64_encode($user));
        $reponse = mavka_smtp_commande($socket, base64_encode($pass));
        if (mavka_smtp_code($reponse) !== '235') {
            $erreur = 'Authentification refusée (vérifier SMTP_USER/SMTP_PASS — le mot de passe d\'application Gmail) — ' . trim($reponse);
            error_log("SMTP: $erreur");
            return false;
        }

        mavka_smtp_commande($socket, "MAIL FROM:<$from>");
        $reponse = mavka_smtp_commande($socket, "RCPT TO:<$to>");
        if (mavka_smtp_code($reponse) !== '250') {
            $erreur = 'Destinataire refusé — ' . trim($reponse);
            error_log("SMTP: $erreur");
            return false;
        }
        $reponse = mavka_smtp_commande($socket, 'DATA');
        if (mavka_smtp_code($reponse) !== '354') {
            $erreur = 'DATA refusé — ' . trim($reponse);
            error_log("SMTP: $erreur");
            return false;
        }

        $entetes = [
            'From: ' . mb_encode_mimeheader($fromName) . " <$from>",
            "To: <$to>",
            'Subject: ' . mb_encode_mimeheader($subject),
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $hostnameLocal . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        if ($replyTo !== '') {
            $entetes[] = "Reply-To: <$replyTo>";
        }
        // Une ligne du corps qui commence par un point serait sinon prise pour le point seul
        // qui termine le message SMTP (protocole DATA) — on la double, convention SMTP standard.
        $corpsEchappe = preg_replace('/^\./m', '..', $body);
        $reponse = mavka_smtp_commande($socket, implode("\r\n", $entetes) . "\r\n\r\n" . $corpsEchappe . "\r\n.");

        mavka_smtp_commande($socket, 'QUIT');

        $ok = mavka_smtp_code($reponse) === '250';
        if (!$ok) {
            $erreur = 'Message refusé après envoi — ' . trim($reponse);
            error_log("SMTP: $erreur");
        }
        return $ok;
    } finally {
        fclose($socket);
    }
}
