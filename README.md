# BuildLedger

BuildLedger is a client-work operating system for agencies, freelancers, and digital teams. It combines proposals, contracts, invoices, payments, projects, billing, waitlist onboarding, and an admin console into one platform.

## What’s inside

- `backend/` - Laravel API, admin controls, billing, payments, notifications, and audit logging.
- `frontend/` - Next.js app for the customer dashboard, marketing site, and admin UI.
- `docs/` - Product, deployment, security, and operations notes.
- `ops/` - Nginx and deployment templates.

## Core features

- Invite-only onboarding and waitlist approvals
- Admin dashboard with support and operational tooling
- Clients, proposals, contracts, invoices, projects, and payments
- Public invoice payment links and public contract signing
- Subscription billing with monthly and annual plans
- Security controls for rate limiting, audit logs, and abuse detection

## Local development

Run the backend and frontend separately:

```bash
cd backend
php artisan serve
```

```bash
cd frontend
npm install
npm run dev
```

Local URLs:

- Frontend: `http://localhost:3000`
- Backend: `http://localhost:8000`

For a full step-by-step localhost walkthrough, see [docs/LOCALHOST_GUIDE.md](docs/LOCALHOST_GUIDE.md).

## Environment setup

Each app uses its own `.env` file.

- `backend/.env`
- `frontend/.env.local`

For production-ready defaults, see:

- `backend/production.env.example`
- `frontend/production.env.example`
- `docs/PRODUCTION_ENV_CHECKLIST.md`

## Testing

Backend:

```bash
cd backend
php artisan test
```

Frontend:

```bash
cd frontend
npm run lint
npm run build
```

## Deployment

The current deployment plan is:

- GitHub repo: `https://github.com/ynotunited/buildledger`
- Production deployment target: Hostinger VPS
- Domain: `madeitcodes.online`

If you use the VPS route, keep the app connected to `main` on GitHub and deploy from the server after pushing updates.

## Security and operations

BuildLedger includes:

- invite-only launch mode
- admin approval for waitlist signups
- payment idempotency and ledger tracking
- backup and reconciliation commands
- readiness checks and operational events

For details, see:

- `docs/SECURE_DEPLOYMENT.md`
- `docs/OPERATIONS_RUNBOOK.md`
- `docs/SECRETS_AUDIT.md`

## License

Internal project for MadeItCodes / BuildLedger.
