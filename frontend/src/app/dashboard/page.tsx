"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import AppShell from "@/components/layout/AppShell";
import { DollarSign, Briefcase, FileText, Users, ArrowRight } from "lucide-react";
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
  { label: "New Proposal",  href: "/proposals/create",  color: "bg-blue-500/10 text-blue-500 hover:bg-blue-500/20" },
  { label: "New Project",   href: "/projects/create",   color: "bg-green-500/10 text-green-500 hover:bg-green-500/20" },
  { label: "Record Payment",href: "/payments/record",   color: "bg-orange-500/10 text-orange-500 hover:bg-orange-500/20" },
  { label: "Add Client",    href: "/clients",            color: "bg-purple-500/10 text-purple-500 hover:bg-purple-500/20" },
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
      <div className="space-y-8">
        {/* Header */}
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
          <p className="text-muted-foreground text-sm mt-1">
            Here&apos;s what&apos;s happening with your business today.
          </p>
        </div>

        {/* Metric cards */}
        <div className="grid gap-4 grid-cols-2 lg:grid-cols-4">
          {METRIC_CARDS.map((card) => {
            const Icon = card.icon;
            return (
              <Card key={card.key}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                    {card.label}
                  </CardTitle>
                  <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${card.bg}`}>
                    <Icon className={`w-4 h-4 ${card.color}`} />
                  </div>
                </CardHeader>
                <CardContent>
                  {loading ? (
                    <Skeleton className="h-7 w-24 mt-1" />
                  ) : (
                    <div className="text-2xl font-bold">
                      {card.format(metrics?.[card.key] ?? 0)}
                    </div>
                  )}
                  <p className="text-xs text-muted-foreground mt-1">{card.sub}</p>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Quick actions */}
        <div>
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
            Quick Actions
          </h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {QUICK_ACTIONS.map((action) => (
              <Link
                key={action.label}
                href={action.href}
                className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-medium transition-colors ${action.color}`}
              >
                {action.label}
              </Link>
            ))}
          </div>
        </div>

        {/* Recent activity */}
        <div>
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">
              Recent Activity
            </h2>
            <Link href="/invoices" className="text-xs text-primary hover:underline flex items-center gap-1">
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>

          {loading ? (
            <div className="space-y-2">
              {[...Array(4)].map((_, i) => (
                <Skeleton key={i} className="h-14 w-full" />
              ))}
            </div>
          ) : activity.length === 0 ? (
            <div className="text-center py-10 text-muted-foreground border rounded-xl border-dashed text-sm">
              No recent activity yet. Create a proposal or project to get started.
            </div>
          ) : (
            <div className="space-y-2">
              {activity.map((item, i) => (
                <Link
                  key={i}
                  href={item.link}
                  className="flex items-center justify-between p-3.5 rounded-xl border border-border bg-card hover:bg-secondary/30 transition-colors group"
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-2 h-2 rounded-full shrink-0 ${item.type === "invoice" ? "bg-orange-500" : "bg-blue-500"}`} />
                    <div>
                      <p className="text-sm font-medium">{item.title}</p>
                      <p className="text-xs text-muted-foreground">{item.subtitle}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-muted-foreground">
                      {new Date(item.time).toLocaleDateString()}
                    </span>
                    <ArrowRight className="w-3.5 h-3.5 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
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
