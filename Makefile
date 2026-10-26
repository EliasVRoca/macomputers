.PHONY: dev up down restart logs sh bash migrate seed fresh test build db-create \
	db-test-create test-funcional test-integracion test-validacion test-seguridad test-nuevas

## levanta el contenedor y arranca Vite con HMR
dev:
	docker compose up -d --build
	pnpm install
	pnpm dev

## levanta/actualiza el contenedor en segundo plano
up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart app

logs:
	docker compose logs -f app

sh:
	docker compose exec app sh

bash:
	docker compose exec app bash

migrate:
	docker compose exec -T -u www-data app php artisan migrate

seed:
	docker compose exec -T -u www-data app php artisan db:seed

fresh:
	docker compose exec -T -u www-data app php artisan migrate:fresh --seed

test:
	docker compose exec -T -u www-data app php artisan test

build:
	docker compose build --no-cache

## crea la base de datos en el MariaDB compartido (idempotente)
db-create:
	docker exec mariadb mysql -uroot -e "CREATE DATABASE IF NOT EXISTS macomputers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

## base de datos exclusiva para las pruebas de integración
db-test-create:
	docker exec mariadb mysql -uroot -e "CREATE DATABASE IF NOT EXISTS macomputers_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

test-funcional:
	docker compose exec -T -u www-data app php artisan test --testsuite=Funcional

test-integracion:
	docker compose exec -T -u www-data app php artisan test --testsuite=Integracion

test-validacion:
	docker compose exec -T -u www-data app php artisan test --testsuite=Validacion

test-seguridad:
	docker compose exec -T -u www-data app php artisan test --testsuite=Seguridad

## las cuatro suites nuevas (sin las pruebas históricas)
test-nuevas:
	docker compose exec -T -u www-data app php artisan test tests/Funcional tests/Integracion tests/Validacion tests/Seguridad

## integración contra el motor real; requiere `make db-test-create`
test-mariadb:
	docker compose exec -T -u www-data app env DB_CONNECTION=mysql DB_DATABASE=macomputers_test php artisan test --configuration=phpunit.mariadb.xml --testsuite=Integracion
