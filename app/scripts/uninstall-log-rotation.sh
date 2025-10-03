#!/bin/bash

################################################################################
# Infinity Log Rotation - Uninstall Script
################################################################################
#
# This script removes the systemd timer for log rotation.
#
# Usage:
#   chmod +x scripts/uninstall-log-rotation.sh
#   sudo ./scripts/uninstall-log-rotation.sh
#
################################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SERVICE_NAME="infinity-logs-cleanup"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
TIMER_FILE="/etc/systemd/system/${SERVICE_NAME}.timer"

print_header() {
    echo -e "${BLUE}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  Infinity Log Rotation - Uninstall"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run with sudo privileges"
        exit 1
    fi
}

main() {
    print_header

    check_root

    # Confirm uninstallation
    read -p "$(echo -e ${YELLOW}Remove log rotation timer? [y/N]:${NC} )" -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Uninstall cancelled"
        exit 0
    fi
    echo ""

    # Stop timer
    if systemctl is-active --quiet ${SERVICE_NAME}.timer; then
        echo "Stopping timer..."
        systemctl stop ${SERVICE_NAME}.timer
        print_success "Timer stopped"
    fi

    # Disable timer
    if systemctl is-enabled --quiet ${SERVICE_NAME}.timer; then
        echo "Disabling timer..."
        systemctl disable ${SERVICE_NAME}.timer
        print_success "Timer disabled"
    fi

    # Remove files
    if [ -f "$TIMER_FILE" ]; then
        rm "$TIMER_FILE"
        print_success "Timer file removed"
    fi

    if [ -f "$SERVICE_FILE" ]; then
        rm "$SERVICE_FILE"
        print_success "Service file removed"
    fi

    # Reload systemd
    systemctl daemon-reload
    print_success "Systemd daemon reloaded"

    echo ""
    print_success "Log rotation timer uninstalled successfully"
    echo ""
    print_warning "Note: Monolog rotation is still configured in config/packages/monolog.yaml"
}

main
