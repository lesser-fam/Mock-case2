bootstrap:
	docker-compose up -d --build
	docker-compose exec php composer install
	@make env
	@make init

env:
	cp -n src/.env.example src/.env
	sed -i 's/DB_HOST=127.0.0.1/DB_HOST=mysql/' src/.env
	sed -i 's/DB_DATABASE=.*/DB_DATABASE=laravel_db/' src/.env
	sed -i 's/DB_USERNAME=.*/DB_USERNAME=laravel_user/' src/.env
	sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=laravel_pass/' src/.env

init:
	docker-compose exec php php artisan key:generate
	docker-compose exec php php artisan storage:link
	@make fresh

fresh:
	docker-compose exec php php artisan migrate:fresh --seed

up:
	docker-compose up -d

down:
	docker-compose down --remove-orphans

restart:
	@make down
	@make up

cache:
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:cache

stop:
	docker-compose stop