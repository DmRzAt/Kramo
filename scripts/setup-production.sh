#!/usr/bin/env bash

set -Eeuo pipefail

WP_PATH="${WP_PATH:-/var/www/html}"

wp() {
	command wp --path="${WP_PATH}" "$@"
}

echo "Waiting for wp-config.php..."
attempt=0
until [ -f "${WP_PATH}/wp-config.php" ]; do
	attempt=$((attempt + 1))
	if [ "${attempt}" -ge 90 ]; then
		echo "The base image did not create wp-config.php in time." >&2
		exit 1
	fi
	sleep 2
done

# wp-cli refuses to bootstrap before WordPress is installed, so the readiness
# probe talks to MySQL directly through PHP.
db_reachable() {
	php -r '
		$host = getenv("WORDPRESS_DB_HOST");
		$port = 3306;
		if ( false !== strpos( $host, ":" ) ) {
			list( $host, $port ) = explode( ":", $host, 2 );
			$port = (int) $port;
		}
		$link = @mysqli_connect( $host, getenv("WORDPRESS_DB_USER"), getenv("WORDPRESS_DB_PASSWORD"), getenv("WORDPRESS_DB_NAME"), $port );
		exit( $link ? 0 : 1 );
	'
}

echo "Waiting for the database..."
attempt=0
until db_reachable >/dev/null 2>&1; do
	attempt=$((attempt + 1))
	if [ "${attempt}" -ge 90 ]; then
		echo "Database did not become reachable in time." >&2
		exit 1
	fi
	sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
	echo "Installing WordPress..."
	wp core install \
		--url="${WORDPRESS_SITE_URL}" \
		--title="${WORDPRESS_SITE_TITLE:-Kramo}" \
		--admin_user="${WORDPRESS_ADMIN_USER}" \
		--admin_password="${WORDPRESS_ADMIN_PASSWORD}" \
		--admin_email="${WORDPRESS_ADMIN_EMAIL}" \
		--locale=pl_PL \
		--skip-email
fi

wp option update siteurl "${WORDPRESS_SITE_URL}"
wp option update home "${WORDPRESS_SITE_URL}"
wp language core install pl_PL --activate || true
wp option update timezone_string Europe/Warsaw
wp rewrite structure '/%postname%/' --hard

wp theme activate generatepress
wp theme activate kramo-child

for plugin in woocommerce seo-by-rank-math updraftplus woocommerce-paypal-payments webp-uploads limit-login-attempts-reloaded; do
	wp plugin activate "${plugin}" || true
done

wp option update perflab_modern_image_format webp
wp option update perflab_generate_webp_and_jpeg 0

wp option update woocommerce_currency PLN
wp option update woocommerce_currency_pos right_space
wp option update woocommerce_price_thousand_sep ' '
wp option update woocommerce_price_decimal_sep ','
wp option update woocommerce_price_num_decimals 2
wp option update woocommerce_weight_unit kg
wp option update woocommerce_dimension_unit cm
wp option update woocommerce_default_country PL
wp option update woocommerce_onboarding_opt_in no
wp eval 'update_option("woocommerce_task_list_hidden","yes"); update_option("woocommerce_onboarding_profile", ["skipped" => true, "completed" => true]);'

for slug in hello-world sample-page; do
	post_ids="$(wp post list --name="${slug}" --post_type=post,page --post_status=any --format=ids)"
	if [ -n "${post_ids}" ]; then
		# shellcheck disable=SC2086
		wp post delete ${post_ids} --force
	fi
done

for plugin in akismet hello; do
	if wp plugin is-installed "${plugin}" >/dev/null 2>&1; then
		wp plugin delete "${plugin}"
	fi
done

echo "Creating Polish store pages..."
wp eval-file /scripts/provision-pages.php

echo "Configuring shipping, SEO and the demo payment method..."
wp eval-file /scripts/configure-production.php

echo "Seeding demo content..."
wp eval-file /scripts/seed-demo.php

wp rewrite flush --hard
wp eval 'if ( class_exists( "\\RankMath\\Sitemap\\Cache" ) ) { \RankMath\Sitemap\Cache::invalidate_storage(); }'

echo "Kramo demo is ready at ${WORDPRESS_SITE_URL}"
