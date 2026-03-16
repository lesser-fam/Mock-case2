bootstrap:
	docker-compose up -d --build
	docker-compose exec php composer install
	@make env
	@make init

env:
	cp --update=none src/.env.example src/.env
	sed -i 's/DB_HOST=127.0.0.1/DB_HOST=mysql/' src/.env
	sed -i 's/DB_DATABASE=.*/DB_DATABASE=laravel_db/' src/.env
	sed -i 's/DB_USERNAME=.*/DB_USERNAME=laravel_user/' src/.env
	sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=laravel_pass/' src/.env

wait-db:
	until docker-compose exec php php -r 'try { new PDO("mysql:host=mysql;port=3306;dbname=laravel_db", "laravel_user", "laravel_pass"); echo "DB ready\n"; } catch (Throwable $$e) { fwrite(STDERR, $$e->getMessage().PHP_EOL); exit(1); }'; do \
		echo "Waiting for MySQL..."; \
		sleep 2; \
	done

init:
	@make wait-db
	docker-compose exec php php artisan key:generate
	docker-compose exec php php artisan storage:link || true
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
	docker-compose exec php php artisan optimize:clear
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:cache

stop:
	docker-compose stop