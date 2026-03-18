#!/bin/bash

# CampaignFlow Docker Deployment Script
# Usage: ./scripts/deploy-docker.sh

echo "🚀 Starting CampaignFlow Deployment..."

# 1. Pull latest changes (if in a git repo)
if [ -d ".git" ]; then
    echo "📥 Pulling latest code..."
    git pull origin main
fi

# 2. Build and restart containers
echo "🏗️ Building and restarting containers..."
docker compose up -d --build

# 3. Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
until docker compose exec -T postgres pg_isready -U $(docker compose exec -T app php -r "echo env('DB_USERNAME');") > /dev/null 2>&1; do
  sleep 2
done

# 4. Run migrations
echo "🗄️ Running database migrations..."
docker compose exec -T app php artisan migrate --force

# 5. Optimize Laravel
echo "⚡ Optimizing Laravel performance..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan event:cache

# 6. Restart Horizon & Workers
echo "🔄 Restarting queue workers..."
docker compose exec -T app php artisan horizon:terminate

# 7. Cleanup
echo "🧹 Cleaning up old images..."
docker image prune -f

echo "✅ Deployment complete! Health status:"
curl -s http://localhost:8000/health | grep -o '"status":"[^"]*"'
