# BuildLedger

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Next.js](https://img.shields.io/badge/Next.js-15-000000?logo=next.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-success)
![License](https://img.shields.io/badge/License-Private-red)

A modern multi-tenant SaaS platform that helps agencies, freelancers, and service businesses manage proposals, contracts, invoices, payments, projects, and client relationships from a single dashboard.
Built with **Laravel**, **Next.js**, **TypeScript**, **MySQL**, **Docker**, and **Nginx**.

## 📖 Overview

BuildLedger is an all-in-one business operating system designed to simplify how service businesses manage their entire client lifecycle.

From the first proposal to the final payment, the platform provides tools for client onboarding, contracts, invoicing, project management, payment tracking, subscription billing, and administrative operations within a secure, scalable architecture.

## 🚀 Key Highlights

- Multi-tenant SaaS architecture
- Secure authentication and role-based access control
- Proposal, contract, invoice, and payment workflows
- Subscription billing with monthly and annual plans
- Admin dashboard with operational tools
- Public invoice payment links and digital contract signing
- RESTful API powered by Laravel
- Modern frontend built with Next.js and TypeScript
- Production-ready deployment using Docker and Nginx
- Security-first design with audit logs and rate limiting

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
- `docs/OBSERVABILITY_SETUP.md`

## License

Internal project for MadeItCodes / BuildLedger.
