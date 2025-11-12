# Makefile for NoorPanel Project (Dockerized Laravel with Supabase)

# Container names
APP_CONTAINER = app
DB_CONTAINER = noorpanel_postgres
REDIS_CONTAINER = noorpanel_redis

# ========================
# Laravel Shortcuts
# ========================

# Run artisan commands
artisan:
	docker-compose exec $(APP_CONTAINER) php artisan $(cmd)

# Run composer commands
composer:
	docker-compose exec $(APP_CONTAINER) composer $(cmd)

# Open bash inside the app container
bash:
	docker-compose exec $(APP_CONTAINER) bash

# Clear caches & optimize Laravel
optimize:
	docker-compose exec $(APP_CONTAINER) php artisan optimize:clear && \
	docker-compose exec $(APP_CONTAINER) php artisan config:cache && \
	docker-compose exec $(APP_CONTAINER) php artisan route:cache && \
	docker-compose exec $(APP_CONTAINER) php artisan view:cache

# Run migrations
migrate:
	docker-compose exec $(APP_CONTAINER) php artisan migrate

# Run database seeders
seed:
	docker-compose exec $(APP_CONTAINER) php artisan db:seed

# Run queue worker
queue:
	docker-compose exec $(APP_CONTAINER) php artisan queue:work

# Generate new APP_KEY
keygen:
	docker-compose exec $(APP_CONTAINER) php artisan key:generate

# Refresh database (migrate fresh, seed)
refresh:
	docker-compose exec $(APP_CONTAINER) php artisan migrate:fresh --seed

# Install Laravel Breeze for authentication
breeze:
	docker-compose exec $(APP_CONTAINER) composer require laravel/breeze --dev && \
	docker-compose exec $(APP_CONTAINER) php artisan breeze:install

# ========================
# Docker Management
# ========================

# Restart all containers
restart:
	docker-compose down && docker-compose up -d

# Build and start fresh
build:
	docker-compose up -d --build

# Stop containers
stop:
	docker-compose down

# View logs
logs:
	docker-compose logs -f $(APP_CONTAINER)

# ========================
# Development
# ========================

# Install fresh dependencies
install:
	docker-compose exec $(APP_CONTAINER) composer install

# Update dependencies
update:
	docker-compose exec $(APP_CONTAINER) composer update

# Run tests
test:
	docker-compose exec $(APP_CONTAINER) php artisan test

# Run npm commands
npm:
	docker-compose exec $(APP_CONTAINER) npm $(cmd)

# Run npm dev
dev:
	docker-compose exec $(APP_CONTAINER) npm run dev

# Run npm build
build-assets:
	docker-compose exec $(APP_CONTAINER) npm run build

# ========================
# Database Management
# ========================

# Test database connection
db-test:
	docker-compose exec $(APP_CONTAINER) php artisan db:test-connection

# Connect to local PostgreSQL
db-connect:
	docker-compose exec $(DB_CONTAINER) psql -U postgres -d noorpanel

# Open pgAdmin
pgadmin:
	@echo "Opening pgAdmin at http://localhost:8080"
	@if which xdg-open > /dev/null; then xdg-open http://localhost:8080; \
	elif which open > /dev/null; then open http://localhost:8080; \
	else echo "Please open http://localhost:8080 manually"; fi

# Reset local database
db-reset:
	docker-compose exec $(DB_CONTAINER) dropdb -U postgres noorpanel || true
	docker-compose exec $(DB_CONTAINER) createdb -U postgres noorpanel
	docker-compose exec $(APP_CONTAINER) php artisan migrate:fresh --seed

# Switch to Supabase database
use-supabase:
	@echo "Switching to Supabase database..."
	@sed -i 's/DB_HOST=.*/DB_HOST=vsucltqmzenytmkgmbhv.supabase.co/' .env
	@sed -i 's/DB_DATABASE=.*/DB_DATABASE=postgres/' .env
	@sed -i 's/DB_USERNAME=.*/DB_USERNAME=postgres/' .env
	@sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=##kilban13#/' .env
	@echo "Updated to use Supabase"

# Switch to local database
use-local:
	@echo "Switching to local database..."
	@sed -i 's/DB_HOST=.*/DB_HOST=postgres/' .env
	@sed -i 's/DB_DATABASE=.*/DB_DATABASE=noorpanel/' .env
	@sed -i 's/DB_USERNAME=.*/DB_USERNAME=postgres/' .env
	@sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=password/' .env
	@echo "Updated to use local database"

# Wait for database to be ready
wait-for-db:
	@echo "Waiting for database to be ready..."
	@docker-compose exec $(APP_CONTAINER) bash -c 'until php artisan tinker --execute="try { DB::connection()->getPdo(); echo \"Database ready\"; exit(0); } catch (Exception \$e) { echo \"Waiting...\"; exit(1); }" 2>/dev/null; do sleep 2; done'

# ========================
# Start Project (All-in-One)
# ========================

start:
	@echo "=== Building and starting containers ==="
	docker-compose up -d --build
	@echo "=== Waiting for database to be ready ==="
	@$(MAKE) wait-for-db
	@echo "=== Installing composer dependencies if missing ==="
	@if [ ! -d ./vendor ]; then \
		docker-compose exec $(APP_CONTAINER) composer install; \
	fi
	@echo "=== Generating APP_KEY if missing ==="
	@docker-compose exec $(APP_CONTAINER) php artisan key:generate
	@echo "=== Running migrations ==="
	@docker-compose exec $(APP_CONTAINER) php artisan migrate
	@echo "=== Serving Laravel app ==="
	@echo "NoorPanel is running at: http://localhost:8000"
	@echo "PostgreSQL is available at: localhost:5432"
	@echo "pgAdmin is available at: http://localhost:8080"
	@echo "Redis is available at: localhost:6379"

# ========================
# Security
# ========================

# Generate application key
key:
	docker-compose exec $(APP_CONTAINER) php artisan key:generate

# Cache configuration
cache:
	docker-compose exec $(APP_CONTAINER) php artisan config:cache

# Create migration
migration:
	docker-compose exec $(APP_CONTAINER) php artisan make:migration $(name)

# Create model with migration
model:
	docker-compose exec $(APP_CONTAINER) php artisan make:model $(name) -m

# Show database connection status
db-status:
	docker-compose exec $(APP_CONTAINER) php artisan db:show

.PHONY: artisan composer bash optimize migrate seed queue keygen refresh breeze restart build stop logs install update test npm dev build-assets start db-status migration model key cache db-test db-connect pgadmin db-reset use-supabase use-local wait-for-db
