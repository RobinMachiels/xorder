#!/usr/bin/env bash
# Bouwt een uploadklaar zip-pakket voor Combell. Uploadt NIETS.
set -euo pipefail
cd "$(dirname "$0")/.."

STAMP="$(date +%Y%m%d-%H%M)"
OUT="dist/xorder-combell-${STAMP}.zip"
mkdir -p dist

zip -rq "$OUT" . \
    -x '.git/*' '.gitignore' 'dist/*' 'bin/*' \
    'config/config.php' \
    'storage/*.sqlite' 'storage/*.sqlite-*' 'storage/*.log' \
    '*.DS_Store' 'widget/preview.html'

echo "Pakket klaar: $OUT"
unzip -l "$OUT" | tail -n 1
