#!/usr/bin/env bash

set -Eeuo pipefail

health_file="/run/kramo-health"

if [[ -z "${WORDPRESS_SITE_URL:-}" && -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]]; then
	export WORDPRESS_SITE_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

required_variables=(
	WORDPRESS_DB_HOST
	WORDPRESS_DB_NAME
	WORDPRESS_DB_USER
	WORDPRESS_DB_PASSWORD
	WORDPRESS_SITE_URL
	WORDPRESS_ADMIN_USER
	WORDPRESS_ADMIN_PASSWORD
	WORDPRESS_ADMIN_EMAIL
)

for variable in "${required_variables[@]}"; do
	if [[ -z "${!variable:-}" ]]; then
		echo "Missing required environment variable: ${variable}" >&2
		exit 1
	fi
done

if [[ "${WORDPRESS_SITE_URL}" != https://* ]]; then
	echo "WORDPRESS_SITE_URL must use HTTPS in production." >&2
	exit 1
fi

export WORDPRESS_SITE_URL="${WORDPRESS_SITE_URL%/}"
export WORDPRESS_DEBUG=0
export HTTP_HOST="${WORDPRESS_SITE_URL#https://}"
export HTTP_HOST="${HTTP_HOST%%/*}"

# mod_php needs the prefork MPM, and the platform may enable another one at
# runtime, so the conflicting modules are removed right before startup.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null
apache2ctl -t

rm -f "${health_file}"
mkdir -p /var/www/html/wp-content/uploads
chown -R www-data:www-data /var/www/html/wp-content/uploads

docker-entrypoint.sh "$@" &
apache_pid=$!

stop_apache() {
	kill -TERM "${apache_pid}" 2>/dev/null || true
	wait "${apache_pid}" || true
}
trap stop_apache TERM INT

if ! runuser -u www-data -- /scripts/setup-production.sh; then
	stop_apache
	exit 1
fi

printf 'ok\n' > "${health_file}"
chown www-data:www-data "${health_file}"

wait "${apache_pid}"
