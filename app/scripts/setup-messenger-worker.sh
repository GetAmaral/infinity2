#!/bin/bash

################################################################################
# Infinity Messenger Worker - Automated Setup Script
################################################################################
#
# This script automatically configures systemd service for Messenger worker
# to process async audit events and other background tasks.
#
# Usage:
#   chmod +x scripts/setup-messenger-worker.sh
#   sudo ./scripts/setup-messenger-worker.sh
#
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
APP_DIR="/home/user/inf/app"
SERVICE_NAME="infinity-messenger-worker"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
PHP_BIN="/usr/bin/php"

print_header() {
    echo -e "${BLUE}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  Infinity Messenger Worker - Automated Setup"
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

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run with sudo privileges"
        echo ""
        print_info "Please run: sudo ./scripts/setup-messenger-worker.sh"
        exit 1
    fi
}

check_systemd() {
    if ! command -v systemctl &> /dev/null; then
        print_error "systemd not found"
        exit 1
    fi
    print_success "systemd detected"
}

check_php() {
    if ! command -v php &> /dev/null; then
        print_error "PHP not found"
        exit 1
    fi
    print_success "PHP found: $(php -v | head -n 1)"
}

check_app_directory() {
    if [ ! -d "$APP_DIR" ]; then
        print_error "Application directory not found: $APP_DIR"
        exit 1
    fi
    if [ ! -f "$APP_DIR/bin/console" ]; then
        print_error "Symfony console not found"
        exit 1
    fi
    print_success "Application directory verified"
}

detect_php_user() {
    local detected_user=""

    if id "www-data" &>/dev/null; then
        detected_user="www-data"
    elif id "nginx" &>/dev/null; then
        detected_user="nginx"
    elif id "apache" &>/dev/null; then
        detected_user="apache"
    else
        detected_user=$(stat -c '%U' "$APP_DIR")
    fi

    echo "$detected_user"
}

create_systemd_service() {
    print_info "Creating systemd service file..."

    local php_user=$(detect_php_user)
    print_info "Detected PHP user: $php_user"

    cat > "$SERVICE_FILE" << EOF
[Unit]
Description=Infinity Messenger Worker - Async Task Processor
After=network.target postgresql.service
Documentation=file://$APP_DIR/docs/ASYNC_AUDIT_LOGGING.md

[Service]
Type=simple
User=$php_user
Group=$php_user
WorkingDirectory=$APP_DIR
ExecStart=$PHP_BIN $APP_DIR/bin/console messenger:consume async --time-limit=3600
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=infinity-messenger

# Performance & Security
LimitNOFILE=65536
PrivateTmp=true
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF

    print_success "Service file created: $SERVICE_FILE"
}

enable_and_start_service() {
    print_info "Reloading systemd daemon..."
    systemctl daemon-reload
    print_success "Systemd daemon reloaded"

    print_info "Enabling service..."
    systemctl enable ${SERVICE_NAME}.service
    print_success "Service enabled"

    print_info "Starting service..."
    systemctl start ${SERVICE_NAME}.service
    print_success "Service started"
}

show_status() {
    echo ""
    print_info "Service status:"
    echo ""
    systemctl status ${SERVICE_NAME}.service --no-pager || true
}

print_summary() {
    echo ""
    echo -e "${GREEN}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  ✓ Messenger Worker Setup Complete!"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
    echo ""
    echo "Configuration:"
    echo "  • Service: $SERVICE_FILE"
    echo "  • Queue: async (Doctrine transport)"
    echo "  • Restart: Automatic on failure"
    echo "  • Time limit: 3600s (1 hour, then auto-restart)"
    echo ""
    echo "Useful Commands:"
    echo "  • Check status:    systemctl status ${SERVICE_NAME}"
    echo "  • View logs:       journalctl -u ${SERVICE_NAME} -f"
    echo "  • Queue stats:     cd $APP_DIR && php bin/console messenger:stats"
    echo "  • Restart:         sudo systemctl restart ${SERVICE_NAME}"
    echo "  • Stop:            sudo systemctl stop ${SERVICE_NAME}"
    echo ""
    echo "What It Does:"
    echo "  • Processes async audit events"
    echo "  • Handles video processing jobs"
    echo "  • Sends emails and notifications"
    echo "  • Retries failed messages automatically"
    echo ""
}

main() {
    print_header

    # Pre-flight checks
    echo "Running pre-flight checks..."
    check_root
    check_systemd
    check_php
    check_app_directory
    echo ""

    # Confirm installation
    read -p "$(echo -e ${YELLOW}Continue with messenger worker setup? [y/N]:${NC} )" -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Setup cancelled"
        exit 0
    fi
    echo ""

    # Create systemd service
    create_systemd_service
    echo ""

    # Enable and start
    enable_and_start_service
    echo ""

    # Wait for service to start
    sleep 2

    # Show status
    show_status

    # Summary
    print_summary
}

main
