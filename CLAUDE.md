# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Syllaby is a monorepo for a video generation and social media content management platform. The repository contains a Laravel backend API that handles video generation, scheduling, publishing, and user management. The project is designed to work with a React frontend (which should be cloned separately when running the development environment).

## Architecture

### Backend (Laravel 11)
- **API Structure**: RESTful API under `/api/v1/` with modular controllers organized by feature
- **Key Modules**:
  - Videos: Faceless video generation, footage management, rendering
  - Schedulers: Content scheduling with recurrence rules
  - Publications: Multi-platform social media publishing (YouTube, TikTok, Instagram, etc.)
  - Subscriptions: Stripe and Google Play billing integration
  - Characters: AI character generation and training
  - RealClones: Avatar and voice cloning features
  - Credits: Usage-based credit system

### Database
- MySQL with 3 databases: `syllaby`, `syllaby_testing`, `syllaby_pulse`
- Migrations in `backend/database/migrations/`
- Factories for testing in `backend/database/factories/`

### Services
- Redis for caching and queues
- MailHog for email testing in development
- Laravel Horizon for queue management
- External integrations: OpenAI, Stripe, Google Play, Social Media APIs

## Development Commands

### Starting Development Environment
```bash
# First time setup (clones frontend if needed)
./scripts/setup.sh

# Start all services
./scripts/dev.sh

# Or manually with Docker
docker-compose -f docker-compose.dev.yml up
```

### Laravel Commands
```bash
# Run inside backend container
docker-compose -f docker-compose.dev.yml exec backend-dev bash

# Database migrations
php artisan migrate
php artisan migrate:fresh --seed

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Queue workers
php artisan horizon
php artisan queue:work

# Generate API documentation
php artisan scribe:generate
```

### Testing
```bash
# Run all tests
./scripts/test.sh

# Or manually
docker-compose -f docker-compose.dev.yml exec backend-dev php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/Videos/FacelessControllerTest.php
```

### Code Quality
```bash
# Laravel Pint for code formatting
docker-compose -f docker-compose.dev.yml exec backend-dev ./vendor/bin/pint

# With specific path
./vendor/bin/pint app/Http/Controllers
```

## API Endpoints

The main API routes are defined in:
- `backend/routes/api-v1.php` - Main API v1 routes
- `backend/routes/webhooks.php` - Webhook endpoints
- `backend/routes/external.php` - External service endpoints

Key endpoint patterns:
- Authentication: `/api/v1/authentication/*`
- Videos: `/api/v1/videos/*`
- Schedulers: `/api/v1/schedulers/*`
- Publications: `/api/v1/publications/*`
- Subscriptions: `/api/v1/subscriptions/*`

## Environment Configuration

Key environment variables to configure:
- Database: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Redis: `REDIS_HOST`, `REDIS_PORT`
- API Keys: `AI_API_KEY`, `STRIPE_KEY`, `STRIPE_SECRET`
- AWS: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`
- Social Media: Various OAuth credentials for platforms

## Queue Jobs

The application uses Laravel Horizon for queue management. Key job classes are in:
- `app/Syllaby/Videos/Jobs/` - Video processing jobs
- `app/Syllaby/Publisher/Jobs/` - Social media publishing jobs
- `app/Syllaby/Characters/Jobs/` - Character generation jobs

## Testing Approach

Tests are organized by feature in `backend/tests/Feature/` and use:
- PHPUnit as the test framework
- Laravel's built-in testing helpers
- Database transactions for isolation
- Factories for test data generation

## Key Development Patterns

1. **Request Validation**: Form requests in `app/Http/Requests/`
2. **Resource Transformation**: API resources in `app/Http/Resources/`
3. **Service Layer**: Business logic in `app/Syllaby/*/Services/`
4. **Repository Pattern**: Data access in model classes
5. **Event-Driven**: Events and listeners for decoupled operations

## Debugging

- Xdebug is configured on port 9230
- Laravel Telescope can be enabled for request monitoring
- Horizon dashboard available for queue monitoring
- Clockwork for performance profiling

## Common Tasks

### Adding a New Feature
1. Create migration if database changes needed
2. Create model in appropriate Syllaby namespace
3. Create controller in `app/Http/Controllers/Api/v1/`
4. Add routes to `routes/api-v1.php`
5. Create form request for validation
6. Create resource for API response
7. Write feature tests

### Modifying Video Processing
- Video types: Faceless, Footage, RealClone
- Processing flows defined in respective service classes
- Webhook handlers in `app/Http/Controllers/Webhooks/`

### Working with Schedulers
- Uses RRule for recurrence patterns
- Occurrences generated from scheduler rules
- Background jobs process scheduled content

## Service URLs

- Frontend: http://localhost:3330
- Backend API: http://localhost:8880
- MySQL: localhost:3906
- Redis: localhost:6930
- MailHog: http://localhost:8062
- Xdebug: port 9230