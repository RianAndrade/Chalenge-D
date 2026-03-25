up:
	docker compose up -d

down:
	docker compose down

test:
	docker compose -f docker-compose.test.yml up -d --wait
	docker compose -f docker-compose.test.yml exec php-test php bin/phpunit --testdox; \
	docker compose -f docker-compose.test.yml down --volumes
