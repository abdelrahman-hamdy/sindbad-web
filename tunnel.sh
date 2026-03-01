#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  Sindbad V2 — Local Tunnel Script
#  Starts ngrok (2 tunnels), Reverb, and queue worker.
#  Patches .env with live URLs and restores them on exit.
# ─────────────────────────────────────────────────────────────
set -e

GREEN='\033[0;32m'; CYAN='\033[0;36m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"
NGROK_TUNNEL_CONFIG="$SCRIPT_DIR/ngrok-tunnels.yml"
NGROK_GLOBAL_CONFIG="$HOME/Library/Application Support/ngrok/ngrok.yml"

# ── Save original .env values ────────────────────────────────
ORIG_APP_URL=$(grep    "^APP_URL="       "$ENV_FILE" | cut -d= -f2-)
ORIG_REVERB_HOST=$(grep "^REVERB_HOST=" "$ENV_FILE" | cut -d= -f2-)
ORIG_REVERB_PORT=$(grep "^REVERB_PORT=" "$ENV_FILE" | cut -d= -f2-)
ORIG_REVERB_SCHEME=$(grep "^REVERB_SCHEME=" "$ENV_FILE" | cut -d= -f2-)

# ── Cleanup on Ctrl-C / exit ─────────────────────────────────
cleanup() {
    echo -e "\n${YELLOW}Shutting down services...${NC}"
    pkill -f "reverb:start" 2>/dev/null || true
    pkill -f "queue:work"   2>/dev/null || true
    pkill -f "ngrok"        2>/dev/null || true

    # Restore .env
    sed -i '' "s|^APP_URL=.*|APP_URL=$ORIG_APP_URL|"             "$ENV_FILE"
    sed -i '' "s|^REVERB_HOST=.*|REVERB_HOST=$ORIG_REVERB_HOST|" "$ENV_FILE"
    sed -i '' "s|^REVERB_PORT=.*|REVERB_PORT=$ORIG_REVERB_PORT|" "$ENV_FILE"
    sed -i '' "s|^REVERB_SCHEME=.*|REVERB_SCHEME=$ORIG_REVERB_SCHEME|" "$ENV_FILE"
    cd "$SCRIPT_DIR" && php artisan config:clear --quiet
    echo -e "${GREEN}.env restored to local values. Bye!${NC}"
}
trap cleanup EXIT INT TERM

# ── Kill any leftover processes ───────────────────────────────
pkill -f ngrok          2>/dev/null || true
pkill -f "reverb:start" 2>/dev/null || true
pkill -f "queue:work"   2>/dev/null || true
sleep 1

# ── Start ngrok with both tunnels ────────────────────────────
echo -e "${CYAN}Starting ngrok tunnels (app:80, reverb:8080)...${NC}"
ngrok start --all \
    --config "$NGROK_GLOBAL_CONFIG" \
    --config "$NGROK_TUNNEL_CONFIG" \
    --log /tmp/ngrok-sindbad.log \
    --log-format json \
    > /dev/null 2>&1 &
NGROK_PID=$!

# ── Wait until BOTH tunnels appear in the API (not just API ready) ────
echo -n "Waiting for ngrok tunnels"
for i in $(seq 1 30); do
    TUNNEL_COUNT=$(curl -s http://localhost:4040/api/tunnels 2>/dev/null | \
        python3 -c "import sys,json; print(len(json.load(sys.stdin).get('tunnels',[])))" 2>/dev/null || echo 0)
    if [ "$TUNNEL_COUNT" -ge 2 ]; then
        echo -e " ${GREEN}ready${NC}"; break
    fi
    if [ "$i" -eq 30 ]; then
        echo -e " ${RED}timed out!${NC}"
        echo "ngrok log:"; tail -20 /tmp/ngrok-sindbad.log
        exit 1
    fi
    echo -n "."; sleep 1
done

# ── Parse URLs from ngrok API ─────────────────────────────────
TUNNELS_JSON=$(curl -s http://localhost:4040/api/tunnels)

# Match by tunnel name only (not proto — both tunnels may share the same domain)
APP_URL=$(echo "$TUNNELS_JSON" | python3 -c "
import sys, json
for t in json.load(sys.stdin)['tunnels']:
    if t['name'] == 'app':
        print(t['public_url'].replace('http://', 'https://')); break
" 2>/dev/null)

REVERB_URL=$(echo "$TUNNELS_JSON" | python3 -c "
import sys, json
for t in json.load(sys.stdin)['tunnels']:
    if t['name'] == 'reverb':
        print(t['public_url'].replace('http://', 'https://')); break
" 2>/dev/null)

if [ -z "$APP_URL" ] || [ -z "$REVERB_URL" ]; then
    echo -e "${RED}Failed to read tunnel URLs. ngrok log:${NC}"
    tail -30 /tmp/ngrok-sindbad.log
    exit 1
fi

# ngrok free tier routes HTTP→:80 and WebSocket upgrades→:8080 on the same domain.
# So REVERB_HOST == APP_HOST is expected and correct.
REVERB_HOST=$(echo "$REVERB_URL" | sed 's|https://||')

# ── Patch .env with tunnel values ────────────────────────────
sed -i '' "s|^APP_URL=.*|APP_URL=$APP_URL|"                     "$ENV_FILE"
sed -i '' "s|^REVERB_HOST=.*|REVERB_HOST=$REVERB_HOST|"         "$ENV_FILE"
sed -i '' "s|^REVERB_PORT=.*|REVERB_PORT=443|"                  "$ENV_FILE"
sed -i '' "s|^REVERB_SCHEME=.*|REVERB_SCHEME=https|"            "$ENV_FILE"
cd "$SCRIPT_DIR" && php artisan config:clear --quiet

# ── Start Reverb ─────────────────────────────────────────────
echo -e "${CYAN}Starting Reverb WebSocket server on :8080...${NC}"
php "$SCRIPT_DIR/artisan" reverb:start --host=0.0.0.0 --port=8080 \
    > /tmp/reverb-sindbad.log 2>&1 &

# ── Start queue worker ────────────────────────────────────────
echo -e "${CYAN}Starting queue worker...${NC}"
php "$SCRIPT_DIR/artisan" queue:work --sleep=3 --tries=3 \
    > /tmp/queue-sindbad.log 2>&1 &

sleep 2

# ── Print ready summary ───────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║        SINDBAD TUNNEL READY                  ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Admin Panel:${NC}    $APP_URL/admin"
echo -e "  ${CYAN}API Base URL:${NC}   $APP_URL/api"
echo -e "  ${CYAN}Reverb WSS:${NC}     wss://$REVERB_HOST:443"
echo ""
echo -e "${YELLOW}─── Logs ────────────────────────────────────────${NC}"
echo -e "  ngrok:   tail -f /tmp/ngrok-sindbad.log"
echo -e "  reverb:  tail -f /tmp/reverb-sindbad.log"
echo -e "  queue:   tail -f /tmp/queue-sindbad.log"
echo ""
echo -e "${GREEN}Press Ctrl+C to stop all services and restore .env${NC}"
echo ""

# Keep alive until Ctrl-C
wait $NGROK_PID
