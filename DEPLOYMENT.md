# Deployment

Little Steps runs as a second application on the existing **vault** server. It
does not get a box of its own.

> **`$SERVER_IP` is a placeholder throughout this file.** This repository is
> public, so the address is not written down here — it lives in the `SSH_HOST`
> repo secret and in `vault/ansible/inventory.ini`. Export it before pasting
> any command below:
> ```bash
> export SERVER_IP=<the address>
> ```

## The short version

Steps 1-3 below are automated. Run this once and skip to step 4:

```bash
scp -i <key> -r deploy ubuntu@$SERVER_IP:/tmp/
ssh -i <key> ubuntu@$SERVER_IP 'bash /tmp/deploy/bootstrap-server.sh'
```

The long-form steps are kept because they explain *why* each piece exists, and
because you will want them the day the script is not what you need.

## What is already there

`$SERVER_IP` — AWS EC2 `t3.small`, eu-central-1, Ubuntu 24.04, user `ubuntu`.
The vault stack lives in `/opt/vault` and runs nine containers: Caddy on
80/443, `vault_app`, `vault_horizon`, `vault_scheduler`, `vault_database`
(MariaDB 10.11), `vault_redis` (Redis 7), plus Uptime Kuma, Netdata and Dozzle.
They all sit on a bridge network called `vault_network`.

**The constraint that shapes everything below: 2 GB of RAM, roughly 730 MB free
with vault running, and no swap.** Disk is comfortable (~8.7 GB free).

## What we add

Three containers in their own compose project at `/opt/littlesteps`, attached
to `vault_network`:

| Container                | Role                                            | Ceiling |
| ------------------------ | ----------------------------------------------- | ------- |
| `littlesteps_app`        | nginx + php-fpm on port 80                      | 768 MB  |
| `littlesteps_queue`      | `queue:work`                                    | 320 MB  |
| `littlesteps_scheduler`  | `schedule:run` every 60s                        | 192 MB  |

Data comes from vault's containers — its MariaDB (our own database and user)
and its Redis (our own DB indexes and key prefix).

Three consequences worth understanding before you touch anything:

- **We reuse vault's datastores because a second MySQL does not fit.** The
  self-contained stack in `docker-compose.production.yml` brings MySQL 8.4 and
  Redis 8; MySQL 8.4 alone wants ~400 MB, and the app tier already needs ~350 MB
  of the ~730 MB free. That file remains the right choice on a dedicated box —
  it is not deprecated, it is just for a different machine.
- **The image is never built on the server.** `npm run build` (Vite 8 +
  Tailwind 4) needs more memory than is free. CI builds it and the server pulls
  it from GHCR.
- **We are a separate compose project on purpose.** vault's `update.sh` copies
  its own compose file over `/opt/vault/docker-compose.yml`, so anything of
  ours living in that directory would be destroyed on vault's next config
  change.

The queue worker and scheduler have no work yet — there is no `ShouldQueue` job
in the app, all five Spatie conversions are `nonQueued()`, and
`routes/console.php` schedules nothing. They are deployed anyway so that the
first queued job or scheduled command ships without a deployment change.

## MariaDB instead of MySQL

Production is MariaDB 10.11; dev and CI are MySQL 8.4. That divergence is
deliberate, and it was checked rather than assumed:

- The only `json()` columns are in Spatie's `media` table. MariaDB stores JSON
  as LONGTEXT, which is fine because nothing reads them as JSON.
- The app makes **no** `whereJsonContains`, `whereJsonLength` or `fullText`
  calls anywhere in `app/` or `database/`.
- `config/database.php` uses `utf8mb4` / `utf8mb4_unicode_ci`, both of which
  MariaDB 10.11 supports.

If a MariaDB-specific failure ever does appear, switch the `services.mysql`
image in `.github/workflows/deploy.yml` to `mariadb:10.11` so CI reproduces it.

---

# First-time setup

Steps 1-6 are one-time. After that, deploys are `git push`.

## 1. Add swap (do this first)

With no swap and ~380 MB of headroom left after our containers start, one
oversized image conversion is enough to invoke the OOM killer — and it chooses
its victim by score, so it may well kill `vault_database` rather than us.

```bash
ssh ubuntu@$SERVER_IP

sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Prefer RAM; only swap under real pressure.
sudo sysctl vm.swappiness=10
echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/99-swappiness.conf

free -m   # confirm: Swap total should now be 2047
```

## 2. Create the database and user

Little Steps gets its own database and its own credentials — it must not be
able to read vault's tables.

```bash
# On the server. Uses vault's root password from its own .env.
cd /opt/vault
docker compose exec database mariadb -uroot -p
```

```sql
CREATE DATABASE littlesteps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'littlesteps'@'%' IDENTIFIED BY 'PUT-A-REAL-SECRET-HERE';
GRANT ALL PRIVILEGES ON littlesteps.* TO 'littlesteps'@'%';
FLUSH PRIVILEGES;
```

Grant only on `littlesteps.*`. A wildcard grant here would hand the app vault's
data.

## 3. Create the directories and the env file

```bash
sudo mkdir -p /opt/littlesteps/{laravel/storage,php}
sudo chown -R ubuntu:ubuntu /opt/littlesteps
```

Copy `deploy/.env.server.example` from this repo to
`/opt/littlesteps/laravel/.env` and fill in every `CHANGEME`:

- `APP_KEY` — generate a **fresh** one, never the dev key. `key:generate` is
  just 32 random bytes base64-encoded, so this is equivalent and does not need
  the image (which does not exist yet on first setup):
  ```bash
  echo "base64:$(openssl rand -base64 32)"
  ```
- `DB_PASSWORD` — what you set in step 2.
- `REDIS_PASSWORD` — vault's. Both passwords you need are in one place:
  ```bash
  grep -E '^(DB|REDIS)_PASSWORD' /opt/vault/laravel/.env
  ```
  Note vault's MariaDB **root** password is its `DB_PASSWORD` — the compose
  file passes the same value to `MYSQL_ROOT_PASSWORD`.

That file is the only place secrets live. It is never overwritten by a deploy.

Redis is shared, so the env file puts us on DB indexes 2 and 3 (vault uses the
Laravel defaults, 0 and 1) with a `littlesteps_` key prefix on top.

## 4. GHCR access — nothing to do

There is deliberately no PAT on the server. The deploy workflow authenticates
it to GHCR on every run using that run's own `GITHUB_TOKEN`, piped over stdin
so it never appears in the server's process list. The token dies with the job,
so there is no standing credential to leak or rotate.

The one consequence: a **manual** `deploy.sh` run may hit `unauthorized` if no
workflow has run recently. Re-run the workflow, or log in by hand for that
session:

```bash
echo "<a PAT with read:packages>" | docker login ghcr.io -u freskimveliu --password-stdin
```

## 5. Point DNS, then add the Caddy route

Create the A record **first** and let it resolve. Caddy requests a certificate
as soon as it loads the new config, and failed attempts count against Let's
Encrypt rate limits.

```
littlesteps.freskimveliu.dev.  A  $SERVER_IP
```

The route itself is already committed — in the **vault** repo, at
`ansible/docker/caddy/Caddyfile.j2`, alongside a `littlesteps_domain` variable
in both playbooks. It has to live there because `update.sh` re-renders that
template over the server's Caddyfile and would silently drop a hand-added
block.

```bash
cd ~/Desktop/Projects/vault/ansible
./update.sh $SERVER_IP
```

The route answers 503 until step 6 puts a container behind it. Vault is
unaffected either way.

## 6. Set the GitHub secrets and deploy

In `littlesteps-web` → Settings → Secrets and variables → Actions:

| Secret            | Value                                                |
| ----------------- | ---------------------------------------------------- |
| `SSH_HOST`        | `$SERVER_IP`                                       |
| `SSH_USER`        | `ubuntu`                                             |
| `SSH_PRIVATE_KEY` | contents of the `.pem` that reaches the box, verbatim including the BEGIN/END lines |

Also create a `production` environment (Settings → Environments) — the deploy
job references it, and it is where you would add a required reviewer later.

Then push:

```bash
git add .github deploy DEPLOYMENT.md .dockerignore
git commit -m "Deploy to the shared vault server"
git push origin main
```

The workflow runs the tests, pushes
`ghcr.io/freskimveliu/littlesteps-web:main` (plus a commit-SHA tag), copies the
compose file and PHP overrides to the server, and runs `deploy.sh`.

PHPStan is **not** a gate. `composer phpstan` reports ~300 errors at level 6
today, 194 of them Pest higher-order syntax in `tests/` that PHPStan cannot
resolve. None of it is new and none of it is about deployment. The step goes
back in once it runs clean.

Then create the first user. There is no `make:admin` command in this project,
so use tinker:

```bash
ssh ubuntu@$SERVER_IP
cd /opt/littlesteps
docker compose exec app php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Freskim',
    'email' => 'freskim.veliu@gmail.com',
    'password' => bcrypt('a-real-password'),
]);
```

**Do not run `php artisan db:seed` on this server.** `DatabaseSeeder` calls
`DemoSeeder`, which exists to populate a development database with fake
families and children.

`migrate --force` runs on every deploy, so the catalogue import migration
(`2026_08_01_100003_import_catalogue`) has already loaded
`database/data/*.json` by this point. It does not need to be run by hand.

---

# Everyday deploys

Push to `main`. That is the whole procedure.

```
push to main
  → tests (MySQL 8.4 service, PHPUnit)
  → build linux/amd64, push to ghcr.io/freskimveliu/littlesteps-web:main
  → scp compose + PHP overrides to /opt/littlesteps
  → deploy.sh: pull → migrate --force → up -d → wait for healthy → prune
  → health check https://littlesteps.freskimveliu.dev/up
```

Migrations run with the **new** image before the running containers are
replaced, so write destructive migrations carefully — there is no automatic
rollback.

To deploy by hand, or to roll back to a specific build:

```bash
ssh ubuntu@$SERVER_IP
/opt/littlesteps/deploy.sh main        # or a commit SHA tag
```

## Operations

```bash
cd /opt/littlesteps

docker compose ps
docker compose logs app --tail=50
docker compose exec app php artisan <command>
docker compose exec app bash

# .env changes need a recreate — `restart` does NOT reload env_file
docker compose up -d --no-deps app
```

Vault's Dozzle at `https://logs.vault.freskimveliu.dev` streams our containers
too — it reads the Docker socket, so it sees everything on the host. Netdata at
`https://metrics.vault.freskimveliu.dev` is the place to watch memory.

## Backups

Two things on this box cannot be rebuilt:

- the `littlesteps` database inside `vault_database` (`/opt/vault/data/mysql`)
- `/opt/littlesteps/laravel/storage` — uploaded media

```bash
docker exec vault_database mariadb-dump -uroot -p littlesteps > littlesteps-$(date +%F).sql
tar czf littlesteps-storage-$(date +%F).tar.gz -C /opt/littlesteps/laravel storage
```

## Gotchas

- **Never hand-edit `/opt/vault/docker/caddy/Caddyfile`.** vault's `update.sh`
  re-renders it from the Jinja template. Edit
  `vault/ansible/docker/caddy/Caddyfile.j2` instead.
- **Never put our files in `/opt/vault`.** vault's Ansible overwrites that
  directory's compose file.
- **`docker compose restart` does not reload `env_file`.** Use
  `up -d --no-deps <service>`.
- **The image has no `.env`** — the builder stage deletes it. Everything comes
  from `env_file`, which reaches PHP only because the image sets
  `clear_env = no` in the php-fpm pool.
- **`TRUSTED_PROXIES=*` is required.** Caddy terminates TLS and forwards plain
  HTTP; without it Laravel generates `http://` URLs for every route and asset.
  `bootstrap/app.php` already calls `trustProxies(at: '*')` — its comment says
  Traefik, but the mechanism is identical.
- **`opcache.validate_timestamps=0` in the production image.** Code changes
  need a new container, not a restart. A normal deploy does this; editing files
  inside a running container does nothing.
- **PHP memory is capped below the image defaults** — 384M instead of 1024M,
  and 4 php-fpm workers instead of 5. See `deploy/php/README.md` for why, and
  for the log line that means the box needs to grow.
