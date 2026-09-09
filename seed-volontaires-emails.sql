-- Crée les profils Intervenant·e + l'accès "Bénévole" (connexion Google) pour les
-- volontaires listés avec leur email, à exécuter UNE FOIS dans phpMyAdmin (onglet SQL)
-- sur la base u568973923_mavka_dev.
--
-- Chaque personne : un profil dans `intervenants` (nom + email de contact) + une ligne
-- dans `admins` (role='benevole', liée par intervenant_id) — même résultat que remplir
-- "Accès espace bénévole" sur la fiche de chaque intervenant·e depuis l'admin, juste fait
-- en une fois pour tout le monde.
--
-- Ne remplit PAS bio/parcours_personnel/vision (les 3 onglets publics de la page) —
-- ce contenu vient de l'ancien site WordPress et n'est pas disponible ici pour l'écrire
-- automatiquement. À compléter ensuite pour chaque personne dans Intervenants.

INSERT INTO intervenants (nom, email, actif) VALUES ('William Aubert', 'william.aubert.evo@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('william.aubert.evo@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Haiyan Huang', 'sarah.photo79@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('sarah.photo79@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Dmytro Gorbatko', 'gorbatkodmitrij@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('gorbatkodmitrij@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Hanna Sokha', 'hannasokham@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('hannasokham@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Larysa Mas', 'mas.larysa@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('mas.larysa@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Nadiia Denysenko', 'borysenkonadiia020@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('borysenkonadiia020@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Nataliia Kolesnikova', 'art.nk.france@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('art.nk.france@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Olena Maksymova', 'elenamaksimova502@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('elenamaksimova502@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Olha Hapiienko', 'Ol.art1301@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('Ol.art1301@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Snizhana Zhuravlova', 'sneghana28@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('sneghana28@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Nataliia Veremeienko', 'veremeienkon@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('veremeienkon@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);

INSERT INTO intervenants (nom, email, actif) VALUES ('Yellyzaveta Mas', 'mas.yelyzaveta@gmail.com', 1);
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('mas.yelyzaveta@gmail.com', 'benevole', LAST_INSERT_ID(), NULL);
