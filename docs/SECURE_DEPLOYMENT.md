# Secure Deployment Checklist

This project is ready to run securely only when the hosting environment is configured correctly too. Use this checklist before exposing the app to the internet.

If you are still testing locally, keep your current local `.env` unchanged and use the separate production templates in `backend/production.env.example` and `frontend/production.env.example` when preparing deployment.

## 1. Enforce HTTPS everywhere

- Set `APP_URL` to your real HTTPS API origin, for example `https://api.example.com`.
- Set `FRONTEND_URL` to your real HTTPS frontend origin, for example `https://app.example.com`.
- Set `SECURITY_ENFORCE_HTTPS=true`.
- Keep `SESSION_SECURE_COOKIE=true`.
- Terminate TLS at your load balancer, reverse proxy, or platform edge.

## 2. Store secrets outside the repo

- Do not commit `.env`.
- Load secrets from your hosting provider's secret manager or environment variable settings.
- Generate a strong `APP_KEY`.
- Store payment keys, mail credentials, Google OAuth credentials, and database passwords as deployment secrets.
- If you enforce an API gateway secret, store `API_GATEWAY_SHARED_SECRET` in the gateway and app secret store only.
- For encrypted env file workflows, Laravel supports `php artisan env:encrypt`.

## 3. Keep the database off the public internet

- Never publish MySQL/PostgreSQL ports directly to the internet.
- Bind the database to `127.0.0.1` or a private network interface only.
- Allow inbound DB access only from the application server or private VPC/subnet.
- Use firewall or security-group rules to block public access to ports like `3306` and `5432`.
- Use a separate database user with only the permissions the app needs.

## 4. Disable production debug leakage

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Use `LOG_STACK=daily,auth,api,security,analytics,support` or your provider's managed log drain.

## 5. Use Redis for distributed throttling

- Set `CACHE_STORE=redis` in production.
- Set `SESSION_DRIVER=redis` and `QUEUE_CONNECTION=redis` so sessions, queue workers, and throttles all share the same distributed store.
- Configure `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, and related connection settings.
- Keep at least one Redis instance reachable from all app replicas so rate limiting works across servers.
- If you do not use Redis in production, the app will log a security warning because distributed throttling is degraded.
- The production backend container now includes the Redis PHP extension, so the image is ready for `phpredis`.

## 6. Review reverse proxy and API gateway settings

- Keep `SECURITY_TRUSTED_PROXIES=*` only if your platform correctly injects trusted forwarding headers.
- If your platform provides fixed proxy IPs, prefer listing those exact proxies instead of `*`.
- If you want to require an upstream gateway, set `SECURITY_API_GATEWAY_ENFORCED=true`.
- Optionally set `API_GATEWAY_SHARED_SECRET` and configure the gateway to send `X-Gateway-Token`.
- Forward `X-Request-Id` and `X-Forwarded-Proto=https` from the gateway.
- A production example stack now lives in `docker-compose.production.yml` with an Nginx edge config at `ops/nginx/default.conf.template`.

## 7. Monitor suspicious activity

The app now writes:

- `storage/logs/auth-*.log` for login, logout, verification, reset, and OAuth events
- `storage/logs/api-*.log` for server-side API errors
- `storage/logs/security-*.log` for suspicious scans and high request volume
- `storage/logs/analytics-*.log` for telemetry events
- `storage/logs/support-*.log` for user-reported issues

You should ship these logs to a managed logging platform and alert on repeated login failures, scan traffic, and 5xx spikes.

## 8. Run readiness checks before cutover

- Use `php artisan security:deployment-check` inside the backend container or release job before opening traffic.
- Probe `GET /readyz` from your load balancer or container orchestrator.
- The readiness check validates database connectivity, Redis availability, secure session cookies, HTTPS URLs, gateway configuration, and required log channels.
