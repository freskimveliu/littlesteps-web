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
├── Dockerfile              # multi-stage: base → development / builder → production
├── docker-compose.yml      # dev stack
├── .env                    # single source of truth (laravel/.env symlinks to it)
├── bin/docker-exec         # command proxy into the containers
├── certs/                  # mkcert TLS cert for the dev domain
├── docker/
│   ├── nginx/              # dev webserver image + vhost
│   ├── traefik/            # static + dynamic Traefik config
│   └── mysql/init/         # creates the `testing` database
└── laravel/                # the application
```

## First-time setup

1. Point the dev domain at localhost:

   ```bash
   echo "127.0.0.1 littlesteps.dev.freskimveliu.dev" | sudo tee -a /etc/hosts
   ```

2. Copy the env file and generate a key (already done on this checkout):

   ```bash
   cp .env.example .env
   ./bin/docker-exec artisan key:generate
   ```

3. Generate the local TLS certificate (already done on this checkout):

   ```bash
   mkcert -cert-file certs/local-cert.pem -key-file certs/local-key.pem littlesteps.dev.freskimveliu.dev
   ```

4. Start everything and migrate:

   ```bash
   docker compose up -d
   ./bin/docker-exec artisan migrate
   ```

The app is at **https://littlesteps.dev.freskimveliu.dev:8443**.

> Ports 80/443 are used by another local stack, so Traefik listens on
> `TRAEFIK_HTTP_PORT=8090` and `TRAEFIK_HTTPS_PORT=8443`. If those ports are
> free on your machine, set both back to 80/443 in `.env` and drop the `:8443`
> from `APP_URL` and `VITE_HMR_CLIENT_PORT`.

## Services

| Container                | Role                                                    |
| ------------------------ | ------------------------------------------------------- |
| `littlesteps_traefik`    | TLS termination and routing (`:8090` / `:8443`)         |
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

## Production image

```bash
docker build --target production -t littlesteps-prod .
```

The `builder` stage installs dependencies, runs `npm run build`, then reinstalls
Composer packages with `--no-dev --optimize-autoloader`. The `production` stage
runs nginx and php-fpm under supervisor on port 80, with opcache timestamp
validation disabled and a `/up` healthcheck.
