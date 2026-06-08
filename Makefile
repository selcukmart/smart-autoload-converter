# =============================================================================
# Smart Autoload Converter - Makefile
# PHP 8.5 + Symfony 7.4 LTS
# =============================================================================

COMPOSE = docker compose
APP     = $(COMPOSE) exec app
PHP     = $(APP) php

.PHONY: build up down restart test analyze convert init shell logs status clean help

# =============================================================================
# Core Commands
# =============================================================================

## Build and start all containers
build:
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(APP) composer install --prefer-dist --no-interaction
	@echo ""
	@echo "=== Smart Autoload Converter ==="
	@$(APP) php -v | head -1
	@$(APP) php bin/console --version 2>/dev/null || true
	@echo ""

## Start containers (without rebuild)
up:
	$(COMPOSE) up -d

## Stop all containers
down:
	$(COMPOSE) down

## Restart containers
restart: down up

# =============================================================================
# Development
# =============================================================================

## Run PHPUnit tests
test:
	$(PHP) vendor/bin/phpunit --no-coverage --colors=always

## Run tests with coverage
test-coverage:
	$(PHP) vendor/bin/phpunit --colors=always

## Run analysis on a legacy project (put it in workspace/input/)
analyze:
	$(PHP) bin/console smart:analyze -t /workspace/input

## Dry-run conversion (preview only)
dry-run:
	$(PHP) bin/console smart:convert -t /workspace/input -e /workspace/output --dry-run

## Run full conversion
convert:
	$(PHP) bin/console smart:convert -t /workspace/input -e /workspace/output

## Generate example config
init:
	$(PHP) bin/console smart:init -o /workspace/smart_autoload.yaml

## Open a shell inside the app container
shell:
	$(COMPOSE) exec app sh

# =============================================================================
# Composer
# =============================================================================

## Install composer dependencies
composer-install:
	$(APP) composer install --prefer-dist --no-interaction

## Update composer dependencies
composer-update:
	$(APP) composer update --prefer-dist --no-interaction

## Dump autoload
composer-dump:
	$(APP) composer dump-autoload --optimize

# =============================================================================
# Utilities
# =============================================================================

## Show container logs
logs:
	$(COMPOSE) logs -f app

## Show container status
status:
	$(COMPOSE) ps
	@echo ""
	@$(COMPOSE) exec app php -v | head -1 2>/dev/null || echo "Containers not running"

## Remove containers, volumes, and build cache
clean:
	$(COMPOSE) down -v --rmi local --remove-orphans
	@echo "Cleaned."

## Clear Symfony cache
cache-clear:
	$(PHP) bin/console cache:clear

# =============================================================================
# Help
# =============================================================================

## Show this help
help:
	@echo ""
	@echo "Smart Autoload Converter"
	@echo "========================"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@grep -E '^## ' Makefile | sed 's/## /  /' | paste - <(grep -E '^[a-zA-Z_-]+:' Makefile | sed 's/:.*//' | sed 's/^/make /') | awk -F'\t' '{printf "  %-24s %s\n", $$2, $$1}'
	@echo ""

.DEFAULT_GOAL := help
