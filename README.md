# Little Steps

Laravel + Inertia + Vue + Tailwind, running on Docker behind Traefik with local TLS.

| Layer     | Version                                  |
| --------- | ---------------------------------------- |
| Laravel   | 13.x (PHP 8.4, FPM)                      |
| Inertia   | 3.x (`inertiajs/inertia-laravel`, `@inertiajs/vue3`) |
| Vue       | 3.5 (TypeScript, SFC)                    |
| Tailwind  | 4.x (via `@tailwindcss/vite`)            |
| Vite      | 8.x                                      |
| MySQL     | 8.4                                      |
| Redis     | 8.x (cache, sessions, queue)             |

## Layout

```
.
├── Dockerfile                      # multi-stage: base → development / builder → production
├── docker-compose.yml              # dev stack
├── docker-compose.production.yml   # server stack
├── .env                            # single source of truth (laravel/.env symlinks to it)
├── bin/docker-exec                 # command proxy into the containers
├── docker/
│   ├── nginx/                      # dev webserver image + vhost
│   └── mysql/init/                 # creates the `testing` database
└── laravel/                        # the application
```

TLS and routing are **not** in this repo. A shared Traefik in `../proxy` owns
80/443 and serves every project, so several apps can run at once on clean
hostnames. This stack only publishes a container for Traefik to find.

## Changing the domain

`APP_URL_TRAEFIK` in `.env` is the only place the hostname is defined — the
Traefik router label reads it. To move the app to another domain, change that
value (and `APP_URL`/`VITE_HMR_HOST`), point DNS at the host, and restart.
Nothing else is domain-specific.

## First-time setup

1. Start the shared proxy — see `../proxy/README.md`. It owns 80/443 and holds
   the certificate for `*.dev.freskimveliu.dev`.

2. Point the hostname at your machine. There is no public DNS record for
   `*.dev.freskimveliu.dev`, so each dev hostname needs an `/etc/hosts` entry:

   ```bash
   echo "127.0.0.1 littlesteps.dev.freskimveliu.dev" | sudo tee -a /etc/hosts
   ```

   Without it the browser fails with `ERR_NAME_NOT_RESOLVED` before it ever
   reaches Traefik — even with the whole stack running.

3. Copy the env file and generate a key (already done on this checkout):

   ```bash
   cp .env.example .env
   ./bin/docker-exec artisan key:generate
   ```

4. Start everything and migrate:

   ```bash
   docker compose up -d
   ./bin/docker-exec artisan migrate
   ```

The app is at **https://littlesteps.dev.freskimveliu.dev**.

## Services

| Container                | Role                                                    |
| ------------------------ | ------------------------------------------------------- |
| `littlesteps_webserver`  | Nginx, serves `laravel/public`                          |
| `littlesteps_php`        | PHP-FPM 8.4 with Xdebug                                 |
| `littlesteps_node`       | Vite dev server with HMR (proxied through Traefik)      |
| `littlesteps_database`   | MySQL 8.4 (host port `33062`)                           |
| `littlesteps_redis`      | Redis 8 (host port `63792`)                             |
| `littlesteps_queue`      | `queue:work`                                            |
| `littlesteps_scheduler`  | `schedule:run` every 60s                                |

Vite is not started by hand — the `node` container runs `npm install && npm run dev`
on boot, and Traefik routes `/@vite`, `/@id`, `/@fs`, `/node_modules` and
`/resources` to it so HMR works over the same HTTPS origin as the app.

## Everyday commands

Everything goes through `./bin/docker-exec`:

```bash
./bin/docker-exec artisan <cmd>     # php artisan
./bin/docker-exec composer <cmd>    # composer
./bin/docker-exec npm <cmd>         # npm (node container)
./bin/docker-exec test              # php artisan test
./bin/docker-exec pint              # code style
./bin/docker-exec phpstan           # static analysis (level 6)
./bin/docker-exec mysql             # mysql shell
./bin/docker-exec mfs               # migrate:fresh --seed
./bin/docker-exec bash              # shell in the php container
```

Frontend type-checking:

```bash
./bin/docker-exec npm run type-check
```

## Frontend conventions

- Pages live in `laravel/resources/js/Pages` and are resolved by name from
  `Inertia::render('Welcome')`.
- `@/` is aliased to `laravel/resources/js`.
- Shared props (`appName`, `auth.user`, `flash`) are defined in
  `app/Http/Middleware/HandleInertiaRequests.php` and typed in
  `resources/js/types/global.d.ts`.
- Ziggy is installed, so `route()` is available in Vue components.

## Deploying

The shared proxy must be running on the server first — see
`../proxy/README.md`. It gets certificates from Let's Encrypt automatically, so
a new domain needs only an A record and the `APP_URL_TRAEFIK` value.

```bash
cp .env.example .env       # then edit: see below
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
```

Before the first deploy, set in `.env`:

| Variable            | Value                                        |
| ------------------- | -------------------------------------------- |
| `APP_ENV`           | `production`                                 |
| `APP_DEBUG`         | `false`                                      |
| `APP_KEY`           | generate a fresh one — do not reuse the dev key |
| `APP_URL_TRAEFIK`   | the public hostname                          |
| `APP_URL`           | `https://<that hostname>`                    |
| `DB_PASSWORD`       | a real secret                                |
| `DB_ROOT_PASSWORD`  | a different real secret                      |
| `REDIS_PASSWORD`    | a real secret                                |

The production stack differs from dev in ways worth knowing: no source is
bind-mounted (code is baked into the image, so a deploy is rebuild + `up -d`),
MySQL and Redis publish no host ports, `storage/` is a named volume so uploads
survive rebuilds, and `queue`/`scheduler` reuse the app image rather than
rebuilding it.

### How the image is built

The `builder` stage installs dependencies, runs `npm run build`, then reinstalls
Composer packages with `--no-dev --optimize-autoloader`. The `production` stage
runs nginx and php-fpm under supervisor on port 80, with opcache timestamp
validation disabled and a `/up` healthcheck.

### Backups

Two volumes hold state that cannot be rebuilt: `littlesteps-web_dbdata` and
`littlesteps-web_storage`. The proxy's `letsencrypt` volume holds `acme.json` —
losing it forces certificate re-issue, which Let's Encrypt rate-limits.
