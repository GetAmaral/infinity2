#!/bin/bash

# Infinity - Quick Test Runner
#
# AUTOMATICALLY RUNS ALL TESTS FROM /app/tests DIRECTORY
# - Simple execution without verbose output
# - PHPUnit auto-discovers all *Test.php files recursively
# - No manual updates needed when new tests are added
#
# Usage: ./scripts/test-quick.sh (from /home/user/inf directory)

set -e

echo "🧪 Running ALL Infinity Tests from /tests directory..."

if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Please run from /home/user/inf directory"
    exit 1
fi

# Run tests
docker run --rm --network=inf_infinity_network \
    -v "$(pwd)/app:/app" \
    -w /app \
    inf-app php bin/phpunit

echo "✅ Test execution complete!"