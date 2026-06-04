import Link from "next/link";
import { ArrowRight, BadgeCheck, Briefcase, CreditCard, FileText, Sparkles, Users } from "lucide-react";
import InviteOnlyBanner from "@/components/marketing/InviteOnlyBanner";
import WaitlistSignupForm from "@/components/marketing/WaitlistSignupForm";
import BrandLogo from "@/components/brand/BrandLogo";
import { APP_VERSION_LABEL } from "@/lib/app-version";

const FEATURES = [
  {
    title: "Clients",
    description: "Keep a live picture of who owes you money, what they approved, and what needs follow-up.",
    icon: Users,
  },
  {
    title: "Proposals",
    description: "Turn scope and pricing into polished approvals clients can say yes to without the usual back-and-forth.",
    icon: FileText,
  },
  {
    title: "Projects",
    description: "Track delivery, deadlines, and bottlenecks without losing sight of the money attached to the work.",
    icon: Briefcase,
  },
  {
    title: "Payments",
    description: "See paid, pending, and overdue money from one dashboard instead of chasing it across tabs.",
    icon: CreditCard,
  },
];

const WORKFLOW_STEPS = [
  {
    step: "01",
    title: "Proposal",
    description: "Turn scope into a clean approval step clients can sign off on faster.",
  },
  {
    step: "02",
    title: "Contract",
    description: "Lock expectations before work starts so clients and teams stay aligned.",
  },
  {
    step: "03",
    title: "Invoice",
    description: "Send polished invoices and payment links with fewer follow-up messages.",
  },
  {
    step: "04",
    title: "Payment",
    description: "Know what cleared, what is pending, and what still needs a nudge.",
  },
] as const;

const PRICING = [
  {
    name: "Starter",
    price: "₦10,000",
    period: "per month",
    annualPrice: "₦96,000",
    annualPeriod: "per year",
    eyebrow: "For independents",
    description: "A polished workspace for independent operators who want proposals, contracts, invoices, and follow-ups in one place.",
    highlights: ["A single operating layer for client work", "Faster approvals and payment follow-up", "A clearer path from proposal to cash"],
    note: "Save ₦24,000 annually with yearly billing.",
    cta: "Request access",
    href: "#waitlist",
    featured: false,
  },
  {
    name: "Growth",
    price: "₦25,000",
    period: "per month",
    annualPrice: "₦240,000",
    annualPeriod: "per year",
    eyebrow: "For growing teams",
    description: "Built for teams that want visibility into profitability, delivery, and revenue without slowing the work down.",
    highlights: ["See who owes you money and which projects actually pay", "Analytics and operational reporting", "Priority support and governance"],
    note: "Save ₦60,000 annually with yearly billing.",
    cta: "Request access",
    href: "#waitlist",
    featured: true,
  },
  {
    name: "Agency",
    price: "₦50,000",
    period: "per month",
    annualPrice: "₦480,000",
    annualPeriod: "per year",
    eyebrow: "For established studios",
    description: "Designed for larger teams that need tighter oversight, heavier client volume, and a more hands-on rollout.",
    highlights: ["Built for heavier client loads", "Sharper visibility across accounts and teams", "Concierge-style onboarding and support"],
    note: "Save ₦120,000 annually with yearly billing.",
    cta: "Request access",
    href: "#waitlist",
    featured: false,
  },
] as const;

export default function Home() {
  return (
    <main className="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.16),_transparent_32%),linear-gradient(180deg,_rgba(24,24,27,0.98),_rgba(9,9,11,1))] text-white">
      <div className="pointer-events-none absolute inset-0 overflow-hidden">
        <div className="landing-pan absolute -top-20 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-400/18 blur-3xl" />
        <div className="landing-orbit absolute left-[12%] top-40 h-28 w-28 rounded-full bg-cyan-400/10 blur-2xl" />
        <div className="landing-float absolute bottom-28 right-[10%] h-48 w-48 rounded-full bg-emerald-300/10 blur-3xl" />
        <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-300/40 to-transparent" />
      </div>

      <div className="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-8">
        <header className="landing-fade-up flex items-center justify-between gap-4" style={{ animationDelay: "0.05s" }}>
          <div className="landing-float min-w-0 shrink" style={{ animationDuration: "7s" }}>
            <BrandLogo href="/" variant="white" className="h-8 w-auto" priority />
            <p className="mt-1 text-xs text-zinc-400 sm:text-sm">From proposal to payment.</p>
          </div>
          <div className="flex shrink-0 items-center gap-2">
            <Link
              href="/login"
              className="rounded-full border border-white/10 px-3 py-1.5 text-xs font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/5 sm:px-4 sm:py-2 sm:text-sm"
            >
              Sign in
            </Link>
            <Link
              href="#waitlist"
              className="rounded-full bg-emerald-400 px-3 py-1.5 text-xs font-medium text-zinc-950 transition-transform hover:-translate-y-0.5 sm:px-4 sm:py-2 sm:text-sm"
            >
              <span className="sm:hidden">Request access</span>
              <span className="hidden sm:inline">Request access</span>
            </Link>
          </div>
        </header>
        <InviteOnlyBanner />

        <section className="flex flex-1 items-center py-16 pb-0">
          <div className="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
              <div className="landing-fade-up inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200" style={{ animationDelay: "0.12s" }}>
                Built for agencies, studios, and independent operators
              </div>
              <h1 className="landing-fade-up mt-6 max-w-3xl text-5xl font-semibold tracking-tight text-balance sm:text-6xl" style={{ animationDelay: "0.18s" }}>
                Run client operations from one calm, premium workspace.
              </h1>
              <p className="landing-fade-up mt-6 max-w-2xl text-lg leading-8 text-zinc-300" style={{ animationDelay: "0.26s" }}>
                BuildLedger keeps your proposals, contracts, invoices, projects, and payments in one connected flow so your team always knows what is next.
              </p>
              <div className="landing-fade-up mt-8 flex flex-col gap-3 sm:flex-row" style={{ animationDelay: "0.34s" }}>
                <Link
                  href="#waitlist"
                  className="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-medium text-zinc-950 transition-transform hover:-translate-y-0.5"
                >
                  Request access
                  <ArrowRight className="h-4 w-4" />
                </Link>
                <Link
                  href="#pricing"
                  className="inline-flex items-center justify-center rounded-2xl border border-white/10 px-5 py-3 text-sm font-medium text-white transition-colors hover:border-white/20 hover:bg-white/5"
                >
                  View pricing
                </Link>
              </div>

              <div className="landing-fade-up mt-10 grid max-w-2xl grid-cols-3 gap-4" style={{ animationDelay: "0.42s" }}>
                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                  <p className="text-2xl font-semibold">₦4.8M</p>
                  <p className="mt-1 text-xs uppercase tracking-[0.18em] text-zinc-400">Revenue tracked</p>
                </div>
                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                  <p className="text-2xl font-semibold">32</p>
                  <p className="mt-1 text-xs uppercase tracking-[0.18em] text-zinc-400">Active clients</p>
                </div>
                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                  <p className="text-2xl font-semibold">9d</p>
                  <p className="mt-1 text-xs uppercase tracking-[0.18em] text-zinc-400">Avg. close time</p>
                </div>
              </div>
            </div>

            <div className="landing-fade-up relative rounded-[2rem] border border-white/10 bg-white/5 p-5 shadow-2xl shadow-black/30 backdrop-blur" style={{ animationDelay: "0.24s" }}>
              <div className="mb-4 flex items-center justify-between rounded-[1.5rem] border border-white/8 bg-black/20 px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-zinc-200">Live business pulse</p>
                  <p className="text-xs text-zinc-500">Today&apos;s workflow across the stack</p>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="h-2.5 w-2.5 rounded-full bg-emerald-300 shadow-[0_0_12px_rgba(110,231,183,0.8)]" />
                  <span className="text-xs uppercase tracking-[0.16em] text-emerald-200">Live</span>
                </div>
              </div>

              <div className="mb-4 grid grid-cols-3 gap-3">
                <div className="landing-float rounded-2xl border border-white/8 bg-white/6 p-3" style={{ animationDuration: "5.5s" }}>
                  <p className="text-xs uppercase tracking-[0.16em] text-zinc-500">Invoices</p>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-white/8">
                    <div className="landing-pulse h-full w-[78%] rounded-full bg-emerald-300" />
                  </div>
                </div>
                <div className="landing-float rounded-2xl border border-white/8 bg-white/6 p-3" style={{ animationDuration: "6.4s", animationDelay: "0.6s" }}>
                  <p className="text-xs uppercase tracking-[0.16em] text-zinc-500">Projects</p>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-white/8">
                    <div className="landing-pulse h-full w-[66%] rounded-full bg-cyan-300" style={{ animationDelay: "0.4s" }} />
                  </div>
                </div>
                <div className="landing-float rounded-2xl border border-white/8 bg-white/6 p-3" style={{ animationDuration: "5.9s", animationDelay: "1s" }}>
                  <p className="text-xs uppercase tracking-[0.16em] text-zinc-500">Payments</p>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-white/8">
                    <div className="landing-pulse h-full w-[84%] rounded-full bg-white" style={{ animationDelay: "0.7s" }} />
                  </div>
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                {FEATURES.map((feature) => {
                  const Icon = feature.icon;
                  return (
                    <div
                      key={feature.title}
                      className="landing-fade-up rounded-3xl border border-white/8 bg-black/20 p-5 transition-transform duration-300 hover:-translate-y-1"
                      style={{ animationDelay: `${0.4 + FEATURES.indexOf(feature) * 0.08}s` }}
                    >
                      <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-400/12 text-emerald-300">
                        <Icon className="h-5 w-5" />
                      </div>
                      <h2 className="mt-4 text-lg font-medium">{feature.title}</h2>
                      <p className="mt-2 text-sm leading-6 text-zinc-400">{feature.description}</p>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </section>

        <section className="landing-fade-up mt-10 rounded-[2.25rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur" style={{ animationDelay: "0.44s" }}>
          <div className="flex flex-col gap-4 border-b border-white/10 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-2xl">
              <div className="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">
                <Sparkles className="h-3.5 w-3.5" />
                Workflow proof
              </div>
              <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                From proposal to payment, the flow stays visible.
              </h2>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-zinc-300">
                BuildLedger is designed to reduce client chaos, not just collect feature checkboxes. One client project can fund a full year of BuildLedger.
              </p>
            </div>
            <div className="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
              One client project can fund a full year of BuildLedger.
            </div>
          </div>

          <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {WORKFLOW_STEPS.map((step) => (
              <article key={step.step} className="rounded-[1.75rem] border border-white/10 bg-black/20 p-5">
                <div className="flex items-center justify-between">
                  <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold tracking-[0.22em] text-zinc-200">
                    {step.step}
                  </span>
                  <ArrowRight className="h-4 w-4 text-zinc-500" />
                </div>
                <h3 className="mt-6 text-xl font-semibold">{step.title}</h3>
                <p className="mt-2 text-sm leading-6 text-zinc-400">{step.description}</p>
              </article>
            ))}
          </div>
        </section>

        <section id="pricing" className="landing-fade-up mt-10 rounded-[2.25rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur" style={{ animationDelay: "0.46s" }}>
          <div className="flex flex-col gap-4 border-b border-white/10 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-2xl">
              <div className="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">
                <Sparkles className="h-3.5 w-3.5" />
                Pricing built around outcomes
              </div>
              <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                Approved invites still unlock a 30-day trial. After that, the ladder starts at Starter and scales to Agency.
              </h2>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-zinc-300">
                BuildLedger opens by invitation so we can onboard teams in batches. Once approved, you get a trial first, then choose the tier that fits the pace and complexity of your client work.
              </p>
              <div className="mt-5 rounded-3xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-zinc-200">
                One client project can fund a full year of BuildLedger.
              </div>
            </div>
            <Link
              href="#waitlist"
              className="inline-flex w-fit items-center gap-2 rounded-2xl border border-white/12 bg-white/8 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:border-white/20 hover:bg-white/12"
            >
              Request access
              <ArrowRight className="h-4 w-4" />
            </Link>
          </div>

          <div className="mt-6 grid gap-4 xl:grid-cols-3">
            {PRICING.map((plan) => (
              <article
                key={plan.name}
                className={[
                  "relative overflow-hidden rounded-[1.75rem] border p-6 md:p-7",
                  plan.featured
                    ? "border-emerald-400/20 bg-gradient-to-br from-emerald-400/12 via-white/6 to-transparent"
                    : "border-white/10 bg-black/20",
                ].join(" ")}
              >
                {plan.featured ? (
                  <div className="absolute right-4 top-4 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-emerald-200">
                    Recommended
                  </div>
                ) : null}

                <p className="text-xs font-medium uppercase tracking-[0.22em] text-zinc-500">{plan.eyebrow}</p>
                <h3 className="mt-3 text-2xl font-semibold">{plan.name}</h3>
                <p className="mt-2 max-w-xl text-sm leading-6 text-zinc-300">{plan.description}</p>

                <div className="mt-6 flex flex-wrap items-end gap-x-3 gap-y-2">
                  <span className="text-4xl font-semibold">{plan.price}</span>
                  <span className="pb-1 text-sm text-zinc-400">{plan.period}</span>
                  <span className="pb-1 text-sm text-zinc-500">or {plan.annualPrice} {plan.annualPeriod}</span>
                </div>

                <p className={["mt-2 text-xs", plan.featured ? "text-emerald-200/80" : "text-zinc-500"].join(" ")}>
                  {plan.note}
                </p>

                <ul className="mt-6 space-y-2">
                  {plan.highlights.map((item) => (
                    <li key={item} className="flex items-start gap-3 rounded-2xl border border-white/8 bg-white/6 px-4 py-3 text-sm text-zinc-200">
                      <BadgeCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                      <span>{item}</span>
                    </li>
                  ))}
                </ul>

                <Link
                  href={plan.href}
                  className={[
                    "mt-6 inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-medium transition-transform hover:-translate-y-0.5",
                    plan.featured
                      ? "bg-white text-zinc-950"
                      : "border border-white/10 bg-white/5 text-white hover:bg-white/10",
                  ].join(" ")}
                >
                  {plan.cta}
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </article>
            ))}
          </div>
        </section>

        <WaitlistSignupForm />

        {/* Footer */}
        <footer className="landing-fade-up mt-16 border-t border-white/8 py-8" style={{ animationDelay: "0.58s" }}>
          <div className="flex flex-col items-center gap-5 text-center">
            {/* Brand + copyright on one line */}
            <div className="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
              <BrandLogo href="/" variant="white" className="h-6 w-auto" />
              <span className="text-zinc-600">·</span>
              <span className="text-sm text-zinc-500">© {new Date().getFullYear()} Webxpress Technologies MadeIT</span>
              <span className="text-zinc-700">·</span>
              <span className="text-xs uppercase tracking-[0.22em] text-zinc-600">{APP_VERSION_LABEL}</span>
            </div>

            {/* Legal links — single row, wraps gracefully */}
            <nav className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
              <Link href="/privacy-policy"  className="text-xs text-zinc-600 transition-colors hover:text-zinc-400">Privacy Policy</Link>
              <Link href="/terms-of-use"    className="text-xs text-zinc-600 transition-colors hover:text-zinc-400">Terms of Use</Link>
              <Link href="/data-compliance" className="text-xs text-zinc-600 transition-colors hover:text-zinc-400">Data &amp; Compliance</Link>
              <Link href="/ip-infringement" className="text-xs text-zinc-600 transition-colors hover:text-zinc-400">IP Infringement</Link>
            </nav>

            <p className="text-xs text-zinc-700">Built for agencies &amp; freelancers.</p>
          </div>
        </footer>

      </div>
    </main>
  );
}
