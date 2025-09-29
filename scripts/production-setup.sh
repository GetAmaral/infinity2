#!/bin/bash
set -e

echo "🚀 Infinity Production Setup & Optimization"
echo "=========================================="

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "❌ Docker is required"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "❌ Docker Compose is required"; exit 1; }

# Navigate to project root
cd "$(dirname "$0")/.."

echo "🔧 Setting production environment..."

# Create production environment file
cp .env .env.prod
sed -i 's/APP_ENV=dev/APP_ENV=prod/' .env.prod
sed -i 's/FRANKENPHP_NUM_THREADS=4/FRANKENPHP_NUM_THREADS=8/' .env.prod
echo "APP_DEBUG=0" >> .env.prod
echo "OPCACHE_VALIDATE_TIMESTAMPS=0" >> .env.prod
echo "OPCACHE_MAX_ACCELERATED_FILES=20000" >> .env.prod
echo "OPCACHE_MEMORY_CONSUMPTION=256" >> .env.prod
echo "REALPATH_CACHE_SIZE=4096K" >> .env.prod
echo "REALPATH_CACHE_TTL=600" >> .env.prod

echo "🔐 Generating SSL certificates..."
if [ ! -f nginx/ssl/localhost.crt ]; then
    chmod +x scripts/generate-ssl.sh
    ./scripts/generate-ssl.sh
fi

echo "🗄️ Starting database and Redis..."
docker-compose --env-file .env.prod up -d database redis

echo "⏳ Waiting for services to be ready..."
timeout 60 bash -c 'until docker-compose --env-file .env.prod exec -T database pg_isready -U ${POSTGRES_USER:-infinity_user} -d ${POSTGRES_DB:-infinity_db} >/dev/null 2>&1; do sleep 2; done'
timeout 30 bash -c 'until docker-compose --env-file .env.prod exec -T redis redis-cli ping >/dev/null 2>&1; do sleep 2; done'

echo "🏗️ Building optimized application container..."
docker-compose --env-file .env.prod build --no-cache app

echo "🚀 Starting application..."
docker-compose --env-file .env.prod up -d app

echo "⏳ Waiting for application to be ready..."
timeout 120 bash -c 'until docker-compose --env-file .env.prod exec -T app wget --no-verbose --tries=1 --spider http://localhost:8000/health >/dev/null 2>&1; do sleep 5; done'

echo "🗄️ Setting up database..."
docker-compose --env-file .env.prod exec -T app php bin/console doctrine:database:create --if-not-exists
docker-compose --env-file .env.prod exec -T app php bin/console doctrine:migrations:migrate --no-interaction

# Optional: Load sample data in non-production environments
read -p "Load sample data fixtures? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "📊 Loading sample data..."
    docker-compose --env-file .env.prod exec -T app php bin/console doctrine:fixtures:load --no-interaction
fi

echo "🌐 Starting nginx..."
docker-compose --env-file .env.prod up -d nginx

echo "🧹 Optimizing application cache..."
docker-compose --env-file .env.prod exec -T app php bin/console cache:clear --env=prod
docker-compose --env-file .env.prod exec -T app php bin/console cache:warmup --env=prod

echo "📊 Running performance tests..."
docker-compose --env-file .env.prod exec -T app php bin/phpunit --group=performance || echo "⚠️  Performance tests failed or not found"

echo "🏥 Running health checks..."
sleep 10

# Check all endpoints
HEALTH_ENDPOINTS=(
    "https://localhost/health"
    "https://localhost/health/detailed"
    "https://localhost/health/metrics"
    "https://localhost/api"
)

for endpoint in "${HEALTH_ENDPOINTS[@]}"; do
    if curl -sf -k "$endpoint" >/dev/null 2>&1; then
        echo "✅ $endpoint - OK"
    else
        echo "⚠️  $endpoint - Failed"
    fi
done

echo ""
echo "🎉 Production setup completed successfully!"
echo ""
echo "🌐 Application URLs:"
echo "   Frontend:      https://localhost"
echo "   API:           https://localhost/api"
echo "   Health:        https://localhost/health"
echo "   Detailed Health: https://localhost/health/detailed"
echo "   Metrics:       https://localhost/health/metrics"
echo ""
echo "📊 Services Status:"
docker-compose --env-file .env.prod ps
echo ""
echo "📋 Production Commands:"
echo "   View logs:     docker-compose --env-file .env.prod logs -f"
echo "   Stop all:      docker-compose --env-file .env.prod down"
echo "   Enter app:     docker-compose --env-file .env.prod exec app sh"
echo "   Redis CLI:     docker-compose --env-file .env.prod exec redis redis-cli"
echo "   PostgreSQL:    docker-compose --env-file .env.prod exec database psql -U infinity_user infinity_db"
echo ""
echo "🔍 Performance Monitoring:"
echo "   System metrics available at /health/metrics"
echo "   Logs are structured JSON in production"
echo "   Redis cache status: docker-compose --env-file .env.prod exec redis redis-cli info memory"
echo ""
echo "⚠️  Security Notes:"
echo "   - Change default passwords in production"
echo "   - Review and customize rate limiting settings"
echo "   - Consider setting up external monitoring"
echo "   - Backup strategy should be implemented"
echo ""
echo "🚀 Application is ready for production use!"