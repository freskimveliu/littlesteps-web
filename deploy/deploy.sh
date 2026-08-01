#!/usr/bin/env bash
#
# Little Steps — deploy on the shared vault server.
#
# Copied to /opt/littlesteps/deploy.sh. Run it there, or let the GitHub Actions
# workflow run it over SSH:
#
#   /opt/littlesteps/deploy.sh [image-tag]      # defaults to "main"
#
# The image is built by CI and pulled from GHCR — it is never built here. A
# t3.small with ~730 MB free cannot run `npm run build` without the OOM killer
# getting involved.
set -euo pipefail

APP_DIR=/opt/littlesteps
export DOCKER_IMAGE_TAG="${1:-main}"

cd "$APP_DIR"

echo "==> Deploying littlesteps-web:${DOCKER_IMAGE_TAG}"

# 1. Pull the new image. Done before anything is stopped, so a registry failure
#    leaves the running app untouched.
echo "==> Pulling image"
docker compose pull app

# 2. Migrate with the NEW image, before the running containers are replaced.
#    --no-deps so this does not drag the whole stack up, --rm so the throwaway
#    container does not linger.
echo "==> Running migrations"
docker compose run --rm --no-deps app php artisan migrate --force

# 3. Recreate the three containers on the new image.
echo "==> Recreating containers"
docker compose up -d --remove-orphans

# 4. Wait for the app's healthcheck rather than guessing with sleep.
echo "==> Waiting for the app to report healthy"
for i in $(seq 1 30); do
    status=$(docker inspect -f '{{.State.Health.Status}}' littlesteps_app 2>/dev/null || echo "starting")
    if [ "$status" = "healthy" ]; then
        echo "==> Healthy after ${i} attempt(s)"
        break
    fi
    if [ "$i" = "30" ]; then
        echo "!!! Never became healthy (last status: ${status})"
        docker compose logs app --tail=50
        exit 1
    fi
    sleep 5
done

# 5. Reclaim disk. The box has ~8 GB free and every deploy leaves an old image
#    behind, so this is not optional housekeeping.
echo "==> Pruning old images"
docker image prune -f

echo "==> Done"
docker compose ps
