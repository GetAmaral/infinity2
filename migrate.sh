#!/bin/bash

# NAI Database Migration Script
# Runs migration commands inside Docker container

docker compose exec php symfony console make:migration

docker compose exec php symfony console doctrine:migrations:migrate