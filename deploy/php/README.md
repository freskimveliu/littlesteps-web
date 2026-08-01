# PHP overrides for the shared server

Two files, bind-mounted read-only into the containers by
`deploy/docker-compose.server.yml`. They exist because the production image is
built for a machine of its own, and the vault box is not that.

| File             | Mounted at                                    | Why |
| ---------------- | --------------------------------------------- | --- |
| `zz-server.ini`  | `/usr/local/etc/php/conf.d/zz-server.ini`     | Drops `memory_limit` from the image's 1024M to 384M |
| `zz-server.conf` | `/usr/local/etc/php-fpm.d/zz-server.conf`     | Drops `pm.max_children` from 5 to 4, adds `pm.max_requests` |

The image ships `memory_limit = 1024M` and inherits php-fpm's default
`pm.max_children = 5`. On the vault box — 2 GB total, ~730 MB free with vault's
nine containers running, and no swap — that pairing lets a single runaway
request outgrow the whole machine. The host OOM killer then picks a victim by
score, and there is no guarantee it picks us rather than `vault_database`.

These are mounted rather than baked into the Dockerfile on purpose: they are
facts about *this server*, not about the application. The self-contained stack
in `docker-compose.production.yml` should keep the image's own defaults.

The `zz-` prefix matters. Both directories are included with a glob, last file
wins, and `zz-server` sorts after the `zz-docker.conf` that the official
`php:fpm` image ships. Redefining `[www]` in a second file is the same trick
that image uses.

## When to revisit

- `server reached pm.max_children` in the app container's logs means requests
  are queueing behind the worker limit. That is a signal to grow the box, not
  to raise the number — four workers already exceed the container's `mem_limit`
  if they all peak at once.
- `memory_limit` is set at 384M to leave room for in-request media conversions.
  Every Spatie conversion is `nonQueued()` today, so a 6000x4000 JPEG (~96 MB
  decoded, and it needs source and destination live at the same time) is
  handled inside a PHP-FPM worker. Once conversions move to the queue
  container, the web tier can go considerably lower.
