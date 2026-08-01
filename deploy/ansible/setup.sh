#!/bin/bash
# =============================================================================
# Little Steps — provision a server and deploy onto it
# =============================================================================
# Run this once, for a brand new server. Afterwards use ./apply.sh.
#
#   ./setup.sh
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

if [ -n "${SERVER_IP:-}" ]; then
    echo -e "${YELLOW}SERVER_IP is already set to ${SERVER_IP}.${NC}"
    echo "setup.sh creates a NEW instance — use ./apply.sh to update the existing one."
    read -r -p "Create another server anyway? [y/N] " reply
    [ "$reply" = "y" ] || [ "$reply" = "Y" ] || exit 0
fi

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Little Steps - Provision & Deploy${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""
echo -e "Region : ${YELLOW}${AWS_REGION:-eu-central-1}${NC}"
echo -e "Type   : ${YELLOW}${INSTANCE_TYPE:-t3.small}${NC}"
echo -e "Domain : ${YELLOW}${LITTLESTEPS_DOMAIN:-littlesteps.freskimveliu.dev}${NC}"
echo ""

ansible-playbook "$SCRIPT_DIR/create-and-setup-ec2.yml"

echo ""
echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Done. Put the printed IP in .env as SERVER_IP,${NC}"
echo -e "${GREEN}and point DNS at it. Later changes: ./apply.sh${NC}"
echo -e "${GREEN}==================================================${NC}"
