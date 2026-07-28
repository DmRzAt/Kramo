#!/bin/sh

set -eu

# On Windows Git Bash, MSYS rewrites leading-slash arguments into Windows paths,
# which corrupts values like '/%postname%/' and container paths like '/scripts'.
# Disabling that conversion is a no-op on Linux/macOS.
export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

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

# Path conversion is disabled above so that wp-cli arguments such as
# '/%postname%/' survive, which means Docker must be handed paths it can read
# on its own. On Windows that is the native form; elsewhere nothing changes.
to_host_path() {
    if command -v cygpath >/dev/null 2>&1; then
        cygpath -w "$1"
    else
        printf '%s' "$1"
    fi
}

ENV_FILE_ARG="$(to_host_path "$ENV_FILE")"
COMPOSE_FILE_ARG="$(to_host_path "$ROOT_DIR/docker/docker-compose.yml")"

compose() {
    docker compose \
        --env-file "$ENV_FILE_ARG" \
        -f "$COMPOSE_FILE_ARG" \
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

if compose exec -T wpcli test -f wp-content/themes/kramo-child/style.css; then
    wp theme activate kramo-child
else
    echo "Child theme files are not present yet; GeneratePress remains active."
fi

for plugin in woocommerce seo-by-rank-math updraftplus woocommerce-paypal-payments webp-uploads limit-login-attempts-reloaded; do
    wp plugin install "$plugin" --activate
done

# Modern Image Formats defaults to AVIF, which older Safari cannot read. WebP
# covers every browser in current use and still cuts image weight sharply.
wp option update perflab_modern_image_format webp
wp option update perflab_generate_webp_and_jpeg 0

P24_PLUGIN_URL="https://www.przelewy24.pl/do-pobrania/woocommerce-pobierz"
if wp plugin is-installed woo-przelewy24 >/dev/null 2>&1; then
    wp plugin activate woo-przelewy24
else
    wp plugin install "$P24_PLUGIN_URL" --activate
fi

# Official shipping plugins. Best-effort: they need ShipX / Orlen credentials to
# do anything live, so a missing package must not abort the whole bootstrap.
for shipping_plugin in inpost-for-woocommerce orlen-paczka; do
    if wp plugin is-installed "$shipping_plugin" >/dev/null 2>&1; then
        wp plugin activate "$shipping_plugin" || true
    elif ! wp plugin install "$shipping_plugin" --activate; then
        echo "Note: could not install '$shipping_plugin' from wp.org; add it manually with the client's credentials (see docs/dostawa.md)." >&2
    fi
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
ensure_page 'Dostawa i płatności' 'dostawa-i-platnosci' '<!-- wp:paragraph --><p>Informacje o formach dostawy i płatności uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->'
ensure_page 'Zwroty i reklamacje' 'zwroty-i-reklamacje' '<!-- wp:paragraph --><p>Zasady zwrotów i reklamacji uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->'
ensure_page 'Polityka cookies' 'polityka-cookies' '<!-- wp:paragraph --><p>Informacje o plikach cookies uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->'

wp option update woocommerce_shop_page_id "$shop_id"
wp option update woocommerce_cart_page_id "$cart_id"
wp option update woocommerce_checkout_page_id "$checkout_id"
wp option update woocommerce_myaccount_page_id "$account_id"
wp option update woocommerce_terms_page_id "$terms_id"
wp option update wp_page_for_privacy_policy "$privacy_id"

# WooCommerce hides account creation by default, which leaves the account page
# with a login form customers can never sign up for.
wp option update woocommerce_enable_myaccount_registration yes
wp option update woocommerce_enable_signup_and_login_from_checkout yes
wp option update woocommerce_enable_checkout_login_reminder yes
wp option update woocommerce_enable_guest_checkout yes

wp rewrite flush --hard

echo "Configuring the Polish shipping zone..."
wp eval '
$zone_name = "Polska";
$zone_id = 0;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
    if ( $z["zone_name"] === $zone_name ) { $zone_id = (int) $z["zone_id"]; break; }
}
$zone = $zone_id ? new WC_Shipping_Zone( $zone_id ) : new WC_Shipping_Zone();
if ( ! $zone_id ) {
    $zone->set_zone_name( $zone_name );
    $zone->set_zone_order( 1 );
}
$has_pl = false;
foreach ( $zone->get_zone_locations() as $loc ) {
    if ( "country" === $loc->type && "PL" === $loc->code ) { $has_pl = true; }
}
if ( ! $has_pl ) { $zone->add_location( "PL", "country" ); }
$zone->save();

$existing = array();
foreach ( $zone->get_shipping_methods() as $m ) { $existing[] = $m->get_title(); }
$want = array(
    array( "flat_rate", "Paczkomat InPost" ),
    array( "flat_rate", "Kurier InPost" ),
    array( "flat_rate", "Orlen Paczka" ),
    array( "free_shipping", "Darmowa dostawa" ),
);
foreach ( $want as $method ) {
    list( $type, $title ) = $method;
    if ( in_array( $title, $existing, true ) ) { continue; }
    $instance_id = $zone->add_shipping_method( $type );
    $key = "woocommerce_" . $type . "_" . $instance_id . "_settings";
    $settings = get_option( $key, array() );
    if ( ! is_array( $settings ) ) { $settings = array(); }
    $settings["title"] = $title;
    if ( "flat_rate" === $type ) { $settings["cost"] = "12"; $settings["tax_status"] = "none"; }
    if ( "free_shipping" === $type ) { $settings["requires"] = "min_amount"; $settings["min_amount"] = "200"; }
    update_option( $key, $settings );
}
echo "Shipping zone ready.\n";
'

echo "Hardening WordPress..."
wp config set DISALLOW_FILE_EDIT true --raw --type=constant
wp config set WP_AUTO_UPDATE_CORE minor --type=constant

# Files weekly, database daily. Remote storage stays unset on purpose: backups
# belong on the account of the shop owner, not on the server being backed up.
echo "Scheduling UpdraftPlus backups (database daily, files weekly)..."
wp option update updraft_interval weekly
wp option update updraft_interval_database daily
wp option update updraft_retain 14
wp option update updraft_retain_db 30

echo "Configuring Rank Math (no wizard)..."
wp eval '
update_option( "rank_math_wizard_completed", true );
update_option( "rank_math_registration_skip", true );

$general = get_option( "rank-math-options-general", array() );
if ( ! is_array( $general ) ) { $general = array(); }
$general["breadcrumbs"] = "on";
update_option( "rank-math-options-general", $general );

$sitemap = get_option( "rank-math-options-sitemap", array() );
if ( ! is_array( $sitemap ) ) { $sitemap = array(); }
$sitemap["authors_sitemap"]        = "off";
$sitemap["tax_post_tag_sitemap"]   = "off";
$sitemap["tax_product_tag_sitemap"] = "off";
update_option( "rank-math-options-sitemap", $sitemap );

$titles = get_option( "rank-math-options-titles", array() );
if ( ! is_array( $titles ) ) { $titles = array(); }
$titles["disable_author_archives"] = "on";
$titles["knowledgegraph_type"]     = "company";
if ( empty( $titles["knowledgegraph_name"] ) ) {
    $titles["knowledgegraph_name"] = get_bloginfo( "name" );
}
update_option( "rank-math-options-titles", $titles );

$modules = (array) get_option( "rank_math_modules", array() );
if ( ! in_array( "sitemap", $modules, true ) ) { $modules[] = "sitemap"; }
update_option( "rank_math_modules", $modules );

// Register the Rank Math sitemap rewrite in this process, then flush so
// /sitemap_index.xml resolves without visiting wp-admin.
if ( class_exists( "\\RankMath\\Sitemap\\Router" ) ) {
    new \RankMath\Sitemap\Router();
    do_action( "init" );
}
flush_rewrite_rules( true );
echo "Rank Math configured.\n";
'

echo
echo "WordPress is ready: http://localhost:${WP_PORT}"
echo "phpMyAdmin is ready: http://localhost:${PMA_PORT}"
echo "Admin user: ${WP_ADMIN_USER}"
