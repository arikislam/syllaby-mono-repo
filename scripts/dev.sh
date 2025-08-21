#!/bin/bash

# Syllaby Monorepo Development Script
echo "🚀 Starting Syllaby Development Environment..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Check if repos exist
if [ ! -d "backend" ]; then
    echo "📥 Cloning backend repository..."
    git clone https://github.com/Syllaby-ai/social_media_application.git backend
fi

if [ ! -d "frontend" ]; then
    echo "📥 Cloning frontend repository..."
    git clone https://github.com/Syllaby-ai/syllaby-react-frontend.git frontend
fi

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file from template..."
    cp env.example .env
fi

# Create .env.testing if it doesn't exist
if [ ! -f ".env.testing" ]; then
    echo "📝 Creating .env.testing file from template..."
    cp env.testing.example .env.testing
fi

# Start development environment
echo "🐳 Starting Docker containers in development mode..."
docker-compose -f docker-compose.dev.yml up --build

echo "✅ Development environment is ready!"
echo "🌐 Frontend: http://localhost:3330"
echo "🔧 Backend (Laravel): http://localhost:8880"
echo "🗄️  MySQL: localhost:3906"
echo "   📊 Databases: syllaby, syllaby_testing, syllaby_pulse"
echo "📮 Redis: localhost:6930"
echo "✉️  MailHog: http://localhost:8062"
echo "🐛 Xdebug: port 9230"
