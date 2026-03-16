#!/bin/sh
set -e

echo "Building and starting PHP container..."
docker compose up -d

echo "Installing Composer dependencies..."
docker compose exec php composer install

echo "Done! Project is ready."
