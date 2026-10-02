# MAVKA-admin — mémo pour Claude

Site (public + admin) de l'association MAVKA (loi 1901, Charente), en remplacement de
l'ancien site WordPress. Dev sur dev.mavka16.fr. Propriétaire du projet : Larysa (présidente
de l'association) — non technicienne, communique en ukrainien, apprécie des résumés courts
après chaque changement plutôt qu'un compte-rendu technique détaillé.

## Architecture

PHP procédural, pas de framework, pas d'ORM. `includes/db.php` expose `db(): PDO` (singleton).
- `includes/auth.php` — `auth_require($roles)`, `peut_editer($user)`. Rôles :
  `super_admin` > `mavka_admin` > `benevole`/`partenaire`.
- `includes/functions.php` — utilitaires admin (upload, `retirer_fond_blanc()`,
  `intervenant_statuts()`, helpers de formulaire `champ_document()`/`champ_fichier_seul()`).
- `includes/site_functions.php` — tout ce qui rend le **site public**, y compris les fonctions
  de carte d'activité (`render_event_card()`, `render_events_grid()`) **partagées entre le site
  public (index.php) et les aperçus admin** (admin/mes-activites.php, intervenant.php "Ses
  activités"). En touchant cette logique, toujours vérifier les deux types d'appelants — c'est
  une source récurrente de bugs de divergence public/admin.
- `includes/layout.php` — `admin_header()`/`admin_footer()` pour les pages admin.
- `index.php` — site public en une seule page, routage par `#hash` en JS (`.page.active`),
  toutes les sections de toutes les pages dans ce fichier unique (gros fichier, chercher par
  ancre `id="page-xxx"`).
- `intervenant.php` — page publique individuelle d'un·e bénévole/intervenant·e.
- `admin/*.php` — un fichier par écran admin, classique `auth_require()` en haut de fichier.

## Bascule FR / UK (index.php)

Un dictionnaire JS géant en une seule ligne : `const UK={...}` (~420 entrées), qui remplace le
texte FR par de l'UK via un TreeWalker sur les nœuds texte + les attributs
`alt/placeholder/aria-label/title`. Toute correspondance manquante reste silencieusement en
français — pas d'erreur visible.

**Toute modification de texte visible (ou d'alt) sur index.php doit avoir une entrée UK
correspondante**, sauf sur la page RGPD/Mentions légales (`#page-confidentialite`) qui n'a
délibérément jamais été traduite (convention déjà en place, ne pas "corriger").

Le dict est une seule ligne illisible : ne jamais l'éditer à la main avec Edit. Toujours passer
par un script Python :
```python
import re, json
with open('index.php', encoding='utf-8') as f: content = f.read()
m = re.search(r'const UK=(\{.*?\});', content)
uk = json.loads(m.group(1))
uk["Nouveau texte FR"] = "Переклад укр"
new_dict_str = json.dumps(uk, ensure_ascii=False, separators=(', ', ': '))
content = content[:m.start(1)] + new_dict_str + content[m.end(1):]
with open('index.php', 'w', encoding='utf-8') as f: f.write(content)
```
Puis `php -l index.php`.

## Migrations SQL

Fichiers `alter-champs-vNN.sql` à la racine, numérotés séquentiellement. **Toujours vérifier le
numéro le plus haut existant avant d'en créer un** (`ls alter-champs-v*.sql | sort -V | tail`)
— écraser un vNN existant par erreur est arrivé une fois. Larysa les applique elle-même,
manuellement, via phpMyAdmin — ne jamais supposer qu'une migration a déjà été appliquée en
prod. `schema.sql` est une référence vivante : la mettre à jour à chaque migration (colonne +
commentaire), elle n'est jamais exécutée directement.

## Images

- Avatars/photos uploadés (PNG) : `retirer_fond_blanc()` (includes/functions.php) détoure
  automatiquement le fond blanc par flood-fill (GD). Ne fonctionne que sur de vrais PNG — un
  fichier envoyé en JPEG mais dans un champ "avatar" est refusé (`pas-un-png (image/jpeg)`).
  C'est un problème côté utilisateur (mauvais export), pas un bug à corriger dans le code : dire
  à Larysa de ré-exporter en PNG.
- Nouvelles illustrations (assets/site-img/) : rogner à la boîte englobante du contenu (+~12px
  de marge), exporter en **WebP lossy** qualité ~92-95, method=6 — jamais lossless (2-4× plus
  lourd pour un dessin en aplats de couleur, sans gain visible).
- Toute image statique référencée dans index.php doit avoir le cache-busting
  `?v=<?= @filemtime(__DIR__.'/chemin') ?: time() ?>`, sinon les navigateurs affichent l'ancienne
  version après remplacement.

## CSS (assets/site.css)

Palette unique en `:root` (--mint, --sun, --violet, --teal, --ground, --cream...), plus de
variantes de thème. Rythme visuel : sections `.sand` (fond mint) et sections sans classe (fond
`--ground`) **alternées** — en ajoutant/réordonnant des sections, vérifier qu'on n'a pas deux
fonds identiques consécutifs.

Composants récurrents : `.hero`, `.cta-card`, `.dir-grid`/`.dir-art`/`.dirs`/`.dir` (texte +
illustration), `.pillars`/`.pillar`, `.steps`/`.step` (numérotation **automatique** via
`.step::before{counter(step)}` — ne jamais ajouter un badge numéroté à la main, ça double
l'affichage), `.stats-strip` (bandeau de chiffres de l'accueil).

Piège mobile : `.btn{white-space:nowrap}`. Un groupe de boutons (`.actions`) doit avoir une
règle d'empilement mobile (`flex-direction:column` + `width:100%`) **et** `white-space:normal`
en filet de sécurité — sinon un texte de bouton un peu long (texte agrandi côté utilisateur,
traduction UK plus longue...) peut déborder de l'écran même en pleine largeur. Déjà pris en
défaut une fois sur `.hero .actions`, qui n'avait pas la règle d'empilement du tout.

## Autres conventions établies

- `texte_bouton = 'Voir sa page'` sur `activites` : marqueur spécial "carte-vitrine" qui pointe
  vers la page d'un·e intervenant·e, pas une vraie activité réservable. Affecte
  `site_activites_intervenant()`, `render_event_card()` (teinte de fond par catégorie),
  `activite-form.php` (lien d'inscription auto).
- Connexion admin = uniquement Google Sign-In, mais un compte Google **peut** être rattaché à
  une adresse non-Gmail ("utiliser mon adresse actuelle" sur accounts.google.com) — utile à
  rappeler à Larysa quand elle veut donner un accès Partenaire à quelqu'un sans @gmail.com.
  Étape 2, après création du compte Google : ajouter l'accès dans `admin/acces.php`.
- Label de statut "Découverte" (pas "Volontaire") pour un profil sans Charte/Contrat signé —
  choix délibéré (distinction juridique française bénévolat/volontariat), ne pas revenir dessus
  sans que Larysa le redemande explicitement.
- `assets/docs/*.pdf` (Statuts, Règlement intérieur, Charte du bénévolat, Contrat-cadre,
  Projet associatif, attestations...) : documents de gouvernance officiels, remplacés
  périodiquement par Larysa via un PDF fourni. À l'extraction/l'enregistrement d'un nouveau PDF,
  **toujours vérifier les pages vides** avant de committer (`page.get_text().strip()` par page
  via pymupdf/`fitz`) — déjà arrivé deux fois qu'un export contienne une page blanche en trop.

## Tester une modification

Pas d'accès DB dans ce sandbox (`config.php` est gitignore et absent ici) — impossible de lancer
l'app PHP complète. Pour une question de mise en page/CSS uniquement : reconstruire un fragment
statique (header réel + section concernée, sans PHP dépendant de la DB) et vérifier avec
Playwright (chromium déjà installé sur `/opt/pw-browsers/chromium`) à de vraies largeurs
d'écran — comparer `document.documentElement.scrollWidth` à `clientWidth` pour un débordement,
ne jamais deviner. Toujours supprimer les fichiers de test temporaires avant de committer
(jamais de fichier de test dans les commits).

## Workflow

- `php -l` sur chaque fichier PHP modifié avant de committer.
- Messages de commit en français (convention du dépôt).
- Résumer les changements à Larysa en ukrainien, de façon concise — elle n'a pas besoin des
  détails d'implémentation sauf si elle demande.
- Larysa envoie parfois des lots d'images en `.zip` : seules les pièces jointes archivées sont
  lisibles (dans `/root/.claude/uploads/...`), pas les images collées directement dans le chat.
