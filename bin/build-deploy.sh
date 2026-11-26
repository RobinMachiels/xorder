#!/usr/bin/env bash
# Bouwt een uploadklaar zip-pakket voor een andere Combell-host (volledig project in de webroot).
# Voor assurio.be/xorder gebruik je bin/deploy-assurio.sh. Dit script uploadt NIETS.
set -euo pipefail
cd "$(dirname "$0")/.."

STAMP="$(date +%Y%m%d-%H%M)"
OUT="dist/xorder-combell-${STAMP}.zip"
mkdir -p dist

zip -rq "$OUT" . \
    -x '.git/*' '.github/*' '.gitignore' 'dist/*' 'bin/*' 'mcp-crashcourse/*' \
    'features/*' 'public/features/*' \
    'config/config.php' \
    'storage/*.sqlite' 'storage/*.sqlite-*' 'storage/*.log' 'storage/logs/*' \
    '*.DS_Store'

echo "Pakket klaar: $OUT"
unzip -l "$OUT" | tail -n 1
