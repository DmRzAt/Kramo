#!/bin/sh

set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$ROOT_DIR/docker/.env"

if [ ! -f "$ENV_FILE" ]; then
    ENV_FILE="$ROOT_DIR/docker/.env.example"
fi

set -a
# shellcheck disable=SC1090
. "$ENV_FILE"
set +a

COMPOSE_PROJECT_NAME="$(printf '%s' "$PROJECT_NAME" | tr '[:upper:]' '[:lower:]' | tr -cs 'a-z0-9_-' '-' | sed 's/^-//; s/-$//')"

compose() {
    docker compose \
        --env-file "$ENV_FILE" \
        -f "$ROOT_DIR/docker/docker-compose.yml" \
        -p "$COMPOSE_PROJECT_NAME" \
        "$@"
}

wp() {
    compose exec -T wpcli wp "$@"
}

if ! compose ps --status running wpcli | grep -q wpcli; then
    echo "The environment is not running. Run make up or make fresh first." >&2
    exit 1
fi

if ! wp plugin is-active woocommerce >/dev/null 2>&1; then
    echo "WooCommerce must be active before seeding demo content." >&2
    exit 1
fi

wp eval-file ../../../scripts/seed-demo.php
