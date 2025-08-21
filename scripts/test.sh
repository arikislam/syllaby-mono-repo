#!/bin/bash

# Syllaby Testing Script
echo "🧪 Running Laravel Tests..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Check if .env.testing exists
if [ ! -f ".env.testing" ]; then
    echo "📝 Creating .env.testing file from template..."
    cp env.testing.example .env.testing
fi

# Start services if not running
echo "🐳 Ensuring services are running..."
docker-compose -f docker-compose.dev.yml up -d mysql redis

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until docker-compose -f docker-compose.dev.yml exec mysql mysqladmin ping -h"localhost" --silent; do
    echo "  MySQL is unavailable - sleeping"
    sleep 2
done
echo "✅ MySQL is ready!"

# Run Laravel tests in the backend container
echo "🧪 Running PHPUnit tests..."
docker-compose -f docker-compose.dev.yml exec backend-dev php artisan test --env=testing

echo "✅ Tests completed!"
