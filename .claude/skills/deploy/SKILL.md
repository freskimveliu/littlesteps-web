---
name: deploy
description: Shipping littlesteps-web — pushing to main deploys straight to production, what gates it, and how to verify afterwards. Use before pushing, when asked to deploy, or when checking whether something is live.
---

# Deploying

**Pushing to `main` deploys to production.** There is no staging: `.github/workflows/deploy.yml`
runs on every push to `main` and goes test → build image → deploy → health check, about 3–4 minutes.
`littlesteps.dev.freskimveliu.dev` does not exist (no DNS record); the only environment is
**`https://littlesteps.freskimveliu.dev`**.

So a push is an outward-facing act. Confirm with the user before pushing to `main` unless they have
just asked for it — and say plainly that it will be live in minutes.

## Before pushing

Run the suite (see the `testing` skill). It is the only gate — PHPStan and Pint are not, so a Pint
diff will not stop a deploy, and pre-existing Pint failures in `Limits.php`,
`ProvisionChild.php` and the catalogue migration are noise, not yours.

**If the payload changed, the app breaks the moment this lands.** The mobile app in
`~/Desktop/Projects/littlesteps` reads these keys directly. Deploy the server first, then the app —
and check with the user whether anyone has an older build installed, because there is no
compatibility window.

## Watching it

```bash
gh run list --limit 1
gh run watch <run-id> --exit-status
```

## Verifying it landed

Ask the server, don't assume. A guest token is one unauthenticated call:

```bash
TOK=$(curl -s -X POST https://littlesteps.freskimveliu.dev/api/v1/auth/guest \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Probe","timezone":"UTC"}' | python3 -c "import sys,json;print(json.load(sys.stdin)['data']['token'])")
```

Then exercise the thing you changed — creating a child needs `name`, `birthday`, `gender` and a valid
`relation` (`mother`, `father`, `brother`, `sister`, `grandparent`, `aunt-uncle`, `other`), and its
chapters come back at `/children/{id}/chapters`.

**Clean up after yourself**: `curl -X DELETE …/auth/me -H "Authorization: Bearer $TOK"` removes the
probe account and its child. Report that you made one and that you removed it. Note the token
contains a `|`, so always quote it in shell.

## The server itself

Three containers at `/opt/littlesteps` on the shared vault box, attached to `vault_network`, behind
that stack's Caddy. **2 GB RAM with ~730 MB free and no swap** — that constraint shapes the whole
setup; read `DEPLOYMENT.md` before changing anything about the topology. `migrate --force` runs on
every deploy, and the first admin creates itself through a migration rather than a seeder.
