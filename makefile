cli:
	docker compose exec php-cli bash
fpm:
	docker compose exec php-fpm bash
npm-install:
	docker compose exec node npm install
npm-build:
	docker compose exec node npm run build
npm-dev:
	docker compose exec node npm run dev
up:
	docker compose up -d
down:
	docker compose down
code-style:
	docker compose exec php-cli vendor/bin/pint --preset laravel
