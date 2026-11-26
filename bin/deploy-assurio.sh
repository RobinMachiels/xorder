#!/usr/bin/env bash
# Deploy XOrder naar https://assurio.be/xorder (Combell, account hellocodebe).
#
#   bash bin/deploy-assurio.sh --dry-run   toont enkel wat er zou veranderen
#   bash bin/deploy-assurio.sh             voert de deploy echt uit (vraagt eerst bevestiging)
#
# Opbouw op de server:
#   ~/xorder-app/                          app-code, config, storage (BUITEN de webroot)
#   ~/subsites/assurio.be/xorder/          enkel de inhoud van public/ (webroot)
#   ~/subsites/assurio.be/xorder/xorder-path.php  wijst naar ~/xorder-app (eenmalig aangemaakt)
#
# Nooit overschreven: config/config.php, storage/ en xorder-path.php op de server.
# Cursistfeatures (features/ en public/features/) gaan niet mee naar productie.
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_HOST="hellocodebe@ssh.hellocode.be"
REMOTE_HOME="/data/sites/web/hellocodebe"
APP_DIR="$REMOTE_HOME/xorder-app"
WEB_DIR="$REMOTE_HOME/subsites/assurio.be/xorder"

DRY=""
if [[ "${1:-}" == "--dry-run" ]]; then
    DRY="--dry-run"
    echo ">> DRY RUN: er wordt niets geupload"
else
    read -r -p "Echt deployen naar https://assurio.be/xorder ? Typ 'deploy' om verder te gaan: " answer
    [[ "$answer" == "deploy" ]] || { echo "Gestopt."; exit 1; }
fi

# Snelle syntaxcontrole van alle PHP-bestanden
if command -v php >/dev/null 2>&1; then
    find src views public config lang -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
    echo ">> PHP syntax OK"
fi

if [[ -z "$DRY" ]]; then
    ssh "$SSH_HOST" "mkdir -p '$APP_DIR/storage/logs' '$WEB_DIR'"
fi

echo ">> App-code naar $APP_DIR"
rsync -az --delete $DRY \
    --exclude '.git/' --exclude '.github/' --exclude '.gitignore' \
    --exclude 'public/' --exclude 'dist/' --exclude 'mcp-crashcourse/' \
    --exclude 'features/' --exclude 'widget/' \
    --exclude 'config/config.php' --exclude 'storage/' \
    --exclude '.DS_Store' --exclude '*.md' --exclude '.htaccess' \
    ./ "$SSH_HOST:$APP_DIR/"

echo ">> Webroot naar $WEB_DIR"
rsync -az --delete $DRY \
    --exclude 'features/' --exclude 'xorder-path.php' --exclude '.DS_Store' \
    public/ "$SSH_HOST:$WEB_DIR/"

if [[ -z "$DRY" ]]; then
    ssh "$SSH_HOST" "test -f '$WEB_DIR/xorder-path.php' || printf '%s\n' '<?php' 'return \"$APP_DIR\";' > '$WEB_DIR/xorder-path.php'"
    ssh "$SSH_HOST" "test -f '$APP_DIR/config/config.php' || echo '!! LET OP: $APP_DIR/config/config.php ontbreekt nog'"
    echo ">> Klaar. Rooktest: https://assurio.be/xorder/"
fi
