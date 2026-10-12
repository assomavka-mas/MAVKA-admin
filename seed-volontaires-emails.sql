-- Crée les profils Intervenant·e + l'accès "Bénévole" (connexion Google) pour les
-- volontaires listés avec leur email, à exécuter UNE FOIS dans phpMyAdmin (onglet SQL)
-- sur la base u568973923_mavka_dev.
--
-- Chaque personne : un profil dans `intervenants` (nom + email de contact) + une ligne
-- dans `admins` (role='benevole', liée par intervenant_id) — même résultat que remplir
-- "Accès espace bénévole" sur la fiche de chaque intervenant·e depuis l'admin, juste fait
-- en une fois pour tout le monde. @iv_id capture l'id de la personne avant l'INSERT
-- suivant dans `admins` (qui a lui-même un AUTO_INCREMENT et écraserait LAST_INSERT_ID()).
--
-- Pour les 7 personnes qui avaient une page "Notre équipe" sur l'ancien WordPress, les 3
-- onglets publics (bio/parcours_personnel/vision), le sous-titre (resume) et les ateliers
-- ("Ce que je propose") sont repris depuis l'export WXR fourni (_elementor_data de chaque
-- page) — tel quel, avec deux corrections :
--   - Nataliia Veremeienko : sous-titre "Violoniste" -> "Saxophoniste" (sa bio/parcours la
--     décrivent comme saxophoniste ; copié-collé visible depuis la page de Snizhana).
--   - Nadiia Denysenko, atelier "Piano & Guitare" : la description se terminait sur un
--     fragment de phrase orphelin ("...chaque enfant.tique musicale après une
--     interruption.") — coupé à la phrase complète, le fragment ne se rattachait à rien.
--
-- Les 5 autres (pas de page trouvée dans l'export) n'ont que nom + email, à compléter
-- manuellement depuis Intervenants.

INSERT INTO intervenants (nom, email, actif) VALUES ('William Aubert', 'william.aubert.evo@gmail.com', 1);
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('william.aubert.evo@gmail.com', 'benevole', @iv_id, NULL);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Haiyan Huang', 'sarah.photo79@gmail.com', 1, 'Photographie • Calligraphie • Origami • Cuisine chinoise • Langue et culture chinoises', 'Originaire de Chine et aujourd’hui installée en Charente, Haiyan Huang est photographe professionnelle et formatrice avec plus de vingt ans d’expérience dans la création artistique et la transmission de savoir-faire.

Au fil de sa carrière, elle a dirigé des projets photographiques, collaboré avec des studios et des agences, accompagné de nombreux élèves et participé à des concours et publications. Aujourd’hui, elle rejoint l’aventure MAVKA avec l’envie de partager son expérience dans un cadre chaleureux, accessible et interculturel.

Pour Haiyan, l’art est avant tout un langage universel qui rapproche les personnes, développe la confiance en soi et permet d’exprimer sa sensibilité sans avoir besoin de maîtriser parfaitement une langue.', '- Plus de 20 ans d’expérience professionnelle dans la photographie et la formation.
- Réalisation de projets artistiques et pédagogiques en Chine.
- Accompagnement de nombreux élèves et créateurs.
Passion pour les échanges interculturels et la transmission des savoir-faire.', 'Haiyan croit profondément que chacun possède une créativité qui ne demande qu’à s’exprimer.

Ses ateliers privilégient l’expérimentation, le partage et la bienveillance. Chacun avance à son rythme, sans pression ni jugement, dans un environnement où l’erreur fait partie de l’apprentissage et où chaque découverte devient une source de confiance.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('sarah.photo79@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Calligraphie chinoise', 'Un art entre écriture, méditation et expression personnelle.

À travers le pinceau et l’encre, les participants découvriront les gestes traditionnels de la calligraphie chinoise, où chaque trait invite à ralentir, se concentrer et trouver l’équilibre. Plus qu’un apprentissage technique, c’est une expérience artistique qui mêle patience, esthétique et bien-être.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Langue et culture chinoises', 'Découvrir une culture à travers des activités vivantes.

Ces ateliers proposeront une initiation ludique au mandarin, particulièrement adaptée aux enfants et aux débutants, tout en ouvrant une fenêtre sur les coutumes, les fêtes et les arts traditionnels chinois.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Origami et créations manuelles', 'Découvrir le plaisir de créer avec des gestes simples.

Les ateliers d’origami invitent petits et grands à développer leur imagination, leur précision et leur créativité en transformant une simple feuille de papier en objet décoratif ou symbolique.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Photographie', 'Apprendre à regarder le monde autrement.

Les ateliers permettront de découvrir les bases de la photographie – composition, lumière, portrait et mise en scène – mais aussi de développer un regard personnel sur ce qui nous entoure. Que vous utilisiez un appareil photo ou simplement votre téléphone, l’objectif est d’apprendre à raconter une histoire à travers l’image et à gagner en confiance dans votre créativité.', 3);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Dmytro Gorbatko', 'gorbatkodmitrij@gmail.com', 1, 'Spécialiste en réadaptation physique — approche du mouvement et du bien-être', 'Fort de plus de 23 ans d''expérience en réadaptation physique et en accompagnement des troubles musculo-squelettiques en Ukraine, Dmytro a dirigé pendant 18 ans son propre cabinet, où il accompagnait des personnes présentant divers troubles du système musculo-squelettique.

Au fil de son parcours, il a développé une approche globale associant anatomie, biomécanique et rééducation fonctionnelle, qu''il enrichit d''une pratique complémentaire par le magnétisme — une approche proche de l''acupuncture dans son principe d''écoute du corps, qu''il utilise pour accompagner la détente et le bien-être general.

Au sein de MAVKA, Dmytro souhaite partager cette expérience à travers des ateliers de prévention, de mobilité et de bien-être, accessibles à tous, dans un esprit de transmission, d''écoute et de respect de chacun.

Ces activités ont une vocation éducative et préventive. Elles ne remplacent pas une consultation médicale ou un traitement thérapeutique.', '- Diplôme d''ingénieur en électronique (Ukraine)
- Formation universitaire en éducation physique et réadaptation
- Master en réadaptation physique
- Plus de 23 ans d''expérience dans la réadaptation fonctionnelle
- Direction pendant 18 ans d''un cabinet privé spécialisé dans l''accompagnement des troubles musculo-squelettiques
- Expérience dans l''accompagnement de personnes souffrant de douleurs lombaires, cervicales, de troubles posturaux et de limitations de mobilité', 'La France représente pour moi une nouvelle étape de vie, mais aussi une nouvelle opportunité de mettre mon expérience au service des autres.

Au sein de MAVKA, je souhaite contribuer à renforcer le lien social, favoriser le bien-être des habitants et partager des pratiques de prévention accessibles à tous.

Je crois que le partage des connaissances, l''entraide et les activités collectives sont de puissants outils d''intégration et de développement personnel.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('gorbatkodmitrij@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Gymnastique articulaire et prévention des douleurs du dos', 'Des mouvements doux pour entretenir la mobilité des articulations et prévenir les tensions du dos et de la nuque.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Comprendre sa posture au quotidien', 'Des repères simples pour mieux se tenir, que ce soit au travail ou dans les gestes de tous les jours.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Mobilité et souplesse pour adultes et seniors', 'Des exercices accessibles pour préserver ou retrouver de l''amplitude de mouvement, à tout âge.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Relaxation corporelle et conscience du mouvement', 'Un temps pour relâcher les tensions et mieux ressentir son corps, avec une approche complémentaire par le magnétisme', 3);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Hanna Sokha', 'hannasokham@gmail.com', 1, 'Praticienne en massage bien-être et professeure de yoga', 'Originaire d''Ukraine, Hanna Sokha accompagne depuis plus de 12 ans des personnes souhaitant améliorer leur mobilité, leur condition physique et leur bien-être global. Son parcours réunit la réadaptation physique, le yoga, le massage bien-être et l''éducation au mouvement.

Passionnée par la prévention et la santé au quotidien, elle conçoit des ateliers accessibles à tous les niveaux, pensés pour mieux connaître son corps, retrouver de la souplesse et préserver son équilibre physique — sans notion de performance.

Aujourd''hui installée en Charente, Hanna s''investit bénévolement au sein de MAVKA afin de rendre les activités de bien-être accessibles au plus grand nombre.', 'Après des études en économie et en comptabilité, Hanna s''est progressivement orientée vers les métiers du mouvement et du bien-être.

Elle obtient un diplôme en santé humaine, puis suit une formation complète auprès de l''International Yoga Association pour devenir professeure de yoga.

Pendant plusieurs années, elle accompagne des personnes dans les domaines de la réadaptation physique, de l''entraînement fonctionnel, du yoga et du massage bien-être, et développe des programmes de mobilité, de prévention et d''amélioration de la condition physique.

Aujourd''hui installée en Charente, elle met cette expérience au service des ateliers collectifs de MAVKA.', 'Originaire d''Ukraine, Hanna Sokha accompagne depuis plus de 12 ans des personnes souhaitant améliorer leur mobilité, leur condition physique et leur bien-être global. Son parcours réunit la réadaptation physique, le yoga, le massage bien-être et l''éducation au mouvement.

Passionnée par la prévention et la santé au quotidien, elle conçoit des ateliers accessibles à tous les niveaux, pensés pour mieux connaître son corps, retrouver de la souplesse et préserver son équilibre physique — sans notion de performance.

Aujourd''hui installée en Charente, Hanna s''investit bénévolement au sein de MAVKA afin de rendre les activités de bien-être accessibles au plus grand nombre.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('hannasokham@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Gymnastique articulaire', 'Des mouvements doux et progressifs pour entretenir la mobilité de chaque articulation, adaptés à tous les âges', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Yoga Bien-Être', 'Une pratique accessible, sans prérequis, centrée sur la respiration, la posture et l''écoute du corps.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Relaxation', 'Des exercices simples pour relâcher les tensions accumulées et retrouver un état de calme durable.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Mobilité', 'Un travail ciblé pour préserver ou retrouver de l''amplitude de mouvement au quotidien.', 3);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Conseils pour le bien-être du quotidien', 'Des repères concrets et faciles à appliquer chez soi, entre les séances.', 4);

INSERT INTO intervenants (nom, email, actif) VALUES ('Larysa Mas', 'mas.larysa@gmail.com', 1);
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('mas.larysa@gmail.com', 'benevole', @iv_id, NULL);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Nadiia Denysenko', 'borysenkonadiia020@gmail.com', 1, 'Enseignante en musique · Professeure de chant · Musicienne', 'Je m’appelle Nadiia Denysenko, musicienne et enseignante diplômée de l’Académie Nationale de Musique Tchaïkovski de Kyiv.

Depuis plus de 10 ans, j’accompagne des enfants et des adolescents dans leur découverte de la musique, du chant et de l’expression artistique.

Ma pédagogie repose sur la bienveillance, la confiance et le respect du rythme de chaque élève. Je suis convaincue que chaque enfant possède un potentiel créatif unique qui mérite d’être encouragé et développé.', '• Master en Art Musical
• Diplômée de l’Académie Nationale de Musique P. I. Tchaïkovski de Kyiv
• Plus de 10 ans d’expérience pédagogique

Expérience professionnelle :
• École de musique n°6 de Kyiv
• Lycée Classique de Zaporijjia
• Collège Professionnel de Musique P. I. Maïboroda de ZaporijjiaJ’ai également dirigé plusieurs ensembles et orchestres d’instruments traditionnels.

• Lauréate de concours internationaux
• Responsable de projets artistiques et musicaux
• Direction d’ensembles de domras et d’orchestres d’instruments traditionnels
• Grande expérience auprès d’enfants de différents âges et niveaux', 'Pour moi, la musique est bien plus qu’un apprentissage technique.

C’est un moyen de développer la confiance en soi, la créativité, l’écoute et le plaisir d’apprendre.

Mon objectif est de rendre chaque cours vivant, motivant et accessible, afin que chaque élève puisse progresser à son rythme et prendre plaisir à faire de la musique.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('borysenkonadiia020@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Éveil & Créativité Musicale', 'Des activités ludiques pour découvrir la musique, développer l’écoute, le rythme et la créativité. Une approche adaptée à l’âge des participants pour apprendre et expérimenter par la musique.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Chant Moderne & Variété', 'Des séances pour découvrir ou développer le plaisir de chanter, travailler la voix, le rythme et l’interprétation. Une approche accessible aux débutants, dans une ambiance bienveillante.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Instruments à Cordes', 'Un accompagnement autour de la domra et de la mandoline, permettant de découvrir ou d’approfondir la pratique de ces instruments et leur univers musical.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Découverte des Instruments Traditionnels', 'Des activités pour découvrir différents instruments traditionnels, leurs sonorités et leur univers musical.

Une approche ludique qui favorise la curiosité et l’ouverture à différentes cultures musicales.', 3);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Piano & Guitare', 'Des cours de piano pour les 6–14 ans et de guitare pour les débutants de 6–10 ans, adaptés au niveau et au rythme de chaque enfant.', 4);

INSERT INTO intervenants (nom, email, actif) VALUES ('Nataliia Kolesnikova', 'art.nk.france@gmail.com', 1);
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('art.nk.france@gmail.com', 'benevole', @iv_id, NULL);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Olena Maksymova', 'elenamaksimova502@gmail.com', 1, 'Psychopraticienne — accompagnement en développement personnel et bien-être psycho-émotionnel', 'Originaire d''Ukraine, Olena Maksymova est psychopraticienne, formée en psychologie et titulaire d''une formation médicale. Depuis plus de vingt ans, elle accompagne des enfants, des adolescents, des adultes et des familles dans les différentes étapes de leur parcours de vie. Son expérience réunit le travail en institution, l''accompagnement individuel, la pratique en cabinet ainsi que l''intervention auprès de personnes confrontées à des situations liées aux conflits armés.

À travers son engagement au sein de MAVKA, Olena souhaite proposer des espaces d''écoute, de réflexion et de développement personnel accessibles à tous. Son approche invite chacun à mieux comprendre son fonctionnement, retrouver ses ressources et avancer à son propre rythme, dans un cadre bienveillant et respectueux.

Ces activités ont une vocation d''écoute, d''échange et de développement personnel. Elles ne remplacent pas un suivi médical ou psychologique professionnel.', '- Diplômée en psychologie (Université Nationale de Donetsk)
- Formation médicale (obstétrique)
- Plus de 20 ans d''expérience dans l''accompagnement psychologique
- Formation continue en psychothérapie existentielle, psychodynamique et psychanalyse
- Formation à la méthode du Symboldrama
- Formation en thérapie cognitivo-comportementale centrée sur le traumatisme
- Expérience auprès de personnes confrontées aux conflits armés
- Formation continue en psychanalyse structurale (Lacan)', 'Je crois que chaque personne possède les ressources nécessaires pour évoluer, retrouver son équilibre et construire une vie qui lui ressemble.

Mon rôle n''est pas d''apporter des réponses toutes faites, mais d''offrir un espace sécurisé où chacun peut mieux comprendre son histoire, transformer ses difficultés et développer ses propres capacités d''évolution.

Au sein de MAVKA, je souhaite contribuer à créer une communauté où chacun peut se sentir accueilli, écouté et soutenu dans son parcours personnel.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('elenamaksimova502@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Groupes de parole et d''échange', 'Un espace pour partager son vécu et être écouté, dans un cadre bienveillant et confidentiel.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Gestion du stress et des émotions', 'Des outils simples pour mieux comprendre et apaiser ses tensions au quotidien.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Connaissance de soi et confiance en soi', 'Un travail progressif pour mieux se comprendre et avancer avec plus d''assurance.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Accompagnement des périodes de transition de vie', 'Un soutien pour traverser les changements — déménagement, nouveau pays, nouvelle étape de vie.', 3);

INSERT INTO intervenants (nom, email, actif) VALUES ('Olha Hapiienko', 'Ol.art1301@gmail.com', 1);
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('Ol.art1301@gmail.com', 'benevole', @iv_id, NULL);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Snizhana Zhuravlova', 'sneghana28@gmail.com', 1, 'Enseignante en musique · Violoniste · Cheffe de chœur', 'Originaire d’Ukraine et installée en France depuis 2024, Sneghana est enseignante en musique, violoniste et pédagogue, avec une longue expérience dans l’enseignement musical et les sciences de l’éducation.

Elle a enseigné pendant de nombreuses années le violon, le piano, la formation musicale et la pratique d’ensemble dans une école de musique en Ukraine, tout en enseignant l’Art et la Culture internationale à l’université de Kropyvnytskyï.

 Elle a également exercé comme violoniste professionnelle au sein de l’Orchestre philharmonique de Kropyvnytskyï.Depuis son arrivée en France, elle poursuit son activité pédagogique auprès d’élèves de différents âges et niveaux.

Au sein de MAVKA, elle souhaite créer un espace où la musique devient à la fois un apprentissage, un moyen d’expression et une occasion de rencontrer les autres.', '- Doctorat en Sciences pédagogiques
- Master en éducation pédagogique, spécialité enseignement musical
- Formation professionnelle en instruments à cordes et enseignement de la musique
- Plus de 20 ans d’expérience dans l’enseignement musical
- Enseignement du violon, du piano et de la formation musicale
- Expérience en musique d’ensemble
- Expérience comme cheffe de chœur
- Expérience comme violoniste concertiste professionnelle', 'Pour Sneghana, l’apprentissage musical ne se limite pas à la maîtrise d’un instrument. Il permet également de développer l’écoute, la concentration, la confiance en soi, la créativité et la capacité à travailler avec les autres.

La pratique collective occupe une place particulière dans son approche : chacun apporte sa voix, son instrument et sa sensibilité pour construire quelque chose ensemble.

Au sein de MAVKA, elle souhaite transmettre son expérience dans un cadre bienveillant, accessible et ouvert à tous les niveaux, tout en favorisant les rencontres et les échanges autour de la musique.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('sneghana28@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Pratique musicale en groupe', 'Des ateliers permettant de découvrir la musique d’ensemble et d''apprendre à jouer avec les autres.

Selon les participants et leurs instruments, ces ateliers pourront évoluer vers la création de petits ensembles musicaux.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Éveil et formation musicale', 'Une approche progressive pour développer l’écoute, le rythme, la compréhension de la musique et la créativité.

Les activités peuvent être adaptées à l’âge et au niveau des participants.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Chant & choral', 'Un atelier collectif pour découvrir ou développer le plaisir de chanter ensemble.

La chorale permet de travailler la voix, l’écoute, le rythme et la musicalité, tout en développant la confiance en soi et le plaisir de faire partie d’un groupe.

Aucune expérience musicale particulière n’est nécessaire.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Cours individuels', 'Des cours adaptés au niveau et aux objectifs de chacun autour notamment du violon, du piano et de la formation musicale.

L’accompagnement peut convenir aussi bien aux débutants qu’aux personnes souhaitant reprendre une pratique musicale après une interruption.', 3);

INSERT INTO intervenants (nom, email, actif, resume, bio, parcours_personnel, vision) VALUES ('Nataliia Veremeienko', 'veremeienkon@gmail.com', 1, 'Enseignante en musique · Saxophoniste', 'Originaire d’Ukraine et installée aujourd’hui à Soyaux, Nataliia Veremeienko est musicienne professionnelle, saxophoniste et enseignante de musique.

Pendant plus de dix ans, elle a accompagné des enfants et des adolescents dans leur apprentissage musical tout en poursuivant une carrière artistique au sein de plusieurs orchestres et ensembles musicaux en Ukraine.

Son parcours réunit la pratique artistique, la pédagogie et le plaisir de transmettre.

Au sein de MAVKA, Nataliia souhaite partager son expérience musicale dans un cadre accessible, bienveillant et ouvert à tous.', '• Diplômée du Collège Professionnel de Musique de Zaporijjia
• Diplômée de l’Académie de Musique de Dnipro
• Professeure de saxophone pendant plusieurs années au Lycée Classique de Zaporijjia
• Saxophoniste au sein d’orchestres et d’ensembles de jazz en Ukraine
• Participation à de nombreux concerts et événements culturels', 'Installée en Charente depuis plusieurs années, Nataliia poursuit aujourd’hui son intégration en France tout en continuant à développer ses projets artistiques et pédagogiques.

À travers MAVKA, elle souhaite transmettre son savoir-faire, rencontrer de nouvelles personnes et participer à la vie culturelle locale.');
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('veremeienkon@gmail.com', 'benevole', @iv_id, NULL);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Pratique Musicale Collective', 'Des ateliers pour apprendre à écouter les autres, jouer ensemble et partager une expérience musicale collective.

Les activités s’adaptent au niveau et aux instruments des participants.', 0);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Éveil & Initiation Musicale', 'Des activités pour découvrir la musique, développer l’écoute, le rythme et la sensibilité musicale.

Une approche ludique particulièrement adaptée aux enfants et à leurs premières expériences avec la musique.', 1);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Musique & Expression Artistique', 'Des activités permettant d’explorer la musique comme moyen d’expression, de créativité et de partage.

Chacun peut expérimenter et développer sa sensibilité artistique à son propre rythme.', 2);
INSERT INTO intervenant_ateliers (intervenant_id, titre, description, ordre) VALUES (@iv_id, 'Musique & Rencontres Interculturelles', 'Des rencontres autour des musiques et des cultures pour découvrir différentes traditions musicales, échanger et créer des liens.

Un espace ouvert à tous favorisant la curiosité, le partage et le dialogue interculturel.', 3);

INSERT INTO intervenants (nom, email, actif) VALUES ('Yellyzaveta Mas', 'mas.yelyzaveta@gmail.com', 1);
SET @iv_id = LAST_INSERT_ID();
INSERT INTO admins (email, role, intervenant_id, password_hash) VALUES ('mas.yelyzaveta@gmail.com', 'benevole', @iv_id, NULL);

