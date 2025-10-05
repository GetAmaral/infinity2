#!/bin/bash
set -e

echo "🚀 Setting up Luminai - Modern Symfony UX Application"
echo "================================================="

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "❌ Docker is required"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "❌ Docker Compose is required"; exit 1; }

# Navigate to project root
cd "$(dirname "$0")/.."

# Generate SSL certificates
echo "🔐 Generating SSL certificates..."
chmod +x scripts/generate-ssl.sh
./scripts/generate-ssl.sh

# Verify .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please create it first."
    exit 1
fi

# Start database first and wait for it to be ready
echo "🗄️ Starting database..."
docker-compose up -d database

echo "⏳ Waiting for database to be ready..."
timeout 60 bash -c 'until docker-compose exec -T database pg_isready -U ${POSTGRES_USER:-luminai_user} -d ${POSTGRES_DB:-luminai_db} >/dev/null 2>&1; do sleep 2; done'

# Build and start application
echo "🏗️ Building and starting application..."
docker-compose up --build -d app

echo "⏳ Waiting for application to be ready..."
timeout 120 bash -c 'until docker-compose exec -T app wget --no-verbose --tries=1 --spider http://localhost:8000/health >/dev/null 2>&1; do sleep 5; done'

# Setup database
echo "🗄️ Setting up database..."
docker-compose exec -T app php bin/console doctrine:database:create --if-not-exists
docker-compose exec -T app php bin/console make:migration --no-interaction
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

# Start nginx
echo "🌐 Starting nginx..."
docker-compose up -d nginx

# Final check
echo "🏥 Running health checks..."
sleep 10
if curl -sf https://localhost/health >/dev/null 2>&1; then
    echo "✅ Setup completed successfully!"
    echo ""
    echo "🌐 Application URLs:"
    echo "   Frontend:  https://localhost"
    echo "   API:       https://localhost/api"
    echo "   Health:    https://localhost/health"
    echo ""
    echo "📋 Useful commands:"
    echo "   View logs:    docker-compose logs -f"
    echo "   Stop all:     docker-compose down"
    echo "   Enter app:    docker-compose exec app sh"
else
    echo "⚠️ Setup completed but health check failed"
    echo "Check logs: docker-compose logs"
fi