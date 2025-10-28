#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/assets/css"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/assets/js"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/assets/images"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/inc/shortcodes"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/inc/hooks"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/inc/post-types"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/inc/taxonomies"
mkdir -p "$ROOT/app/wp-content/themes/hagemancatering/templates"
mkdir -p "$ROOT/app/wp-content/plugins/hm-banqueting-portal"
mkdir -p "$ROOT/app/wp-content/plugins/hm-locations"
mkdir -p "$ROOT/app/wp-content/plugins/hm-utils"
mkdir -p "$ROOT/app/wp-content/mu-plugins"
mkdir -p "$ROOT/app/shared/HM"
mkdir -p "$ROOT/.github/workflows"
echo "Structuur klaar. Kopieer/knip je bestaande bestanden stap-voor-stap."
