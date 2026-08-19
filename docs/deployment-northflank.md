# Public demo deployment (Northflank free sandbox)

Alternative to `deployment.md` when the Railway trial credit is gone. The image
is host-agnostic: the only Railway-specific line in
`deployment/production-entrypoint.sh` is a fallback that derives
`WORDPRESS_SITE_URL` from `RAILWAY_PUBLIC_DOMAIN`. Setting `WORDPRESS_SITE_URL`
explicitly makes that fallback irrelevant, so no code change is needed.

The free Developer Sandbox allows 2 services, 1 addon and 2 jobs, and does not
sleep on idle. A payment method is required for identity verification.
Northflank does not publish the exact vCPU/RAM allocated to free services, so
treat the first provisioning run as the capacity test: it installs WordPress,
activates the plugins and seeds 12 variable products in one pass.

## 1. Create the MySQL addon first

1. Create a project in the `europe-west` region.
2. Add a **MySQL** addon. Wait for it to become running.
3. Open its connection details and copy `host`, `port`, `database`, `username`
   and `password`. Paste literal values into the service later — aliasing the
   addon secrets cannot produce the `host:port` string WordPress expects in a
   single variable.

## 2. Create the service

1. New **Combined service** from the GitHub repository, branch `main`.
2. Build type **Dockerfile**, path `/Dockerfile.production`.
3. Networking: expose port **80**, protocol HTTP, public.
4. Health checks: see the section below. Add them after the first successful
   deploy, never before it.
5. Optional: attach a volume at `/var/www/html/wp-content/uploads`. Without it
   the seeded images are regenerated on each redeploy by the idempotent seed
   script, which costs build time but does not break the demo.

## 3. Variables

Runtime variables on the service, values pasted from the addon:

```dotenv
PORT=80
WORDPRESS_DB_HOST=<addon-host>:<addon-port>
WORDPRESS_DB_NAME=<addon-database>
WORDPRESS_DB_USER=<addon-username>
WORDPRESS_DB_PASSWORD=<addon-password>
WORDPRESS_SITE_URL=https://<service>--<project>--<team>.code.run
WORDPRESS_SITE_TITLE=Kramo
WORDPRESS_ADMIN_USER=kramo_admin
WORDPRESS_ADMIN_PASSWORD=replace_with_a_unique_password
WORDPRESS_ADMIN_EMAIL=replace_with_a_real_email
KRAMO_DEMO=1
KRAMO_PRESET=service
```

`WORDPRESS_SITE_URL` must be HTTPS and must match the public domain exactly.
The entrypoint refuses to boot otherwise, and a mismatch pins WordPress to the
wrong host, which produces redirect loops on `/wp-admin`.

Take the domain from the service networking tab before the first deploy. If it
is added afterwards, update the variable and redeploy: the site URL is written
into the database on first install.

## 4. Health checks

`/kramo-health` is a static file served through an Apache alias
(`deployment/kramo-health.conf`) that the entrypoint writes only after
provisioning finishes. The probe therefore answers "the site finished setting
itself up and Apache is alive" without touching PHP or the database. That is
deliberate: restarting the container fixes a dead Apache, never a dead database.

All three probes are HTTP, port 80, path `/kramo-health`.

| Probe | initialDelaySeconds | periodSeconds | timeoutSeconds | failureThreshold |
| --- | --- | --- | --- | --- |
| startup | 60 | 15 | 5 | 60 |
| readiness | 0 | 15 | 5 | 3 |
| liveness | 0 | 30 | 10 | 5 |

The startup probe buys a 15 minute window (60 × 15s) and gates the other two,
so a slow provisioning run cannot be killed halfway and restarted in a loop. A
full seed on `nf-compute-20` took about 2.5 minutes against a warm database.

Liveness is deliberately slack — 2.5 minutes of consecutive failures before a
restart — because a brief load spike on 0.2 vCPU must not read as a dead
container.

If the platform does not offer a startup probe, drop liveness entirely and keep
readiness alone with `initialDelaySeconds: 600`. Liveness without a startup
probe is what restarts the container mid-install.

## 5. Moving existing content

The demo provisions itself from scratch, so nothing has to be migrated. If a
Railway database still holds real orders, export and import it instead:

```sh
railway run --service wordpress wp db export - --path=/var/www/html > kramo.sql
mysql -h <addon-host> -P <addon-port> -u <addon-user> -p <addon-db> < kramo.sql
```

Then run a search-replace for the changed domain from the Northflank shell:

```sh
wp search-replace 'https://kramo-production.up.railway.app' "$WORDPRESS_SITE_URL" --all-tables --path=/var/www/html
```

## 6. Resetting the demo

Open the service shell and reset, same as on Railway:

```sh
wp db reset --yes --path=/var/www/html
```

The next restart re-provisions everything.
