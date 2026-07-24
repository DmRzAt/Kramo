SHELL := /bin/sh

ENV_FILE := $(if $(wildcard docker/.env),docker/.env,docker/.env.example)
include $(ENV_FILE)

COMPOSE := docker compose --env-file $(ENV_FILE) -f docker/docker-compose.yml -p $(PROJECT_NAME)

.PHONY: up down fresh demo wp logs

up:
	$(COMPOSE) up -d db wordpress phpmyadmin wpcli

down:
	$(COMPOSE) down

fresh:
	$(COMPOSE) down --volumes --remove-orphans
	./scripts/new-project.sh "$(PROJECT_NAME)"

demo:
	./scripts/seed-demo.sh

wp:
	$(COMPOSE) exec -T wpcli wp $(CMD)

logs:
	$(COMPOSE) logs -f --tail=100
