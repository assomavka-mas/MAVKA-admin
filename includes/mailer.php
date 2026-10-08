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
function mavka_smtp_envoyer(string $to, string $subject, string $body, string $replyTo = ''): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
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
        error_log("SMTP: connexion échouée à $host:$port — $errstr");
        return false;
    }
    stream_set_timeout($socket, 15);

    try {
        mavka_smtp_lire_reponse($socket); // bannière du serveur (220 ...)

        mavka_smtp_commande($socket, "EHLO $hostnameLocal");

        $reponse = mavka_smtp_commande($socket, 'STARTTLS');
        if (mavka_smtp_code($reponse) !== '220') {
            error_log('SMTP: STARTTLS refusé — ' . trim($reponse));
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('SMTP: la négociation TLS a échoué');
            return false;
        }
        // Un serveur SMTP peut changer ce qu'il annonce une fois la connexion chiffrée —
        // reEHLO obligatoire après STARTTLS.
        mavka_smtp_commande($socket, "EHLO $hostnameLocal");

        $reponse = mavka_smtp_commande($socket, 'AUTH LOGIN');
        if (mavka_smtp_code($reponse) !== '334') {
            error_log('SMTP: AUTH LOGIN non proposé — ' . trim($reponse));
            return false;
        }
        mavka_smtp_commande($socket, base64_encode($user));
        $reponse = mavka_smtp_commande($socket, base64_encode($pass));
        if (mavka_smtp_code($reponse) !== '235') {
            error_log('SMTP: authentification refusée — ' . trim($reponse));
            return false;
        }

        mavka_smtp_commande($socket, "MAIL FROM:<$from>");
        $reponse = mavka_smtp_commande($socket, "RCPT TO:<$to>");
        if (mavka_smtp_code($reponse) !== '250') {
            error_log('SMTP: destinataire refusé — ' . trim($reponse));
            return false;
        }
        $reponse = mavka_smtp_commande($socket, 'DATA');
        if (mavka_smtp_code($reponse) !== '354') {
            error_log('SMTP: DATA refusé — ' . trim($reponse));
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

        return mavka_smtp_code($reponse) === '250';
    } finally {
        fclose($socket);
    }
}
