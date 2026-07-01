import Link from "next/link";
import {
  ArrowRight,
  ArrowUpRight,
  BadgeCheck,
  CircleAlert,
  Clock3,
  CreditCard,
  FileText,
  LoaderCircle,
  RefreshCw,
  ShieldCheck,
  Sparkles,
} from "lucide-react";
import BrandLogo from "@/components/brand/BrandLogo";
import InviteOnlyBanner from "@/components/marketing/InviteOnlyBanner";
import WaitlistSignupForm from "@/components/marketing/WaitlistSignupForm";
import { APP_VERSION_LABEL } from "@/lib/app-version";

export const dynamic = "force-dynamic";
export const revalidate = 0;

const heroChips = ["Invoices", "Reconciliation", "Ledger entries", "Payment follow-up"] as const;

const workflowCards = [
  {
    title: "Create invoice",
    description: "Draft client invoices with line items, due dates, and the context you need to send them cleanly.",
    icon: FileText,
    accent: "emerald",
  },
  {
    title: "Reconcile transaction",
    description: "Match incoming transfers to the right invoice and spot anything that needs a manual check.",
    icon: CreditCard,
    accent: "sky",
  },
  {
    title: "View ledger entry",
    description: "Inspect the entry, balance impact, and audit trail without jumping through several screens.",
    icon: BadgeCheck,
    accent: "slate",
  },
] as const;

const stateCards = [
  {
    title: "Bank feed loading",
    eyebrow: "Bank feed still syncing",
    body: "Skeleton rows keep the page readable while imported transactions resolve.",
    action: "Waiting on imported transactions",
    icon: LoaderCircle,
    tone: "emerald",
  },
  {
    title: "Empty queue",
    eyebrow: "No uncategorized items",
    body: "When there are no open matches, the page still points to the next finance action instead of going blank.",
    action: "Create invoice or import payments",
    icon: ShieldCheck,
    tone: "slate",
  },
  {
    title: "Partial failure",
    eyebrow: "One transfer needs review",
    body: "A missing counterparty name should be explained inline, not hidden behind a generic error.",
    action: "Review the mismatch and retry",
    icon: CircleAlert,
    tone: "amber",
  },
] as const;

const pricingPlans = [
  {
    name: "Starter",
    price: "₦10,000",
    period: "per month",
    description: "For independent operators who need clean invoices and a reliable cash view.",
    features: ["Create invoices faster", "Track paid and overdue balances", "Keep client work and money together"],
    featured: false,
  },
  {
    name: "Growth",
    price: "₦25,000",
    period: "per month",
    description: "For small teams that want one operating layer across finance and delivery.",
    features: ["Review ledger impact as work moves", "Reconcile transfers with less manual checking", "See issues before they turn into gaps"],
    featured: true,
  },
  {
    name: "Agency",
    price: "₦50,000",
    period: "per month",
    description: "For heavier client volume, more approvals, and more oversight.",
    features: ["Sharper oversight across accounts", "Rollouts with hands-on support", "Reporting that stays readable under load"],
    featured: false,
  },
] as const;

function SectionTitle({
  eyebrow,
  title,
  description,
  align = "left",
}: {
  eyebrow: string;
  title: string;
  description: string;
  align?: "left" | "center";
}) {
  return (
    <div className={align === "center" ? "mx-auto max-w-3xl text-center" : "max-w-3xl"}>
      <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-900">
        <Sparkles className="h-3.5 w-3.5" />
        {eyebrow}
      </div>
      <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance text-slate-950 sm:text-4xl">
        {title}
      </h2>
      <p className="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
        {description}
      </p>
    </div>
  );
}

export default function Home() {
  return (
    <main className="relative min-h-screen overflow-x-clip bg-white text-slate-950">
      <div className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.18),_transparent_42%),radial-gradient(circle_at_72%_24%,_rgba(56,189,248,0.12),_transparent_28%),linear-gradient(180deg,_rgba(240,253,244,0.95),_rgba(255,255,255,0.85)_58%,_rgba(255,255,255,0))]" />

      <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 pb-8 pt-5 sm:px-6 lg:px-8">
        <header className="rounded-full border border-slate-200 bg-white/95 px-4 py-3 shadow-[0_10px_30px_rgba(15,23,42,0.05)] backdrop-blur">
          <div className="flex items-center justify-between gap-3 sm:gap-4">
            <BrandLogo href="/" variant="color" className="h-8 w-auto" priority />

            <nav className="hidden items-center gap-8 text-sm text-slate-600 md:flex">
              <a href="#workflow" className="transition-colors hover:text-slate-950">
                Workflow
              </a>
              <a href="#states" className="transition-colors hover:text-slate-950">
                States
              </a>
              <a href="#pricing" className="transition-colors hover:text-slate-950">
                Pricing
              </a>
              <a href="#waitlist" className="transition-colors hover:text-slate-950">
                Access
              </a>
            </nav>

            <div className="flex items-center gap-2">
              <Link
                href="/login"
                className="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 sm:px-4 sm:py-2 sm:text-sm"
              >
                Sign in
              </Link>
              <Link
                href="#waitlist"
                className="rounded-full bg-slate-950 px-3 py-1.5 text-xs font-medium text-white transition-transform hover:-translate-y-0.5 sm:px-4 sm:py-2 sm:text-sm"
              >
                Request access
              </Link>
            </div>
          </div>
        </header>

        <InviteOnlyBanner />

        <section className="py-14 sm:py-20">
          <div className="grid gap-10 lg:grid-cols-[1.03fr_0.97fr] lg:items-center">
            <div>
              <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-900">
                <ShieldCheck className="h-3.5 w-3.5" />
                Built for live financial workflows
              </div>

              <h1 className="mt-6 max-w-3xl text-5xl font-semibold tracking-tight text-balance text-slate-950 sm:text-6xl lg:text-[4.8rem] lg:leading-[0.95]">
                Keep invoices, reconciliations, and ledger entries in one clean workspace.
              </h1>

              <p className="mt-6 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                BuildLedger keeps the money side of client work readable. Create an invoice, match a transfer, and inspect the ledger impact without moving through a cluttered stack of screens.
              </p>

              <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <Link
                  href="/invoices/create"
                  className="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white transition-transform hover:-translate-y-0.5 hover:bg-emerald-500"
                >
                  Create invoice
                  <ArrowRight className="h-4 w-4" />
                </Link>
                <Link
                  href="/payments/record"
                  className="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 transition-colors hover:border-slate-300 hover:bg-slate-50"
                >
                  Reconcile transaction
                  <ArrowUpRight className="h-4 w-4" />
                </Link>
                <a
                  href="#states"
                  className="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-950"
                >
                  Review states
                  <Clock3 className="h-4 w-4" />
                </a>
              </div>

              <div className="mt-8 flex flex-wrap gap-2">
                {heroChips.map((chip) => (
                  <span
                    key={chip}
                    className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm"
                  >
                    {chip}
                  </span>
                ))}
              </div>
            </div>

            <div className="relative">
              <div className="rounded-[2.5rem] border border-emerald-200 bg-[linear-gradient(180deg,rgba(16,185,129,0.14),rgba(255,255,255,0.92)_50%,rgba(255,255,255,1))] p-5 shadow-[0_22px_70px_rgba(15,23,42,0.08)]">
                <div className="rounded-[2rem] border border-white bg-white/95 p-5 shadow-[0_12px_35px_rgba(15,23,42,0.06)]">
                  <div className="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                      <p className="text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Live ledger snapshot</p>
                      <h2 className="mt-2 text-xl font-semibold text-slate-950">Today&apos;s workflow pulse</h2>
                    </div>
                    <div className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-900">
                      Online
                    </div>
                  </div>

                  <div className="mt-5 grid gap-4">
                    <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="text-xs uppercase tracking-[0.2em] text-slate-500">Revenue tracked</p>
                          <p className="mt-2 text-3xl font-semibold tabular-nums text-slate-950">₦12.4M</p>
                        </div>
                        <div className="rounded-2xl bg-emerald-600/10 px-3 py-2 text-sm font-medium text-emerald-700">
                          + 7.2% this month
                        </div>
                      </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                      <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4">
                        <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Invoices</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-slate-950">24</p>
                        <p className="mt-2 text-sm leading-6 text-slate-600">Awaiting payment</p>
                      </div>
                      <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4">
                        <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Reconciliations</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-slate-950">11</p>
                        <p className="mt-2 text-sm leading-6 text-slate-600">Imported today</p>
                      </div>
                      <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4">
                        <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Ledger entries</p>
                        <p className="mt-2 text-2xl font-semibold tabular-nums text-slate-950">9</p>
                        <p className="mt-2 text-sm leading-6 text-slate-600">Matched cleanly</p>
                      </div>
                    </div>

                    <div className="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Queue status</p>
                          <p className="mt-1 text-sm font-medium text-slate-950">Three entries still need a final review</p>
                        </div>
                        <button
                          type="button"
                          className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100"
                        >
                          Retry import
                          <RefreshCw className="h-4 w-4" />
                        </button>
                      </div>
                      <div className="mt-4 grid gap-2">
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                          INV-0142 · Madu &amp; Co · due tomorrow
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                          INV-0143 · Naira Thread · awaiting approval
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="workflow" className="border-t border-slate-200 py-16 sm:py-20">
          <SectionTitle
            eyebrow="Workflow"
            title="The page shows the real handoffs people use inside BuildLedger."
            description="This stays clean, but it still feels like a live product: the actions are financial, the states are realistic, and the visual language matches the rest of the app."
          />

          <div className="mt-10 grid gap-4 lg:grid-cols-3">
            {workflowCards.map((card) => {
              const Icon = card.icon;

              return (
                <article key={card.title} className="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                  <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <Icon className="h-5 w-5" />
                  </div>
                  <h3 className="mt-4 text-xl font-semibold text-slate-950">{card.title}</h3>
                  <p className="mt-2 text-sm leading-6 text-slate-600">{card.description}</p>
                  <div className="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-500">
                    Clean spacing. No nested dashboard blocks.
                  </div>
                </article>
              );
            })}
          </div>
        </section>

        <section id="states" className="border-t border-slate-200 py-16 sm:py-20">
          <SectionTitle
            eyebrow="Product states"
            align="center"
            title="This section shows what BuildLedger does when records are loading, missing, or partially incomplete."
            description="It is not decorative empty space. It shows the product's behavior when a bank sync is still in progress, a queue is empty, or one transaction needs a manual review."
          />

          <div className="mt-10 grid gap-4 lg:grid-cols-3">
            {stateCards.map((card) => {
              const Icon = card.icon;
              const toneClasses = {
                emerald: "border-emerald-200 bg-emerald-50 text-emerald-900",
                amber: "border-amber-200 bg-amber-50 text-amber-950",
                slate: "border-slate-200 bg-slate-50 text-slate-900",
              }[card.tone];

              return (
                <article key={card.title} className="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <p className="text-xs uppercase tracking-[0.18em] text-slate-500">{card.eyebrow}</p>
                      <h3 className="mt-2 text-xl font-semibold text-slate-950">{card.title}</h3>
                    </div>
                    <div className={`flex h-10 w-10 items-center justify-center rounded-2xl border ${toneClasses}`}>
                      <Icon className={`h-4 w-4 ${card.tone === "amber" ? "text-amber-700" : card.tone === "emerald" ? "text-emerald-700" : "text-slate-700"}`} />
                    </div>
                  </div>
                  <p className="mt-4 text-sm leading-6 text-slate-600">{card.body}</p>

                  <div className="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {card.action}
                  </div>

                  {card.title === "Bank feed loading" ? (
                    <div className="mt-5 space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                      <div className="h-3 w-3/4 rounded-full bg-slate-200" />
                      <div className="h-3 w-5/6 rounded-full bg-slate-200" />
                      <div className="h-3 w-2/3 rounded-full bg-slate-200" />
                    </div>
                  ) : null}
                </article>
              );
            })}
          </div>
        </section>

        <section id="pricing" className="border-t border-slate-200 py-16 sm:py-20">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <SectionTitle
              eyebrow="Pricing"
              title="Invite-only access starts with a trial, then moves into the right plan."
              description="The rollout stays controlled, but the pricing structure still maps to how teams actually grow: solo operators, small teams, and heavier agency use."
            />
            <Link
              href="#waitlist"
              className="inline-flex w-fit items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-900 transition-colors hover:bg-slate-50"
            >
              Request access
              <ArrowRight className="h-4 w-4" />
            </Link>
          </div>

          <div className="mt-10 grid gap-4 lg:grid-cols-3">
            {pricingPlans.map((plan) => (
              <article
                key={plan.name}
                className={[
                  "flex h-full flex-col rounded-[1.8rem] border p-6",
                  plan.featured
                    ? "border-emerald-200 bg-emerald-50/70 shadow-[0_18px_40px_rgba(16,185,129,0.08)]"
                    : "border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.04)]",
                ].join(" ")}
              >
                <div className="flex items-center gap-3">
                  <p className="text-xs uppercase tracking-[0.18em] text-slate-500">{plan.name}</p>
                  {plan.featured ? (
                    <div className="inline-flex w-fit rounded-full border border-emerald-200 bg-white px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-emerald-900">
                      Recommended
                    </div>
                  ) : null}
                </div>
                <div className="mt-3 flex items-end gap-2">
                  <span className="text-4xl font-semibold tracking-tight text-slate-950">{plan.price}</span>
                  <span className="pb-1 text-sm text-slate-500">{plan.period}</span>
                </div>
                <p className="mt-4 text-sm leading-6 text-slate-600">{plan.description}</p>

                <ul className="mt-6 grid gap-2">
                  {plan.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                      <BadgeCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                      <span>{feature}</span>
                    </li>
                  ))}
                </ul>

                <Link
                  href="#waitlist"
                  className={[
                    "mt-auto inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-medium transition-transform hover:-translate-y-0.5",
                    plan.featured
                      ? "bg-emerald-600 text-white hover:bg-emerald-500"
                      : "border border-slate-200 bg-white text-slate-900 hover:bg-slate-50",
                  ].join(" ")}
                >
                  Request access
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </article>
            ))}
          </div>
        </section>

        <WaitlistSignupForm />

        <footer className="border-t border-slate-200 py-8">
          <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <BrandLogo href="/" variant="color" className="h-6 w-auto" />
              <span className="text-xs uppercase tracking-[0.22em] text-slate-400">{APP_VERSION_LABEL}</span>
            </div>

            <nav className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
              <Link href="/privacy-policy" className="transition-colors hover:text-slate-950">
                Privacy Policy
              </Link>
              <Link href="/terms-of-use" className="transition-colors hover:text-slate-950">
                Terms of Use
              </Link>
              <Link href="/data-compliance" className="transition-colors hover:text-slate-950">
                Data &amp; Compliance
              </Link>
              <Link href="/ip-infringement" className="transition-colors hover:text-slate-950">
                IP Infringement
              </Link>
            </nav>
          </div>
        </footer>
      </div>
    </main>
  );
}
