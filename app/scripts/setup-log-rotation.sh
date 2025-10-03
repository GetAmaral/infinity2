#!/bin/bash

################################################################################
# Infinity Log Rotation - Automated Setup Script
################################################################################
#
# This script automatically configures systemd timer for log rotation,
# compression, and cleanup.
#
# Usage:
#   chmod +x scripts/setup-log-rotation.sh
#   ./scripts/setup-log-rotation.sh
#
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/home/user/inf/app"
SERVICE_NAME="infinity-logs-cleanup"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
TIMER_FILE="/etc/systemd/system/${SERVICE_NAME}.timer"
PHP_BIN="/usr/bin/php"

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  Infinity Log Rotation - Automated Setup"
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
        print_info "Please run: sudo ./scripts/setup-log-rotation.sh"
        exit 1
    fi
}

check_systemd() {
    if ! command -v systemctl &> /dev/null; then
        print_error "systemd not found. This script requires a systemd-based Linux distribution."
        exit 1
    fi
    print_success "systemd detected"
}

check_php() {
    if ! command -v php &> /dev/null; then
        print_error "PHP not found. Please install PHP first."
        exit 1
    fi
    PHP_VERSION=$(php -v | head -n 1)
    print_success "PHP found: $PHP_VERSION"
}

check_app_directory() {
    if [ ! -d "$APP_DIR" ]; then
        print_error "Application directory not found: $APP_DIR"
        exit 1
    fi
    if [ ! -f "$APP_DIR/bin/console" ]; then
        print_error "Symfony console not found in $APP_DIR/bin/console"
        exit 1
    fi
    print_success "Application directory verified"
}

detect_php_user() {
    # Try to detect the correct user for PHP-FPM or web server
    local detected_user=""

    if id "www-data" &>/dev/null; then
        detected_user="www-data"
    elif id "nginx" &>/dev/null; then
        detected_user="nginx"
    elif id "apache" &>/dev/null; then
        detected_user="apache"
    else
        # Fall back to the user who owns the app directory
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
Description=Infinity Log Cleanup and Compression
After=network.target
Documentation=file://$APP_DIR/docs/LOG_ROTATION_SETUP.md

[Service]
Type=oneshot
User=$php_user
Group=$php_user
WorkingDirectory=$APP_DIR
ExecStart=$PHP_BIN $APP_DIR/bin/console app:logs:cleanup --env=prod
StandardOutput=journal
StandardError=journal
SyslogIdentifier=infinity-logs-cleanup

[Install]
WantedBy=multi-user.target
EOF

    print_success "Service file created: $SERVICE_FILE"
}

create_systemd_timer() {
    print_info "Creating systemd timer file..."

    cat > "$TIMER_FILE" << EOF
[Unit]
Description=Daily Infinity Log Cleanup at 2:00 AM
Requires=$SERVICE_NAME.service
Documentation=file://$APP_DIR/docs/LOG_ROTATION_SETUP.md

[Timer]
OnCalendar=daily
OnCalendar=02:00
Persistent=true
AccuracySec=1min

[Install]
WantedBy=timers.target
EOF

    print_success "Timer file created: $TIMER_FILE"
}

enable_and_start_timer() {
    print_info "Reloading systemd daemon..."
    systemctl daemon-reload
    print_success "Systemd daemon reloaded"

    print_info "Enabling timer..."
    systemctl enable ${SERVICE_NAME}.timer
    print_success "Timer enabled"

    print_info "Starting timer..."
    systemctl start ${SERVICE_NAME}.timer
    print_success "Timer started"
}

test_service() {
    print_info "Testing service with dry-run..."

    cd "$APP_DIR"
    sudo -u $(detect_php_user) php bin/console app:logs:cleanup --dry-run --env=prod

    if [ $? -eq 0 ]; then
        print_success "Service test passed"
    else
        print_error "Service test failed"
        return 1
    fi
}

show_status() {
    echo ""
    print_info "Current timer status:"
    echo ""
    systemctl status ${SERVICE_NAME}.timer --no-pager || true

    echo ""
    print_info "Next scheduled runs:"
    echo ""
    systemctl list-timers ${SERVICE_NAME}.timer --no-pager || true
}

print_summary() {
    echo ""
    echo -e "${GREEN}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  ✓ Log Rotation Setup Complete!"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
    echo ""
    echo "Configuration:"
    echo "  • Service: $SERVICE_FILE"
    echo "  • Timer: $TIMER_FILE"
    echo "  • Schedule: Daily at 2:00 AM"
    echo "  • Compression: Logs older than 7 days"
    echo "  • Deletion: Compressed logs older than 90 days"
    echo ""
    echo "Useful Commands:"
    echo "  • Check status:    systemctl status ${SERVICE_NAME}.timer"
    echo "  • View logs:       journalctl -u ${SERVICE_NAME}.service -n 50"
    echo "  • Run manually:    sudo systemctl start ${SERVICE_NAME}.service"
    echo "  • Test dry-run:    cd $APP_DIR && php bin/console app:logs:cleanup --dry-run"
    echo "  • Disable:         sudo systemctl disable ${SERVICE_NAME}.timer"
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
    read -p "$(echo -e ${YELLOW}Continue with log rotation setup? [y/N]:${NC} )" -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Setup cancelled"
        exit 0
    fi
    echo ""

    # Create systemd files
    create_systemd_service
    create_systemd_timer
    echo ""

    # Enable and start
    enable_and_start_timer
    echo ""

    # Test the service
    test_service
    echo ""

    # Show status
    show_status

    # Summary
    print_summary
}

# Run main function
main
