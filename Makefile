# =============================================================================
# Smart Autoload Converter - Makefile
# PHP 8.5 + Symfony 7.4 LTS
# =============================================================================

COMPOSE = docker compose
APP     = $(COMPOSE) exec app
CLI     = $(APP) php bin/smart-autoload-converter

.PHONY: build up down restart test analyze dry-run convert init shell logs status clean help

# =============================================================================
# Core
# =============================================================================

## Build and start containers
build:
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(APP) composer install --prefer-dist --no-interaction
	@echo ""
	@echo "=== Smart Autoload Converter ==="
	@$(APP) sh -c "php -v | /usr/bin/head -1"
	@$(CLI) --version
	@echo ""

## Start containers
up:
	$(COMPOSE) up -d

## Stop containers
down:
	$(COMPOSE) down

## Restart containers
restart: down up

# =============================================================================
# Conversion (sample project pre-loaded at /workspace/input)
# =============================================================================

## Run PHPUnit tests
test:
	$(APP) php vendor/bin/phpunit --no-coverage --colors=always

## Analyze sample legacy project
analyze:
	$(CLI) analyze -t /workspace/input

## Dry-run conversion
dry-run:
	$(CLI) convert -t /workspace/input --export-path=/workspace/output --dry-run

## Full conversion
convert:
	$(CLI) convert -t /workspace/input --export-path=/workspace/output

## Generate example YAML config
init:
	$(CLI) init

## Enter container shell
shell:
	$(COMPOSE) exec app sh

# =============================================================================
# Utilities
# =============================================================================

## Show container logs
logs:
	$(COMPOSE) logs -f app

## Show status
status:
	$(COMPOSE) ps
	@$(APP) sh -c "php -v | /usr/bin/head -1" 2>/dev/null || echo "Not running"

## Composer install
composer-install:
	$(APP) composer install --prefer-dist --no-interaction

## Remove everything
clean:
	$(COMPOSE) down -v --rmi local --remove-orphans
	@echo "Cleaned."

## Show help
help:
	@echo "make build     Build + start + install"
	@echo "make test      Run PHPUnit (82 tests)"
	@echo "make analyze   Analyze sample project"
	@echo "make dry-run   Preview conversion"
	@echo "make convert   Full conversion"
	@echo "make init      Generate YAML config"
	@echo "make shell     Enter container"
	@echo "make down      Stop containers"
	@echo "make clean     Remove everything"

.DEFAULT_GOAL := help
