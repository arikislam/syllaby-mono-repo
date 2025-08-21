# Syllaby Monorepo

Single Docker container setup for simultaneous frontend and backend development.

## Quick Start

```bash
# 1. Setup (first time only)
./scripts/setup.sh

# 2. Start development environment
./scripts/dev.sh
```

## Architecture

```
syllaby-monorepo/
├── frontend/          # React frontend (syllaby-react-frontend)
├── backend/           # Laravel backend (social_media_application)
├── dockerfiles/       # External Docker configurations
├── mysql-init/        # MySQL database initialization scripts
├── docker-compose.yml # Production configuration
├── docker-compose.dev.yml # Development configuration
└── scripts/           # Automation scripts
```

## Services

- **Frontend**: http://localhost:3330
- **Backend (Laravel)**: http://localhost:8880
- **MySQL**: localhost:3906 (3 databases: syllaby, syllaby_testing, syllaby_pulse)
- **Redis**: localhost:6930
- **MailHog**: http://localhost:8062
- **Xdebug**: port 9230

## Development Features

- ✅ Hot reload for both frontend and backend
- ✅ Laravel with PHP 8.2, MySQL (3 databases), Redis, MailHog
- ✅ Shared Docker network (seamless API calls)
- ✅ Volume mounts (no rebuild needed for code changes)
- ✅ Xdebug support for Laravel debugging
- ✅ Environment variable management
- ✅ Zero architecture changes to original repos

## Commands

```bash
# Development mode (hot reload)
./scripts/dev.sh
# OR
docker-compose -f docker-compose.dev.yml up

# Run Laravel tests
./scripts/test.sh

# Production mode
docker-compose up

# Rebuild containers
docker-compose up --build

# Stop all services
docker-compose down

# View logs
docker-compose logs -f frontend
docker-compose logs -f backend-dev
```

## Environment Variables

Copy environment files and configure:

```bash
# Copy environment files
cp env.example .env
cp env.testing.example .env.testing
```

**Main Environment (.env):**
```env
# Backend (Laravel)
APP_ENV=local
DB_DATABASE=syllaby
DB_HOST=localhost
DB_PORT=3906

# Frontend  
REACT_APP_API_URL=http://localhost:8880
```

**Testing Environment (.env.testing):**
```env
APP_ENV=testing
DB_DATABASE=syllaby_testing
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

## Network Configuration

Both services run on `syllaby-network` bridge:
- Frontend → Backend: `http://backend:8000` (internal)
- External → Frontend: `http://localhost:3330`
- External → Backend: `http://localhost:8880`

## Troubleshooting

**Port conflicts:**
```bash
docker-compose down
sudo lsof -i :3330 -i :8880 -i :3906 -i :6930 -i :8062 -i :1062
```

**Clear containers:**
```bash
docker-compose down -v
docker system prune -f
```

**Update repos:**
```bash
cd frontend && git pull
cd backend && git pull
docker-compose up --build
```
