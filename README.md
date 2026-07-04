# BuildLedger

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Next.js](https://img.shields.io/badge/Next.js-15-000000?logo=next.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-success)
![License](https://img.shields.io/badge/License-Private-red)

A production-grade multi-tenant SaaS platform for managing proposals, contracts, invoices, payments, and client workflows.

Built for agencies, freelancers, and service-based businesses that need a unified system to run their operations.
Built with Laravel, Next.js, TypeScript, MySQL, Docker, and Nginx. Designed with scalable SaaS architecture, secure authentication, and production deployment practices.

## 📖 Overview

BuildLedger is an all-in-one business operating system designed to simplify how service businesses manage their entire client lifecycle.

From the first proposal to the final payment, the platform provides tools for client onboarding, contracts, invoicing, project management, payment tracking, subscription billing, and administrative operations within a secure, scalable architecture.

## 💡 Why BuildLedger?

Freelancers, agencies, and service businesses often rely on multiple disconnected tools to manage proposals, contracts, invoices, payments, and projects.

BuildLedger was created to unify these workflows into a single platform that improves operational efficiency, reduces manual work, and provides better visibility across the entire client lifecycle.

## 🚀 Production Status

- Live SaaS system deployed on VPS
- Multi-tenant architecture implemented
- Authentication, roles, and permissions active
- Payment and invoice workflows functional
- Admin and client dashboards operational
- CI-style deployment workflow via GitHub + VPS

## 🧠 System Architecture

BuildLedger is designed using a modern decoupled SaaS architecture:

### Frontend Layer
- Next.js handles all UI rendering
- Role-based dashboards (Admin, Client, Operations)
- API-driven state management

### Backend Layer
- Laravel serves as the core API and business logic engine
- Handles authentication, authorization, billing, and workflows
- Implements RESTful API architecture

### Database Layer
- MySQL used for relational multi-tenant data modeling
- Strict separation of tenants via scoped queries and IDs

### Infrastructure Layer
- Docker used for containerized development and deployment
- Nginx used as reverse proxy and request routing layer
- Deployed on VPS for production hosting

---

## 🧠 Engineering Decisions

### Why Laravel + Next.js
Laravel was chosen for its strong backend ecosystem, mature authentication system, and rapid API development capabilities.  
Next.js was used to deliver a fast, scalable, and modern frontend experience with server-side rendering where needed.

---

### Why Multi-Tenancy
A multi-tenant architecture was implemented to allow multiple organizations to operate independently within the same system while maintaining strict data isolation and scalability.

---

### Why REST API Design
A RESTful API structure ensures:
- Clear separation between frontend and backend
- Easier scalability across multiple clients (web, mobile, integrations)
- Maintainable and testable backend services

---

## 🔄 System Flow (How BuildLedger Works)

BuildLedger follows a complete end-to-end business workflow system:

### 1. Client Onboarding
- A client is invited or signs up via waitlist approval
- Admin approves and assigns tenant workspace access
- Role-based access is initialized (Admin / Client / Staff)

---

### 2. Proposal Creation
- Admin or team creates a proposal for a client
- Proposal is sent for review and approval
- Client can accept or request modifications

---

## 💼 Business Impact

BuildLedger replaces the need for multiple disconnected tools by combining core business operations into a single platform.

Instead of using separate tools for:
- Proposals (Google Docs / Notion)
- Contracts (DocuSign alternatives)
- Invoicing (QuickBooks / Stripe Invoicing)
- Project tracking (Trello / Asana)
- Payment tracking (manual spreadsheets)

BuildLedger centralizes everything into one system.

---

## 🎯 Key Value Delivered

- Reduces operational overhead for service businesses
- Improves payment tracking and cash flow visibility
- Eliminates tool fragmentation across teams
- Automates client lifecycle workflows
- Provides a single source of truth for business operations

### 3. Contract Generation
- Once a proposal is accepted, a contract is generated
- Client digitally signs the contract via secure link
- Contract status is tracked in the system

---

### 4. Invoice & Billing
- Approved contracts trigger invoice creation
- Clients receive payment links for invoices
- System tracks payment status in real-time

---

### 5. Payment Processing
- Payments are processed via integrated payment gateway
- Transactions are recorded with idempotency checks
- Ledger system updates financial records automatically

---

### 6. Project Execution
- Once payment is confirmed, a project is created
- Tasks and milestones are tracked within the dashboard
- Progress is visible to both client and admin

---

### 7. Admin Operations
- Admin dashboard provides full system visibility
- Includes user management, billing overview, and audit logs
- System health and activity tracking included

### Why MySQL
MySQL was selected for its reliability, relational integrity, and strong support for structured business data such as invoices, contracts, and payments.

---

### Why Docker
Docker ensures:
- Consistent development and production environments
- Simplified deployment across servers
- Isolation of services for backend and frontend

---

### Deployment Strategy
The system is deployed on a VPS using Nginx as a reverse proxy, with Git-based updates pushed from the main branch to production.

This allows:
- Fast iteration
- Controlled deployments
- Simple rollback strategy if needed

## 🔐 Multi-Tenancy Model

BuildLedger uses a tenant-isolated architecture where:

- Each organization operates in a logically separated workspace
- Data access is scoped per tenant at the query level
- Role-based access control ensures secure data boundaries

---

## 🔄 Core Data Flow

Client Request → Next.js Frontend → Laravel API → Business Logic → MySQL → Response → UI Update

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

## 📂 Project Structure

- `backend/` - Laravel API, admin controls, billing, payments, notifications, and audit logging.
- `frontend/` - Next.js app for the customer dashboard, marketing site, and admin UI.
- `docs/` - Product, deployment, security, and operations notes.
- `ops/` - Nginx and deployment templates.

## ✨ Core Features

- Invite-only onboarding and waitlist approvals
- Admin dashboard with support and operational tooling
- Clients, proposals, contracts, invoices, projects, and payments
- Public invoice payment links and public contract signing
- Subscription billing with monthly and annual plans
- Security controls for rate limiting, audit logs, and abuse detection

## 💻 Local Development

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

## ⚙️ Environment Setup

Each app uses its own `.env` file.

- `backend/.env`
- `frontend/.env.local`

For production-ready defaults, see:

- `backend/production.env.example`
- `frontend/production.env.example`
- `docs/PRODUCTION_ENV_CHECKLIST.md`

## 🧪 Testing

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

## 🚀 Deployment

The current deployment plan is:

- GitHub repo: `https://github.com/ynotunited/buildledger`
- Production deployment target: Hostinger VPS
- Domain: `madeitcodes.online`

If you use the VPS route, keep the app connected to `main` on GitHub and deploy from the server after pushing updates.

## 🔒 Security & Operations

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

## 📄 License

Internal project for MadeItCodes / BuildLedger.
