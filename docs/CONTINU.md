# Fil « Audace en continu » — fonctionnement dans le thème

> **Depuis :** 0.5.0 (bloc de l'accueil), 0.5.1 (page de réglages) — novembre 2026
> **Fichiers :** `radio-audace/inc/continu.php`, `radio-audace/front-page.php`,
> `radio-audace/assets/js/theme.js` (section « Fil »), fin de `radio-audace/assets/css/theme.css`
> **Maquette :** `design/accueil-continu.html` et `design/continu.css`

Le fil affiche sur l'accueil des titres d'information saisis ou validés dans RadioManager
(module Social, écran « Info en continu »). Ce document décrit **la part du thème**. Les
règles éditoriales sont dans le cadrage (dépôt `radiomanager-modular`,
`docs/modules/social/specifications/CADRAGE_INFO_EN_CONTINU.md`) ; le serveur est décrit dans
le dépôt de l'API (`docs/SOCIAL_LIVE.md`).

## 1. Le trajet d'un titre

```
RadioManager   GET /social/live/feed        clé partagée, en-tête X-Audace-Secret
     │
ra_continu()   copie de 60 s ; copie de secours de 10 min si l'API ne répond pas
     │
GET  /wp-json/radio-audace/v1/continu        relais public, lu par theme.js
POST /wp-json/radio-audace/v1/continu/purge  vide les copies ; appelé par RadioManager
```

Le navigateur d'un visiteur n'appelle jamais RadioManager : il lit le relais du thème.

## 2. Mise en service

WordPress, **Réglages › Audace en continu** : coller la clé partagée (la même valeur que
`SOCIAL_LIVE_FEED_SECRET` côté API). La page affiche l'état réel de la connexion.

| Réglage | Option WordPress | Constante `wp-config.php` (passe devant) |
|---|---|---|
| Clé partagée | `ra_continu_cle` | `RA_CONTINU_CLE` |
| Adresse de l'API (site de test seulement) | `ra_continu_api` | `RA_CONTINU_API` |

**Sans clé, le thème ne fait rien** : aucun appel sortant, accueil habituel.

## 3. Quand l'accueil change-t-il ?

`front-page.php` appelle `ra_continu()`. Si `active` est vrai, il rend la **seconde ouverture**
(classe `avec-fil`) : l'antenne en bandeau (`ra_bandeau_antenne_continu`), la une, puis le
bloc du fil (`ra_bloc_continu`). Sinon il rend l'ouverture habituelle, **inchangée**.

`active` vient de l'API : fil activé dans RadioManager **et** un titre assez récent, ou un
titre épinglé. Le thème n'applique aucune règle de fraîcheur lui-même.

Le fil est donc absent quand : la clé manque, le fil est désactivé, il est vide ou trop
ancien, ou l'API est injoignable depuis plus de 10 minutes.

## 4. Le rafraîchissement (`theme.js`)

- `initialiserFil()` est appelé à chaque page affichée, y compris par la navigation sans
  rechargement. Il arrête la minuterie précédente : **une seule minuterie à la fois**.
- Le relais est relu dès le chargement, puis toutes les 90 secondes (`PERIODE_FIL`).
- `majFil()` **n'insère que les nouveaux titres** et retire ceux qui ont disparu. Il ne
  reconstruit jamais la liste : un lecteur d'écran relirait tout (`aria-relevant="additions"`).
- Un titre corrigé est réécrit sur place (comparaison par `data-empreinte`).
- La disposition de la page **ne change jamais sous les yeux du lecteur**. Si le fil est
  désactivé pendant qu'une page est ouverte, le bloc garde ses titres et cesse de se
  rafraîchir ; l'accueil habituel revient au chargement suivant.

### Contrat entre PHP et JavaScript

Le balisage d'un titre est écrit deux fois : par `ra_bloc_continu()` au premier affichage,
par `remplirTitre()` quand un titre arrive. **Les deux doivent rester identiques.**

| Attribut | Rôle |
|---|---|
| `data-fil` | Le bloc. Sa présence déclenche le rafraîchissement |
| `data-fil-liste` | La liste des titres |
| `data-fil-epingle` | L'encart du titre épinglé (hors de la frise, sans heure) |
| `data-fil-maj` | « Dernier titre à… » |
| `data-fil-plus` | Bouton « Voir plus de titres » |
| `li[data-id]`, `data-jour` | Un titre ; `data-jour` vaut `Hier` ou une date pour un titre plus ancien |

Le relais renvoie pour chaque titre : `id`, `titre`, `rubrique`, `classe`, `etiquette`
(`urgent`, `rectificatif` ou vide), `heure`, `iso`, `jour`. Les heures sont calculées par le
serveur dans le fuseau `Africa/Douala`.

## 5. Règles d'affichage à respecter

- Le rouge est réservé au direct : le fil utilise l'encre et le bleu Audace.
- Aucun titre n'est cliquable.
- Cinq titres visibles sur ordinateur, trois sur téléphone ; le reste se déplie sur place.
- Un séparateur « Hier » précède les titres de la veille.
- Un titre qui arrive est surligné brièvement, sans animation si l'utilisateur a demandé à
  réduire les mouvements.

## 6. Vérifier

Il n'y a pas de WordPress local. Trois contrôles possibles :

- **De l'extérieur** : `https://www.radioaudace.com/wp-json/radio-audace/v1/continu` montre ce
  que le site sert.
- **Dans WordPress** : la page Réglages › Audace en continu dit si la connexion réussit et si
  le fil est affiché.
- **Hors WordPress** : une page statique qui charge `theme.css` et `theme.js`, avec le
  balisage du bloc et un fichier JSON à la place du relais (`RadioAudace.continuUrl`). C'est
  ainsi que l'ajout, la correction, le retrait et l'épingle ont été vérifiés avant la 0.5.0.

État des vérifications : vu en production sur ordinateur le 25 novembre 2026. **L'affichage
sur téléphone n'a pas été vérifié.**

## 7. Modifier et publier

1. Modifier ; garder identiques le balisage PHP et le balisage JavaScript (§ 4).
2. Incrémenter la version dans `radio-audace/style.css` **et** `radio-audace/functions.php`.
3. Ajouter la section `## [X.Y.Z]` dans `CHANGELOG.md` (elle sert de notes de release).
4. Committer, pousser sur `main`, puis `./release.sh` (`--essai` pour vérifier sans publier).
5. Dans WordPress : Apparence › Thèmes › « Vérifier les mises à jour maintenant ».

Un push sur `main` ne met pas le site à jour : seul `./release.sh` le fait.
