"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import AppShell from "@/components/layout/AppShell";
import { DollarSign, Briefcase, FileText, Users, ArrowRight, Sparkles, Plus, RefreshCw } from "lucide-react";
import axiosInstance from "@/lib/axios";
import Link from "next/link";

interface DashboardMetrics {
  total_clients: number;
  active_projects: number;
  pending_invoices: number;
  total_revenue: number;
}

interface RecentActivity {
  type: string;
  title: string;
  subtitle: string;
  time: string;
  link: string;
}

const METRIC_CARDS = [
  {
    key: "total_revenue" as const,
    label: "Total Revenue",
    icon: DollarSign,
    sub: "Completed payments",
    format: (v: number) => `₦${v.toLocaleString("en-NG", { minimumFractionDigits: 2 })}`,
    color: "text-green-500",
    bg: "bg-green-500/10",
  },
  {
    key: "active_projects" as const,
    label: "Active Projects",
    icon: Briefcase,
    sub: "In progress",
    format: (v: number) => String(v),
    color: "text-blue-500",
    bg: "bg-blue-500/10",
  },
  {
    key: "pending_invoices" as const,
    label: "Pending Invoices",
    icon: FileText,
    sub: "Awaiting payment",
    format: (v: number) => String(v),
    color: "text-orange-500",
    bg: "bg-orange-500/10",
  },
  {
    key: "total_clients" as const,
    label: "Total Clients",
    icon: Users,
    sub: "Registered clients",
    format: (v: number) => String(v),
    color: "text-purple-500",
    bg: "bg-purple-500/10",
  },
];

const QUICK_ACTIONS = [
  { label: "New Proposal",  href: "/proposals/create",  color: "bg-emerald-600 text-white hover:bg-emerald-500" },
  { label: "New Project",   href: "/projects/create",   color: "bg-emerald-50 text-emerald-700 hover:bg-emerald-100" },
  { label: "Record Payment",href: "/payments/record",   color: "bg-emerald-50 text-emerald-700 hover:bg-emerald-100" },
  { label: "Add Client",    href: "/clients",            color: "bg-emerald-50 text-emerald-700 hover:bg-emerald-100" },
];

export default function DashboardPage() {
  const [metrics, setMetrics]   = useState<DashboardMetrics | null>(null);
  const [activity, setActivity] = useState<RecentActivity[]>([]);
  const [loading, setLoading]   = useState(true);

  useEffect(() => {
    const fetchAll = async () => {
      try {
        const [dashRes, invoiceRes, projectRes] = await Promise.all([
          axiosInstance.get("/dashboard"),
          axiosInstance.get("/invoices?page=1"),
          axiosInstance.get("/projects?page=1"),
        ]);

        setMetrics(dashRes.data.metrics);

        // Build recent activity from latest invoices + projects
        const recentInvoices: RecentActivity[] = (invoiceRes.data.data ?? [])
          .slice(0, 3)
          .map((inv: { id: number; invoice_number: string; status: string; client?: { name: string }; created_at: string }) => ({
            type:     "invoice",
            title:    inv.invoice_number,
            subtitle: `${inv.client?.name ?? "—"} · ${inv.status}`,
            time:     inv.created_at,
            link:     `/invoices/${inv.id}`,
          }));

        const recentProjects: RecentActivity[] = (projectRes.data.data ?? [])
          .slice(0, 2)
          .map((p: { id: number; title: string; status: string; client?: { name: string }; created_at: string }) => ({
            type:     "project",
            title:    p.title,
            subtitle: `${p.client?.name ?? "—"} · ${p.status}`,
            time:     p.created_at,
            link:     `/projects/${p.id}`,
          }));

        setActivity([...recentInvoices, ...recentProjects].sort(
          (a, b) => new Date(b.time).getTime() - new Date(a.time).getTime()
        ).slice(0, 5));
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchAll();
  }, []);

  return (
    <AppShell>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
              <Sparkles className="h-3.5 w-3.5" />
              Dashboard
            </div>
            <h1 className="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
              Keep invoices, projects, and payments moving in one place.
            </h1>
            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
              Here&apos;s what&apos;s happening with your business today.
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link
              href="/invoices/create"
              className="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition-transform hover:-translate-y-0.5 hover:bg-emerald-500"
            >
              <Plus className="h-4 w-4" />
              Create invoice
            </Link>
            <Link
              href="/payments/record"
              className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-4 py-2.5 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-50"
            >
              Reconcile transaction
            </Link>
          </div>
        </div>

        {/* Metric cards */}
        <div className="grid gap-4 grid-cols-2 xl:grid-cols-4">
          {METRIC_CARDS.map((card) => {
            const Icon = card.icon;
            return (
              <Card key={card.key} className="rounded-[1.5rem] border-slate-200 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-slate-500">
                    {card.label}
                  </CardTitle>
                  <div className={`flex h-9 w-9 items-center justify-center rounded-2xl ${card.bg}`}>
                    <Icon className={`h-4 w-4 ${card.color}`} />
                  </div>
                </CardHeader>
                <CardContent>
                  {loading ? (
                    <Skeleton className="h-7 w-24 mt-1" />
                  ) : (
                    <div className="text-2xl font-semibold tracking-tight text-slate-950">
                      {card.format(metrics?.[card.key] ?? 0)}
                    </div>
                  )}
                  <p className="mt-1 text-xs text-slate-500">{card.sub}</p>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Quick actions */}
        <div className="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                Quick actions
              </h2>
              <p className="mt-1 text-sm text-slate-600">The actions that matter most are always one click away.</p>
            </div>
            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 transition-colors hover:bg-emerald-100"
            >
              <RefreshCw className="h-4 w-4" />
              Refresh
            </button>
          </div>
          <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            {QUICK_ACTIONS.map((action) => (
              <Link
                key={action.label}
                href={action.href}
                className={`flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium transition-colors ${action.color}`}
              >
                {action.label}
              </Link>
            ))}
          </div>
        </div>

        {/* Recent activity */}
        <div className="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                Recent activity
              </h2>
              <p className="mt-1 text-sm text-slate-600">Latest invoices and projects stay visible here.</p>
            </div>
            <Link href="/invoices" className="inline-flex items-center gap-1 text-sm font-medium text-slate-950 hover:underline">
              View all <ArrowRight className="h-3.5 w-3.5" />
            </Link>
          </div>

          {loading ? (
            <div className="space-y-2">
              {[...Array(4)].map((_, i) => (
                <Skeleton key={i} className="h-14 w-full" />
              ))}
            </div>
          ) : activity.length === 0 ? (
            <div className="border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500 rounded-2xl bg-slate-50">
              No recent activity yet. Create a proposal or project to get started.
            </div>
          ) : (
            <div className="space-y-2">
              {activity.map((item, i) => (
                <Link
                  key={i}
                  href={item.link}
                  className="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 transition-colors hover:bg-slate-100/80"
                >
                  <div className="flex items-center gap-3">
                    <div className={`h-2.5 w-2.5 rounded-full shrink-0 ${item.type === "invoice" ? "bg-amber-500" : "bg-sky-500"}`} />
                    <div>
                      <p className="text-sm font-medium text-slate-950">{item.title}</p>
                      <p className="text-xs text-slate-500">{item.subtitle}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-500">
                      {new Date(item.time).toLocaleDateString()}
                    </span>
                    <ArrowRight className="h-3.5 w-3.5 text-slate-400 opacity-0 transition-opacity group-hover:opacity-100" />
                  </div>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppShell>
  );
}
