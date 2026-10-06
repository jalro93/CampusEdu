#!/usr/bin/env bash
# Instala Laravel + Breeze (Blade) sobre esta carpeta conservando los ficheros ya creados. Ejecutar UNA vez.
set -e
composer create-project laravel/laravel .laravel-tmp --no-interaction
cp -Rn .laravel-tmp/. . && rm -rf .laravel-tmp
composer require laravel/breeze --dev --no-interaction
php artisan breeze:install blade --no-interaction
sed -i.bak 's/^APP_NAME=.*/APP_NAME=CampusEdu/' .env && rm -f .env.bak
touch database/database.sqlite
php artisan migrate --force
npm install && npm run build
echo "Listo: abre http://campusedu.test"
