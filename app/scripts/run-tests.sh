#!/bin/bash
set -e

# Test Runner Script
# Comprehensive test execution for TURBO Generator System

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  TURBO Generator - Test Suite"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Change to project directory
cd "$(dirname "$0")/.."

# Parse options
COVERAGE=false
SUITE=""
FILTER=""
STOP_ON_FAILURE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage)
            COVERAGE=true
            shift
            ;;
        --suite=*)
            SUITE="${1#*=}"
            shift
            ;;
        --filter=*)
            FILTER="${1#*=}"
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --coverage           Generate code coverage report"
            echo "  --suite=NAME         Run specific test suite (unit, integration, functional)"
            echo "  --filter=PATTERN     Run tests matching pattern"
            echo "  --stop-on-failure    Stop on first failure"
            echo "  --help, -h           Show this help"
            echo ""
            echo "Examples:"
            echo "  $0                                    # Run all tests"
            echo "  $0 --coverage                         # Run with coverage"
            echo "  $0 --suite=unit                       # Run unit tests only"
            echo "  $0 --filter=CsvParser                 # Run CsvParser tests"
            echo "  $0 --stop-on-failure --suite=integration"
            echo ""
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Build command
CMD="php bin/phpunit"

if [ "$COVERAGE" = true ]; then
    echo "📊 Running tests with coverage..."
    CMD="$CMD --coverage-html coverage/"
fi

if [ -n "$SUITE" ]; then
    echo "🎯 Running test suite: $SUITE"
    CMD="$CMD --testsuite=$SUITE"
fi

if [ -n "$FILTER" ]; then
    echo "🔍 Filtering tests: $FILTER"
    CMD="$CMD --filter=$FILTER"
fi

if [ "$STOP_ON_FAILURE" = true ]; then
    CMD="$CMD --stop-on-failure"
fi

# Run tests
echo "🧪 Executing: $CMD"
echo ""

$CMD

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ All tests passed!"
    
    if [ "$COVERAGE" = true ]; then
        echo ""
        echo "📊 Coverage report generated: coverage/index.html"
    fi
else
    echo "❌ Tests failed with exit code: $EXIT_CODE"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit $EXIT_CODE
