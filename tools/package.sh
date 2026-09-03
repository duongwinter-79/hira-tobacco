#!/bin/sh
# Đóng gói theme và plugin thành hai file zip để upload lên WordPress
# (Appearance → Themes → Add New → Upload Theme, và Plugins → Add New → Upload Plugin).
#
#     sh tools/package.sh
#
# File nằm ở dist/. Chạy lint trước để không đóng gói code hỏng.

set -e

cd "$(dirname "$0")/.."

echo "→ kiểm tra code trước khi đóng gói"
sh tools/lint.sh > /dev/null
echo "  ok"

rm -rf dist
mkdir -p dist

echo "→ đóng gói"
( cd wp-content/themes && zip -rq "../../dist/annamleaf-theme.zip" annamleaf -x "*.DS_Store" "*/.*" )
( cd wp-content/plugins && zip -rq "../../dist/annamleaf-core.zip" annamleaf-core -x "*.DS_Store" "*/.*" )

ls -lh dist/*.zip | awk '{ printf "  %-28s %s\n", $9, $5 }'

cat <<'NOTE'

Thứ tự upload lên WordPress:
  1. Plugins  → Add New → Upload Plugin → annamleaf-core.zip  → Install → Activate
  2. Appearance → Themes → Add New → Upload Theme → annamleaf-theme.zip → Install → Activate
  3. Settings → Permalinks → Save Changes
NOTE
