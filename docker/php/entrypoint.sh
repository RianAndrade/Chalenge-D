#!/bin/bash
set -e

composer install --no-interaction --optimize-autoloader

bin/console doctrine:migrations:migrate --no-interaction

exec php-fpm
