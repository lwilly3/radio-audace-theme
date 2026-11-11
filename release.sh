#!/usr/bin/env bash
# Publie la version courante du thème Radio Audace comme release GitHub.
# C'est cette release que le site lit pour proposer la mise à jour dans WordPress
# (voir radio-audace/inc/miseajour.php).
#
# Avant de lancer :
#   1. incrémenter la version dans radio-audace/style.css ET radio-audace/functions.php ;
#   2. ajouter la section « ## [X.Y.Z] - AAAA-MM-JJ » dans CHANGELOG.md ;
#   3. commiter et pousser sur main.
#
# Usage : ./release.sh           publie
#         ./release.sh --essai   vérifie tout et construit le ZIP, sans rien publier
#
# Prérequis : gh authentifié ; dépôt PUBLIC (un dépôt privé répond 404 au site).
set -euo pipefail

DEPOT="lwilly3/radio-audace-theme"
ASSET="radio-audace.zip"
ICI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$ICI/radio-audace"
ESSAI=0
[ "${1:-}" = "--essai" ] && ESSAI=1

echec() { echo "Erreur : $*" >&2; exit 1; }

# 1. Les deux numéros de version doivent être identiques.
VERSION="$(sed -nE 's/^Version: +([0-9]+\.[0-9]+\.[0-9]+)$/\1/p' "$SRC/style.css")"
CONSTANTE="$(sed -nE "s/^define\( 'RADIO_AUDACE_THEME_VERSION', '([0-9.]+)' \);/\1/p" "$SRC/functions.php")"
[ -n "$VERSION" ] || echec "version introuvable dans radio-audace/style.css"
[ "$VERSION" = "$CONSTANTE" ] || echec "versions différentes : style.css $VERSION, functions.php ${CONSTANTE:-absente}"
TAG="v$VERSION"

# 2. Le journal des modifications doit décrire cette version.
NOTES="$(awk -v v="$VERSION" '$0 ~ "^## \\[" v "\\]" {on=1; next} on && /^## \[/ {exit} on' "$ICI/CHANGELOG.md")"
[ -n "$(printf '%s' "$NOTES" | tr -d '[:space:]')" ] || echec "aucune section « ## [$VERSION] » dans CHANGELOG.md"

# 3. Syntaxe PHP et JavaScript.
while IFS= read -r f; do
    php -l "$f" >/dev/null || echec "syntaxe PHP : $f"
done < <(find "$SRC" -name '*.php')
if command -v node >/dev/null 2>&1; then
    node --check "$SRC/assets/js/theme.js" || echec "syntaxe JavaScript : theme.js"
fi

# 4. Le ZIP : sa racine doit être radio-audace/ (structure attendue par WordPress).
TRAVAIL="$(mktemp -d)"
trap 'rm -rf "$TRAVAIL"' EXIT
mkdir -p "$TRAVAIL/radio-audace"
rsync -a --exclude '.DS_Store' "$SRC/" "$TRAVAIL/radio-audace/"
(cd "$TRAVAIL" && zip -qr "$TRAVAIL/$ASSET" radio-audace)
mkdir -p "$ICI/dist"
cp "$TRAVAIL/$ASSET" "$ICI/dist/radio-audace-$VERSION.zip"
echo "ZIP construit : dist/radio-audace-$VERSION.zip ($(du -h "$TRAVAIL/$ASSET" | cut -f1 | tr -d ' '))"

if [ "$ESSAI" = 1 ]; then
    echo "Essai réussi pour $TAG : rien n'a été publié."
    exit 0
fi

# 5. Ce qui est publié doit être exactement ce qui est sur GitHub.
cd "$ICI"
[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || echec "la release se fait depuis la branche main"
[ -z "$(git status --porcelain -- radio-audace CHANGELOG.md)" ] || echec "modifications non commitées dans radio-audace/ ou CHANGELOG.md"
git fetch --quiet origin main
[ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ] || echec "main n'est pas poussée (ou pas à jour) : git push / git pull d'abord"

VISIBILITE="$(gh repo view "$DEPOT" --json visibility --jq .visibility)"
[ "$VISIBILITE" = "PUBLIC" ] || echec "le dépôt $DEPOT est $VISIBILITE : le site ne pourrait pas lire la release"

if gh release view "$TAG" --repo "$DEPOT" >/dev/null 2>&1; then
    echec "la release $TAG existe déjà : incrémenter la version"
fi

# 6. La release (crée le tag sur main) et son asset au nom exact attendu par le thème.
gh release create "$TAG" "$TRAVAIL/$ASSET" \
    --repo "$DEPOT" --target main \
    --title "Thème Radio Audace $TAG" --notes "$NOTES"

echo "Publié : https://github.com/$DEPOT/releases/tag/$TAG"
echo "Sur le site : Apparence › Thèmes › « Vérifier les mises à jour maintenant »."
