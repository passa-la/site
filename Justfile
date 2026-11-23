set dotenv-load

up:
    docker compose up -d --build

down:
    docker compose down

shell:
    docker compose exec -u www-data app sh

artisan *args:
    docker compose exec -u www-data app php artisan {{args}}

composer *args:
    docker compose exec -u www-data app composer {{args}}

install:
    docker compose exec -u www-data app composer install
    npm install

build:
    npm run build

migrate:
    docker compose exec -u www-data app php artisan migrate --force

sync:
    docker compose exec -u www-data app php artisan gtfs:sync

test:
    docker compose exec -u www-data app php artisan test

lint:
    docker compose exec -u www-data app vendor/bin/pint --test
    docker compose exec -u www-data app vendor/bin/phpstan analyse --memory-limit=1G

fix:
    docker compose exec -u www-data app vendor/bin/pint

logs:
    docker compose logs -f app scheduler
