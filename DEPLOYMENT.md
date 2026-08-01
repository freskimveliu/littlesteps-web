# Publishing Little Steps

Little Steps is published to the existing server that already runs Vault — the
EC2 box at `3.121.93.121` (`eu-central-1`, Ubuntu 24.04). It does not get its
own server, its own database, or its own reverse proxy. It reuses what is there
and adds three containers of its own.

Public URL: **https://littlesteps.freskimveliu.dev**

> This is not `docker-compose.production.yml`. That file is the self-contained
> stack — its own MySQL, its own Redis, Traefik out front — and it is what you
> would use on a server of your own. On the shared box the file that counts is
> `deploy/docker-compose.server.yml`.

---

## What runs where

```
                      internet
                          │
                    :443 Caddy  (vault stack, terminates TLS, Let's Encrypt)
                    ╱             ╲
   vault.freskimveliu.dev     littlesteps.freskimveliu.dev
            │                          │
       vault_app                 littlesteps_app        ← nginx + php-fpm, port 80
                                 littlesteps_queue      ← queue:work
                                 littlesteps_scheduler  ← schedule:run every 60s
                          ╲            │            ╱
                        vault_database (MariaDB 10.11)   ← database `littlesteps`
                        vault_redis    (Redis 7)         ← DB 2 (default) + 3 (cache)
```

Everything sits on the `vault_network` Docker network, which is why Caddy can
reach `littlesteps_app` and the app can reach `vault_database` by name.

| Thing | Value |
| --- | --- |
| Server directory | `/opt/littlesteps` |
| Image | `ghcr.io/freskimveliu/littlesteps-web:main` |
| Database | `littlesteps` on the vault MariaDB, user `littlesteps` |
| Redis | vault Redis, DB **2** (sessions/queue) and **3** (cache), prefix `littlesteps-database-` |
| Uploads | `/opt/littlesteps/laravel/storage` (bind mount, survives deploys) |
| Deploy webhook | `https://littlesteps.freskimveliu.dev/webhook/deploy` → host port **9001** |
| Caddy site block | vault repo, `ansible/docker/caddy/Caddyfile.j2` |

### Why it shares the database

The box is a `t3.small` — 2 vCPU, **2 GB of RAM** — and the vault stack already
runs nine containers on it. A second MySQL plus a second Redis would have added
roughly 500 MB and pushed it over. Sharing costs isolation: a MariaDB problem
takes down both apps, and the two apps' data sits in one data directory. If
Little Steps grows, the clean fix is a bigger instance (`t3.medium`, ~2× the
EC2 cost) and its own `database`/`redis` containers — the compose file in the
repo root already describes exactly that stack.

Two consequences worth knowing:

- **MariaDB, not MySQL 8.4.** `DB_CONNECTION=mariadb` in production while dev
  and CI run MySQL 8.4. The schema uses nothing MySQL-specific (the only
  interesting columns are the media library's `json` ones, which MariaDB
  stores as `LONGTEXT` with a `JSON_VALID` check), so this is safe — but it
  is a difference between environments. Keep it in mind for anything that
  reaches for raw SQL.
- **The playbook adds a 2 GB swapfile** with `vm.swappiness=10`, so the box has
  headroom rather than an OOM killer. Watch memory at
  https://metrics.vault.freskimveliu.dev after the first deploy.

---

## Before the first publish

You need, once:

1. **A DNS A record** — `littlesteps.freskimveliu.dev` → `3.121.93.121`.
   Caddy requests the certificate the first time the name is hit, so DNS has to
   resolve before the site will answer on HTTPS.

2. **Ansible and its collections**, locally:

   ```bash
   brew install ansible
   ansible-galaxy collection install -r deploy/ansible/requirements.yml
   ```

3. **The SSH key for the server.** The vault repo already has it — link it:

   ```bash
   ln -s ../../../vault/ansible/ssh_key deploy/ansible/ssh_key
   ```

4. **Secrets.** Copy the template and fill every value:

   ```bash
   cp deploy/ansible/.env.example deploy/ansible/.env
   ```

   | Variable | Where it comes from |
   | --- | --- |
   | `APP_KEY` | `./bin/docker-exec artisan key:generate --show` — a **fresh** key, never the dev one |
   | `VAULT_DB_ROOT_PASSWORD` | `DB_PASSWORD` in the vault repo's `ansible/.env` (that stack uses it as the MariaDB root password) |
   | `LITTLESTEPS_DB_PASSWORD` | new: `openssl rand -base64 24` |
   | `VAULT_REDIS_PASSWORD` | `REDIS_PASSWORD` in the vault repo's `ansible/.env` |
   | `GITHUB_TOKEN` | a token with `read:packages`, so the server can pull from GHCR |
   | `DEPLOY_WEBHOOK_SECRET` | new: `openssl rand -hex 32` — the same value goes into GitHub |

   `deploy/ansible/.env` and `ssh_key` are gitignored. They are the only
   secrets on your machine; nothing sensitive is committed.

5. **The GitHub repository secret.** In
   `github.com/freskimveliu/littlesteps-web` → Settings → Secrets and variables
   → Actions, add `DEPLOY_WEBHOOK_SECRET` with the same value as above.

---

## First publish

Do these in order. Steps 1 and 2 can be done in either order, but the image
must exist in GHCR before step 4 runs, and Caddy must know the hostname before
the site answers.

### 1. Build the first image

```bash
git add .github deploy DEPLOYMENT.md
git commit -m "Add deployment to the shared server"
git push origin main
```

The workflow runs PHPStan and the test suite, then pushes
`ghcr.io/freskimveliu/littlesteps-web:main` (and a tag for the commit SHA).

**The `deploy` job will fail on this first run** — the webhook does not exist on
the server yet. That is expected. Everything up to and including the image push
is what matters here.

### 2. Teach Caddy about the hostname

The site block lives in the vault repo (Caddy belongs to that stack) and is
already written — it just needs to be applied:

```bash
cd ../vault
git add ansible/ && git commit -m "Route littlesteps.freskimveliu.dev" && git push
cd ansible
./update.sh 3.121.93.121
```

This re-renders `/opt/vault/docker/caddy/Caddyfile` with the Little Steps site
and reloads Caddy. Caddy is happy to hold a route whose upstream does not exist
yet — until step 4 the site simply returns 502.

If the vault repo's `ansible/.env` predates this change it does not matter:
`LITTLESTEPS_DOMAIN` defaults to `littlesteps.freskimveliu.dev` in the
playbook. Set it to an empty string there if you ever want the block gone.

### 3. Configure the server

```bash
cd deploy/ansible
./apply.sh
```

This is the whole install, and it is idempotent — run it again any time. It:

- creates `/opt/littlesteps` and the persistent `laravel/storage` tree,
- adds the swapfile,
- creates the `littlesteps` database and user in the vault MariaDB,
- writes `laravel/.env`, `docker-compose.yml`, `scripts/deploy.sh` and the
  webhook server,
- installs and starts `littlesteps-deploy-webhook.service` on port 9001,
- allows that port from the Docker network only (UFW) and adds a Fail2ban jail
  for the new access log,
- logs in to GHCR, pulls the image, runs `migrate --force`, starts the
  containers and waits for the health check to pass.

The catalogue is seeded by a migration (`import_catalogue`), so there is no
separate seeding step.

### 4. Create the first admin user

```bash
ssh ubuntu@3.121.93.121
cd /opt/littlesteps
docker compose exec app php artisan tinker --execute="
  \App\Models\User::create([
    'name' => 'Freskim',
    'email' => 'freskim.veliu@gmail.com',
    'password' => 'change-this-now',
  ])->forceFill(['is_admin' => true])->save();
"
```

The `password` attribute is cast to `hashed`, so pass it in plain — it is
hashed on save. Admin sign-in refuses any account without `is_admin`.

### 5. Verify

```bash
curl -sI https://littlesteps.freskimveliu.dev/up      # 200, valid certificate
open https://littlesteps.freskimveliu.dev/admin       # sign in as the admin user
```

Then re-run the failed `deploy` job in GitHub Actions (or push any commit) to
confirm the webhook path works end to end.

### 6. Add it to the monitoring you already have

- **Uptime Kuma** — https://status.vault.freskimveliu.dev → add an HTTP monitor
  for `https://littlesteps.freskimveliu.dev/up`.
- **Dozzle** — https://logs.vault.freskimveliu.dev already lists the three new
  containers; nothing to configure.
- **Netdata** — https://metrics.vault.freskimveliu.dev; watch memory for the
  first few days.

---

## Every deploy after that

```bash
git push origin main
```

That is the whole thing. The workflow tests, builds, pushes, and calls the
webhook; `deploy.sh` on the server pulls the new image, runs migrations, swaps
the app container, waits for it to report healthy, and only then restarts the
workers. If the new container never becomes healthy, the script stops and the
workflow's health check fails — the old workers stay on the old image.

Pull requests run the tests but do not build or deploy.

### Deploying by hand

```bash
ssh ubuntu@3.121.93.121
/opt/littlesteps/scripts/deploy.sh
```

### Rolling back

Every build is also tagged with its commit SHA:

```bash
ssh ubuntu@3.121.93.121
cd /opt/littlesteps
vim .env                       # DOCKER_IMAGE_TAG=<the good SHA>
docker compose up -d --no-deps app
```

Set the same `DOCKER_IMAGE_TAG` in `deploy/ansible/.env` if you plan to re-run
`apply.sh` before shipping the fix forward — otherwise it puts `main` back.

Rolling back does **not** undo migrations. If a migration is the problem, write
a forward fix.

---

## Day to day

All of these run from `/opt/littlesteps` on the server:

```bash
docker compose ps                              # what is running
docker compose logs app --tail=50 -f           # app logs
docker compose exec app php artisan <command>  # artisan
docker compose exec app bash                   # shell in the container

sudo journalctl -u littlesteps-deploy-webhook -f   # deploy webhook, live
sudo tail -f /var/log/littlesteps-deploy-webhook.log

docker exec -it vault_database mariadb -ulittlesteps -p littlesteps   # SQL shell
```

Reclaiming memory, if the box gets tight — nothing is queued or scheduled yet,
so both workers are idle and a deploy will leave them stopped once you do this:

```bash
docker compose stop queue scheduler
```

Configuration changes (`laravel/.env`, the compose file) go through
`deploy/ansible/apply.sh` from your machine — **never edit files on the server**,
they are overwritten on the next run. Note that `docker compose restart` does
not reload `env_file`; use `docker compose up -d --no-deps app`.

---

## Backups

State that cannot be rebuilt:

| What | Where |
| --- | --- |
| Database (both apps) | `/opt/vault/data/mysql` |
| Little Steps uploads | `/opt/littlesteps/laravel/storage/app/public` |
| TLS certificates | `/opt/vault/caddy_data` |

A database-only dump:

```bash
docker exec vault_database mariadb-dump -uroot -p<root-pw> \
  --databases littlesteps | gzip > littlesteps-$(date +%F).sql.gz
```

There is no automated backup on this server yet — for either app. Worth fixing
before real families are using it.

---

## Gotchas

- **The image is the deploy unit.** Nothing is bind-mounted except `storage`,
  so `git pull` on the server does nothing. A change ships only once CI has
  built and pushed a new image.
- **`.env` files, plural.** `/opt/littlesteps/laravel/.env` is the app's
  configuration. `/opt/littlesteps/.env` is Compose's — it holds only
  `DOCKER_IMAGE_TAG`. Compose does not read the one in `laravel/`.
- **Caddy is not ours.** Anything about routing, TLS or the webhook path is in
  the vault repo. Change it there, apply with that repo's `./update.sh`.
- **Port 9000 is vault's webhook.** Ours is 9001. Both are closed to the
  internet — UFW only allows them from `172.16.0.0/12`, i.e. from Caddy.
- **Migrations run on every deploy**, before the new container starts. Write
  destructive migrations carefully.
- **`TRUSTED_PROXIES=*`** is set, and `bootstrap/app.php` already calls
  `trustProxies(at: '*')`. Without it Laravel would generate `http://` URLs
  behind Caddy.
- **Mail is not configured.** `MAIL_MAILER=log`, so password resets and any
  other mail go to the log and nowhere else. Set real SMTP credentials in the
  playbook's `.env` block when that matters.

---

## Still to do, deliberately left out

- **The mobile app points somewhere else.** `littlesteps/src/services/api/client.ts:7`
  uses `https://api.littlesteps.app/api` for release builds and
  `http://localhost:3000/api` in development. Point it at
  `https://littlesteps.freskimveliu.dev/api` — or, if `littlesteps.app` is
  yours, add that hostname to the Caddy site block (Caddy will issue a
  certificate for both) and leave the app alone.
- **No automated backups**, as above.
- **No staging environment.** `main` goes straight to production.
