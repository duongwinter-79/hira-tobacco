#!/bin/sh
# Everything that can be checked without a WordPress install:
# PHP syntax across the theme, the plugin and the tools, then a full template render
# with notices and warnings treated as failures.
#
#     sh tools/lint.sh

set -e

cd "$(dirname "$0")/.."

echo "→ PHP syntax"
files=$(find wp-content tools -name '*.php' | sort)

for file in $files; do
	php -l "$file" > /dev/null
done

echo "  $(echo "$files" | wc -l | tr -d ' ') files, no syntax errors"

echo "→ theme.json"
php -r 'json_decode(file_get_contents("wp-content/themes/annamleaf/theme.json"), true, 512, JSON_THROW_ON_ERROR); echo "  valid\n";'

echo "→ template render"
php tools/render-check.php
