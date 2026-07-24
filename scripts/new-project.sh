#!/bin/sh

set -eu

if [ "$#" -ne 1 ] || [ -z "$1" ]; then
    echo "Usage: $0 <project-name>" >&2
    exit 1
fi

PROJECT_TITLE="$1"
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

COMPOSE_PROJECT_NAME="$(printf '%s' "$PROJECT_TITLE" | tr '[:upper:]' '[:lower:]' | tr -cs 'a-z0-9_-' '-' | sed 's/^-//; s/-$//')"
if [ -z "$COMPOSE_PROJECT_NAME" ]; then
    COMPOSE_PROJECT_NAME="$PROJECT_NAME"
fi

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

ensure_page() {
    page_title="$1"
    page_slug="$2"
    page_content="$3"
    page_id="$(wp post list --post_type=page --name="$page_slug" --post_status=any --format=ids | awk '{print $1}')"

    if [ -z "$page_id" ]; then
        page_id="$(wp post create \
            --post_type=page \
            --post_status=publish \
            --post_title="$page_title" \
            --post_name="$page_slug" \
            --post_content="$page_content" \
            --porcelain)"
    fi

    printf '%s' "$page_id"
}

echo "Starting Docker services for $PROJECT_TITLE..."
compose up -d db wordpress phpmyadmin wpcli

echo "Waiting for MariaDB readiness..."
attempt=0
until compose exec -T db mariadb-admin ping \
    -h 127.0.0.1 \
    -u "$DB_USER" \
    "-p$DB_PASSWORD" \
    --silent >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "MariaDB did not become ready in time." >&2
        compose logs db
        exit 1
    fi
    sleep 2
done

echo "Waiting for WordPress files..."
attempt=0
until wp core version >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "WordPress files did not become ready in time." >&2
        compose logs wordpress wpcli
        exit 1
    fi
    sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
    echo "Installing WordPress..."
    wp core install \
        --url="http://localhost:${WP_PORT}" \
        --title="$PROJECT_TITLE" \
        --admin_user="$WP_ADMIN_USER" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --locale=pl_PL \
        --skip-email
fi

wp language core install pl_PL --activate
wp option update timezone_string Europe/Warsaw
wp rewrite structure '/%postname%/' --hard

echo "Installing the approved theme and plugins..."
wp theme install generatepress --activate

if compose exec -T wpcli test -f wp-content/themes/woostarter-child/style.css; then
    wp theme activate woostarter-child
else
    echo "Child theme files are not present yet; GeneratePress remains active."
fi

for plugin in woocommerce seo-by-rank-math updraftplus; do
    wp plugin install "$plugin" --activate
done

wp option update woocommerce_currency PLN
wp option update woocommerce_currency_pos right_space
wp option update woocommerce_price_thousand_sep ' '
wp option update woocommerce_price_decimal_sep ','
wp option update woocommerce_price_num_decimals 2
wp option update woocommerce_weight_unit kg
wp option update woocommerce_dimension_unit cm
wp option update woocommerce_default_country PL
wp option update woocommerce_onboarding_opt_in no
wp eval '
update_option("woocommerce_task_list_hidden", "yes");
update_option("woocommerce_onboarding_profile", [
    "skipped" => true,
    "completed" => true,
]);
'

echo "Removing default WordPress content..."
for slug in hello-world sample-page; do
    post_ids="$(wp post list --name="$slug" --post_type=post,page --post_status=any --format=ids)"
    if [ -n "$post_ids" ]; then
        # shellcheck disable=SC2086
        wp post delete $post_ids --force
    fi
done

for plugin in akismet hello; do
    if wp plugin is-installed "$plugin" >/dev/null 2>&1; then
        wp plugin delete "$plugin"
    fi
done

echo "Creating required Polish pages..."
shop_id="$(ensure_page 'Sklep' 'sklep' '')"
cart_id="$(ensure_page 'Koszyk' 'koszyk' '[woocommerce_cart]')"
checkout_id="$(ensure_page 'Zamówienie' 'zamowienie' '[woocommerce_checkout]')"
account_id="$(ensure_page 'Moje konto' 'moje-konto' '[woocommerce_my_account]')"
terms_id="$(ensure_page 'Regulamin' 'regulamin' '')"
privacy_id="$(ensure_page 'Polityka prywatności' 'polityka-prywatnosci' '')"

wp option update woocommerce_shop_page_id "$shop_id"
wp option update woocommerce_cart_page_id "$cart_id"
wp option update woocommerce_checkout_page_id "$checkout_id"
wp option update woocommerce_myaccount_page_id "$account_id"
wp option update woocommerce_terms_page_id "$terms_id"
wp option update wp_page_for_privacy_policy "$privacy_id"
wp rewrite flush --hard

echo
echo "WordPress is ready: http://localhost:${WP_PORT}"
echo "phpMyAdmin is ready: http://localhost:${PMA_PORT}"
echo "Admin user: ${WP_ADMIN_USER}"
