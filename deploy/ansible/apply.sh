#!/bin/bash
# =============================================================================
# Little Steps — apply the server configuration
# =============================================================================
# Idempotent. Run it for the first install and again after any change to
# deploy/docker-compose.server.yml, the playbook, or deploy/ansible/.env.
#
# Usage:
#   ./apply.sh                 # uses SERVER_IP from .env
#   ./apply.sh 3.121.93.121    # or pass the IP explicitly
# =============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ ! -f "$SCRIPT_DIR/.env" ]; then
    echo -e "${RED}Error: .env not found${NC}"
    echo "Run: cp .env.example .env  — then fill in the values"
    exit 1
fi

set -a
# shellcheck disable=SC1091
source "$SCRIPT_DIR/.env"
set +a

SERVER_IP="${1:-$SERVER_IP}"
if [ -z "$SERVER_IP" ]; then
    echo -e "${RED}Error: no server IP${NC}"
    echo "Set SERVER_IP in .env or pass it: ./apply.sh 3.121.93.121"
    exit 1
fi

SSH_KEY="${SSH_KEY:-$SCRIPT_DIR/ssh_key}"
if [ ! -f "$SSH_KEY" ]; then
    echo -e "${RED}Error: SSH key not found at $SSH_KEY${NC}"
    echo "Symlink the one the vault repo already uses:"
    echo "  ln -s ../../../vault/ansible/ssh_key $SCRIPT_DIR/ssh_key"
    exit 1
fi
chmod 600 "$SSH_KEY" 2>/dev/null || true

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Little Steps - Server Configuration${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""
echo -e "Server : ${YELLOW}${SERVER_IP}${NC}"
echo -e "Domain : ${YELLOW}${LITTLESTEPS_DOMAIN:-littlesteps.freskimveliu.dev}${NC}"
echo -e "Image  : ${YELLOW}ghcr.io/freskimveliu/littlesteps-web:${DOCKER_IMAGE_TAG:-main}${NC}"
echo ""

INVENTORY_FILE=$(mktemp)
cat > "$INVENTORY_FILE" << EOF
[littlesteps]
${SERVER_IP} ansible_ssh_private_key_file=${SSH_KEY} ansible_user=ubuntu

[littlesteps:vars]
ansible_python_interpreter=/usr/bin/python3
EOF

trap 'rm -f "$INVENTORY_FILE"' EXIT

echo -e "${YELLOW}Running the playbook...${NC}"
echo ""

ansible-playbook -i "$INVENTORY_FILE" "$SCRIPT_DIR/deploy-littlesteps.yml"

echo ""
echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Done.${NC}"
echo -e "${GREEN}==================================================${NC}"
