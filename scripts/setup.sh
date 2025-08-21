#!/bin/bash

# Syllaby Monorepo Setup Script
echo "🔧 Setting up Syllaby Monorepo..."

# Clone repositories if they don't exist
if [ ! -d "backend" ]; then
    echo "📥 Cloning backend repository..."
    git clone https://github.com/Syllaby-ai/social_media_application.git backend
fi

if [ ! -d "frontend" ]; then
    echo "📥 Cloning frontend repository..."
    git clone https://github.com/Syllaby-ai/syllaby-react-frontend.git frontend
fi

echo "✅ Repositories cloned successfully!"
echo "🔧 All Dockerfiles are external - no files created inside repos"

# Make scripts executable
chmod +x scripts/*.sh

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    cp env.example .env
fi

# Create .env.testing if it doesn't exist
if [ ! -f ".env.testing" ]; then
    cp env.testing.example .env.testing
    echo "📝 Created .env.testing file"
fi

echo "✅ Setup complete!"
echo "🚀 Run './scripts/dev.sh' to start development environment"
echo "🐳 Run 'docker-compose up' for production build"
