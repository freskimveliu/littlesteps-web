# Publishing Little Steps

Little Steps runs on its own EC2 instance — its own database, its own Redis,
nothing shared with any other project. Routing and TLS come from the shared
proxy in `../proxy`, deployed alongside it, exactly as in local development.

Public URL: **https://littlesteps.freskimveliu.dev**

Deploys are push-to-`main`: GitHub Actions runs the tests, builds the image to
GHCR, and calls a signed webhook that swaps the container on the server.

---

## What runs where

```
                    internet
                        │
                  :80/:443  Traefik            /opt/proxy   (from ../proxy)
                        │                       Let's Encrypt, HTTP-01
                        │  proxy network
                  littlesteps_app               /opt/littlesteps
                        │                       nginx + php-fpm, port 80
                        │  internal network
        ┌───────────────┼───────────────┐
   littlesteps_     littlesteps_    littlesteps_queue
     database          redis        littlesteps_scheduler
    MySQL 8.4         Redis 8
```

The `internal` network has no route in from outside — MySQL and Redis publish
no ports. Only the app is on the `proxy` network, and only because Traefik
needs to reach it.

| Thing | Value |
| --- | --- |
| Server directory | `/opt/littlesteps` (and `/opt/proxy` for Traefik) |
| Image | `ghcr.io/freskimveliu/littlesteps-web:main` |
| Instance | `t3.small` (2 vCPU / 2 GB) + a 2 GB swapfile, 20 GB gp3, `eu-central-1` |
| Database | MySQL 8.4, `/opt/littlesteps/data/mysql` |
| Uploads | `/opt/littlesteps/laravel/storage` |
| Deploy webhook | `https://littlesteps.freskimveliu.dev/webhook/deploy` → port 9000, Docker-only |
| Open ports | 22, 80, 443. Everything else denied by UFW |

`t3.small` is deliberate: the stack fits in 2 GB with the swapfile, and the
instance can be resized later without touching anything in this repo. If PHP
requests start swapping, move to `t3.medium` — stop the instance, change the
type, start it. The Elastic IP survives, so DNS does not change.

---

## Before the first publish

1. **Ansible and its collections:**

   ```bash
   brew install ansible
   ansible-galaxy collection install -r deploy/ansible/requirements.yml
   ```

2. **The proxy repo** must sit next to this one (`../proxy`). The playbook
   copies its production Traefik config to the server; it is not vendored here,
   so the two stay in step.

3. **Secrets.** Copy the template and fill it in:

   ```bash
   cp deploy/ansible/.env.example deploy/ansible/.env
   ```

   | Variable | Where it comes from |
   | --- | --- |
   | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | an IAM user allowed to create EC2 instances — only used by `setup.sh` |
   | `APP_KEY` | `./bin/docker-exec artisan key:generate --show` — a **fresh** key, never the dev one |
   | `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `REDIS_PASSWORD` | new: `openssl rand -base64 24` each |
   | `GITHUB_TOKEN` | a token with `read:packages`, so the server can pull from GHCR |
   | `DEPLOY_WEBHOOK_SECRET` | new: `openssl rand -hex 32` — the same value goes into GitHub |

   `deploy/ansible/.env`, `ssh_key` and `ssh_key.pub` are gitignored.

4. **The GitHub repository secret.** In
   `github.com/freskimveliu/littlesteps-web` → Settings → Secrets and variables
   → Actions, add `DEPLOY_WEBHOOK_SECRET` with the same value as above. Or:

   ```bash
   echo -n '<the value>' | gh secret set DEPLOY_WEBHOOK_SECRET --repo freskimveliu/littlesteps-web
   ```

---

## First publish

### 1. Build the first image

```bash
git push origin main
```

The workflow runs the test suite, then pushes
`ghcr.io/freskimveliu/littlesteps-web:main` (and a tag for the commit SHA).

**The `deploy` job fails on this first run** — there is no server yet. Expected;
the image push is what matters.

PHPStan is *not* a gate. `composer phpstan` reports around 300 errors at level 6
today — 194 of them Pest higher-order syntax in `tests/` that PHPStan cannot
resolve, the rest across `app/`, `config/` and `database/`. None of it is new.
The step is commented out in the workflow and should go back in once it runs
clean.

### 2. Provision the server and deploy

```bash
cd deploy/ansible
./setup.sh
```

This creates the key pair, security group, instance and Elastic IP, then
configures everything: Docker, UFW, Fail2ban, the swapfile, the Traefik stack
in `/opt/proxy`, and Little Steps in `/opt/littlesteps` — ending with `migrate
--force` and the containers up.

It prints the Elastic IP and pauses for nothing, so do both of these as soon as
it appears:

- **Point DNS at it** — `littlesteps.freskimveliu.dev` → the new IP. Traefik
  requests the certificate over HTTP-01, so HTTPS only works once the name
  resolves to this server.
- **Put `SERVER_IP=<that IP>` in `deploy/ansible/.env`** so every later run uses
  `./apply.sh` against the existing box instead of building another one.

The catalogue is seeded by a migration (`import_catalogue`) — no separate step.

### 3. Create the first admin user

```bash
ssh -i deploy/ansible/ssh_key ubuntu@<IP>
cd /opt/littlesteps
docker compose exec app php artisan tinker --execute="
  \App\Models\User::create([
    'name' => 'Freskim',
    'email' => 'freskim.veliu@gmail.com',
    'password' => 'change-this-now',
  ])->forceFill(['is_admin' => true])->save();
"
```

The `password` attribute is cast to `hashed`, so pass it in plain. Admin sign-in
refuses any account without `is_admin`.

### 4. Verify

```bash
curl -sI https://littlesteps.freskimveliu.dev/up      # 200, valid certificate
open https://littlesteps.freskimveliu.dev/admin
```

Then re-run the failed `deploy` job in GitHub Actions to confirm the webhook
path works end to end.

---

## Every deploy after that

```bash
git push origin main
```

Actions tests, builds, pushes, and calls the webhook. On the server,
`deploy.sh` pulls the new image, runs migrations, swaps the app container,
waits for it to report healthy, and only then restarts the workers. If the new
container never becomes healthy the script stops and the workflow fails — the
old workers keep running the old image.

Pull requests run the tests but do not build or deploy.

### Server or config changes

Anything in `deploy/` — the compose file, `laravel/.env`, the playbook:

```bash
cd deploy/ansible && ./apply.sh
```

Never edit files on the server; the next run overwrites them. Note that
`docker compose restart` does not reload `env_file` — use
`docker compose up -d --no-deps app`.

### Deploying by hand

```bash
ssh -i deploy/ansible/ssh_key ubuntu@<IP>
/opt/littlesteps/scripts/deploy.sh
```

### Rolling back

Every build is also tagged with its commit SHA:

```bash
cd /opt/littlesteps
vim .env                       # DOCKER_IMAGE_TAG=<the good SHA>
docker compose up -d --no-deps app
```

Set the same `DOCKER_IMAGE_TAG` in `deploy/ansible/.env` if you will run
`apply.sh` before shipping the fix forward — otherwise it puts `main` back.

Rolling back does **not** undo migrations. If a migration is the problem, write
a forward fix.

---

## Day to day

From `/opt/littlesteps` on the server:

```bash
docker compose ps                              # what is running
docker compose logs app --tail=50 -f           # app logs
docker compose exec app php artisan <command>  # artisan
docker compose exec app bash                   # shell in the container
docker compose exec database mysql -ulittlesteps -p littlesteps

docker compose -f /opt/proxy/docker-compose.yml logs -f   # Traefik, certificates

sudo journalctl -u littlesteps-deploy-webhook -f
sudo tail -f /var/log/littlesteps-deploy-webhook.log
```

Nothing is queued or scheduled yet — every media conversion is `nonQueued()`,
no job implements `ShouldQueue`, and `routes/console.php` is empty. Both worker
containers idle at roughly 60 MB each, and `docker compose stop queue scheduler`
survives deploys if you want that memory back.

---

## Backups

State that cannot be rebuilt:

| What | Where |
| --- | --- |
| Database | `/opt/littlesteps/data/mysql` |
| Uploads | `/opt/littlesteps/laravel/storage/app/public` |
| TLS certificates | the `letsencrypt` volume of the proxy stack (`acme.json`) |

A database dump:

```bash
docker compose exec -T database mysqldump -uroot -p<root-pw> littlesteps \
  | gzip > littlesteps-$(date +%F).sql.gz
```

There is no automated backup yet. Worth fixing before real families are using
it — an EBS snapshot schedule is the least effort.

---

## Gotchas

- **The image is the deploy unit.** Nothing is bind-mounted except `storage`,
  so `git pull` on the server does nothing. A change ships only once CI has
  built and pushed a new image.
- **`.env` files, plural.** `/opt/littlesteps/laravel/.env` is the app's
  configuration. `/opt/littlesteps/.env` is Compose's — image tag, hostname and
  the database and Redis passwords. Compose does not read the one in `laravel/`.
- **The certificate needs DNS first.** Traefik uses the HTTP-01 challenge, so
  the hostname must resolve to the server before HTTPS works. Until then the
  site answers on HTTP or not at all.
- **Migrations run on every deploy**, before the new container starts. Write
  destructive migrations carefully.
- **`TRUSTED_PROXIES=*`** is set, and `bootstrap/app.php` already calls
  `trustProxies(at: '*')`. Without it Laravel generates `http://` URLs behind
  Traefik.
- **Mail is not configured.** `MAIL_MAILER=log`, so password resets go to the
  log and nowhere else. Add real SMTP credentials to the playbook's `.env` block
  when that matters.
- **`setup.sh` creates a server every time it runs.** Use `apply.sh` for
  everything after the first time; it refuses to provision.

---

## Still to do, deliberately left out

- **The mobile app points somewhere else.** `littlesteps/src/services/api/client.ts:7`
  uses `https://api.littlesteps.app/api` for release builds and
  `http://localhost:3000/api` in development. Point it at
  `https://littlesteps.freskimveliu.dev/api` — or, if `littlesteps.app` is
  yours, add a second `Host()` rule to the app's Traefik labels (Traefik will
  get a certificate for both) and leave the app alone.
- **No automated backups**, as above.
- **No monitoring.** The vault box has Uptime Kuma, Netdata and Dozzle; this one
  has nothing. The cheapest useful start is an external uptime check against
  `/up`.
- **No staging environment.** `main` goes straight to production.
