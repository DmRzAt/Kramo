# Public demo deployment (Railway)

The demo runs the real store, not a static copy: catalog, filters, variants,
personalization, cart, shipping calculation and checkout all work. Railway
builds `Dockerfile.production`, attaches a managed MySQL service, mounts a
volume for uploads and issues TLS for a public domain.

## 1. Create the services

1. Push this repository to GitHub.
2. In Railway create a project and deploy the repository as a service named
   `wordpress`. `railway.json` already points at `Dockerfile.production`.
3. Add a managed **MySQL** database to the same project.
4. Attach a **Volume** to the WordPress service mounted at exactly
   `/var/www/html/wp-content/uploads`. Without it the demo images disappear on
   every redeploy while the database still references them.
5. Under **Settings → Networking** generate a public domain. Target port 80.

`docker-compose.yml` stays the local development environment; Railway does not
use it.

## 2. Variables

Add these to the WordPress service. The database values are Railway references,
so no credentials end up in Git:

```dotenv
PORT=80
WORDPRESS_DB_HOST=${{MySQL.MYSQLHOST}}:${{MySQL.MYSQLPORT}}
WORDPRESS_DB_NAME=${{MySQL.MYSQLDATABASE}}
WORDPRESS_DB_USER=${{MySQL.MYSQLUSER}}
WORDPRESS_DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
WORDPRESS_SITE_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
WORDPRESS_SITE_TITLE=Kramo
WORDPRESS_ADMIN_USER=kramo_admin
WORDPRESS_ADMIN_PASSWORD=replace_with_a_unique_password
WORDPRESS_ADMIN_EMAIL=replace_with_a_real_email
KRAMO_DEMO=1
KRAMO_PRESET=service
```

`KRAMO_DEMO=1` shows the demo ribbon, disables outgoing e-mail and marks the
checkout as a preview. `KRAMO_PRESET` switches the visual preset without a
rebuild: `craft`, `service` or `premium`.

Payments stay unconfigured on purpose: the demo enables cash on delivery so a
visitor can complete an order. To demonstrate PayPal sandbox instead, add the
same variables the local environment uses:

```dotenv
KRAMO_PAYMENT_MODE=sandbox
KRAMO_PAYPAL_SANDBOX_CLIENT_ID=...
KRAMO_PAYPAL_SANDBOX_CLIENT_SECRET=...
KRAMO_PAYPAL_SANDBOX_MERCHANT_ID=...
KRAMO_PAYPAL_SANDBOX_MERCHANT_EMAIL=...
```

## 3. What the first boot does

The entrypoint waits for the database, then provisions everything without any
click in the browser:

1. Installs WordPress in Polish and pins the site URL to the public domain.
2. Activates GeneratePress, `kramo-child` and the bundled plugins.
3. Applies Polish store settings: PLN, `1 234,56 zł`, kilograms, Europe/Warsaw.
4. Creates Sklep, Koszyk, Zamówienie, Moje konto, Regulamin and Polityka
   prywatności, and assigns them in WooCommerce.
5. Builds the Polska shipping zone, configures Rank Math and enables the demo
   payment method.
6. Seeds 12 variable products with weights, categories, reviews, a coupon, a
   product FAQ and the local-service landing page.

The scripts are idempotent, so a redeploy repairs configuration instead of
duplicating content.

## 4. Health check

Railway watches `/kramo-health`. The file appears only after provisioning
finishes, so a deployment that fails to install never receives traffic.

## 5. Resetting the demo

Anyone can order and register on a public demo, so reset it before showing the
store to a client:

```sh
railway run --service wordpress wp db reset --yes --path=/var/www/html
```

The next restart re-provisions everything from scratch.

## 6. Notes

- Outgoing e-mail is disabled while `KRAMO_DEMO=1`; nobody receives order
  notifications from the demo.
- `DISALLOW_FILE_EDIT` and `FORCE_SSL_ADMIN` are compiled into the image.
- The admin account is real. Use a unique password and treat the demo as
  publicly reachable, because it is.
