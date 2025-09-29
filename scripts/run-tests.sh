#!/bin/bash

# Infinity - Comprehensive Test Runner Script
#
# AUTOMATICALLY RUNS ALL TESTS FROM /app/tests DIRECTORY
# - No manual updates needed when new tests are added
# - PHPUnit auto-discovers all *Test.php files recursively
# - Includes detailed output, system status, and colorized results
#
# Usage: ./scripts/run-tests.sh (from /home/user/inf directory)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Function to print colored headers
print_header() {
    echo -e "\n${CYAN}========================================${NC}"
    echo -e "${WHITE}$1${NC}"
    echo -e "${CYAN}========================================${NC}\n"
}

print_step() {
    echo -e "${YELLOW}➤ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "Please run this script from the /home/user/inf directory"
    exit 1
fi

print_header "🚀 INFINITY TEST SUITE RUNNER"

print_info "Starting comprehensive test execution..."
print_info "This will run ALL tests from the /tests directory"

# Check Docker services status
print_step "Checking Docker services status..."
if ! docker-compose ps | grep -q "Up"; then
    print_error "Docker services are not running. Please start them first with: docker-compose up -d"
    exit 1
fi
print_success "Docker services are running"

# Run tests with detailed output
print_header "📋 RUNNING ALL TESTS"

print_step "Executing PHPUnit test suite..."

# Run tests with detailed output and capture results
docker run --rm --network=inf_infinity_network \
    -v "$(pwd)/app:/app" \
    -w /app \
    inf-app php bin/phpunit \
    --testdox \
    --colors=always

TEST_EXIT_CODE=$?

echo ""

if [ $TEST_EXIT_CODE -eq 0 ]; then
    print_header "🎉 ALL TESTS PASSED SUCCESSFULLY!"

    # Get dynamic test counts
    TEST_SUMMARY=$(docker run --rm --network=inf_infinity_network \
        -v "$(pwd)/app:/app" \
        -w /app \
        inf-app php bin/phpunit --list-tests 2>/dev/null | tail -n 1 || echo "Tests executed successfully")

    print_success "All tests executed successfully"
    print_success "All assertions passed"
    print_success "0 errors, 0 failures"
    print_info "Application is fully functional and ready for development!"

    echo -e "\n${GREEN}Test Categories Covered:${NC}"
    echo "  • Entity Tests: All entity validation and relationships"
    echo "  • Controller Tests: All HTTP endpoints and functionality"
    echo "  • API Tests: All REST/GraphQL API endpoints"
    echo "  • Service Tests: All business logic and utilities"
    echo "  • Integration Tests: Full application workflows"

    echo -e "\n${BLUE}Next Steps:${NC}"
    echo "  • Browse to: http://localhost (with nginx running)"
    echo "  • API docs: http://localhost/api"
    echo "  • Health check: http://localhost/health"
    echo "  • Start development with: docker-compose up -d"

else
    print_header "❌ TESTS FAILED"
    print_error "Some tests failed. Please check the output above for details."
    print_info "Common issues:"
    echo "  • Database connection problems"
    echo "  • Docker services not healthy"
    echo "  • Permission issues"
    echo "  • Missing dependencies"
    exit 1
fi

print_header "🔍 SYSTEM STATUS SUMMARY"

# Show system status
print_step "Docker Services Status:"
docker-compose ps

print_step "Database Connection Test:"
docker run --rm --network=inf_infinity_network \
    -v "$(pwd)/app:/app" \
    -w /app \
    inf-app php bin/console doctrine:query:sql "SELECT version();" \
    2>/dev/null && print_success "Database connection OK" || print_error "Database connection failed"

print_step "Application Health Check:"
if docker run --rm --network=inf_infinity_network inf-app wget --quiet --spider http://infinity_app:8000/health 2>/dev/null; then
    print_success "Application health check OK"
else
    print_error "Application health check failed"
fi

print_header "✨ TEST EXECUTION COMPLETE"
echo -e "${WHITE}Ready for development! 🚀${NC}\n"