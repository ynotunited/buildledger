"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";
import { useAuth } from "@/components/auth/AuthProvider";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Activity, AlertTriangle, BadgeDollarSign, Building2, Clock3, DollarSign, Mail, Shield, Users, Zap } from "lucide-react";
import { AuthUser } from "@/lib/auth";

type HealthCheck = {
  status: "pass" | "warn" | "fail";
  message: string;
  context: Record<string, unknown>;
};

type AdminDashboardResponse = {
  metrics: {
    total_users: number;
    admin_users: number;
    owner_users: number;
    client_users: number;
    total_clients: number;
    active_clients: number;
    trial_accounts: number;
    expiring_trials_7d: number;
    active_subscriptions: number;
    due_renewals_7d: number;
    pending_checkouts: number;
    failed_checkouts: number;
    revenue_30d: number;
    open_issues: number;
    issues_7d: number;
    security_incidents_7d: number;
    errors_7d: number;
    analytics_events_30d: number;
    completed_payments_30d: number;
    waitlist_signups_total: number;
    waitlist_signups_30d: number;
    operational_events_7d: number;
    backup_events_7d: number;
    reconciliation_events_7d: number;
  };
  plan_breakdown: Array<{
    code: string;
    name: string;
    monthly_price_ngn: number;
    annual_price_ngn: number;
    active_subscriptions: number;
    cancelled_subscriptions: number;
    expired_subscriptions: number;
  }>;
  health: {
    status: "ok" | "warning" | "failed";
    checks: Record<string, HealthCheck>;
  };
  recent_users: Array<{
    id: number;
    name: string;
    email: string;
    role: string;
    trial_ends_at: string | null;
    created_at: string;
  }>;
  recent_clients: Array<{
    id: number;
    name: string;
    company: string | null;
    status: string;
    email: string | null;
    phone: string | null;
    owner_name: string | null;
    owner_email: string | null;
    owner_role: string | null;
    created_at: string;
  }>;
  recent_issues: Array<{
    id: number;
    title: string;
    status: string;
    priority: string;
    category: string;
    user_name: string | null;
    user_email: string | null;
    created_at: string;
  }>;
  recent_security_incidents: Array<{
    id: number;
    type: string;
    severity: string;
    path: string | null;
    method: string | null;
    identity_key: string | null;
    created_at: string;
  }>;
  recent_errors: Array<{
    id: number;
    message: string;
    level: string;
    source: string;
    path: string | null;
    created_at: string;
  }>;
  recent_payments: Array<{
    id: number;
    reference: string | null;
    amount: number;
    currency: string;
    status: string;
    gateway: string | null;
    user_name: string | null;
    invoice_number: string | null;
    invoice_total: number | null;
    created_at: string;
  }>;
  recent_waitlist_signups: Array<{
    id: number;
    name: string | null;
    email: string;
    source: string | null;
    status: string;
    approved_at: string | null;
    activated_at: string | null;
    rejected_at: string | null;
    approved_by_name: string | null;
    ip_address: string | null;
    created_at: string;
  }>;
  recent_subscriptions: Array<{
    id: number;
    status: string;
    billing_interval: string | null;
    gateway: string | null;
    current_period_ends_at: string | null;
    user_name: string | null;
    user_email: string | null;
    plan_name: string | null;
    plan_code: string | null;
  }>;
  recent_support_sessions: Array<{
    id: number;
    action: string;
    note: string | null;
    ip_address: string | null;
    user_agent: string | null;
    impersonator_name: string | null;
    impersonator_email: string | null;
    target_name: string | null;
    target_email: string | null;
    target_role: string | null;
    occurred_at: string;
  }>;
  recent_operational_events: Array<{
    id: number;
    category: string;
    severity: string;
    title: string;
    message: string | null;
    source: string | null;
    reference_type: string | null;
    reference_id: number | null;
    user_name: string | null;
    user_email: string | null;
    resolved_at: string | null;
    occurred_at: string;
  }>;
  invite_mode: {
    enabled: boolean;
    source: string;
    updated_at: string | null;
  };
};

const METRIC_CARDS = [
  {
    key: "revenue_30d" as const,
    label: "Revenue 30d",
    icon: DollarSign,
    format: (value: number) => `₦${value.toLocaleString("en-NG", { minimumFractionDigits: 2 })}`,
    tone: "text-green-500",
    bg: "bg-green-500/10",
  },
  {
    key: "active_subscriptions" as const,
    label: "Active Subs",
    icon: BadgeDollarSign,
    format: (value: number) => String(value),
    tone: "text-blue-500",
    bg: "bg-blue-500/10",
  },
  {
    key: "total_clients" as const,
    label: "Total Clients",
    icon: Building2,
    format: (value: number) => String(value),
    tone: "text-emerald-500",
    bg: "bg-emerald-500/10",
  },
  {
    key: "waitlist_signups_total" as const,
    label: "Waitlist Signups",
    icon: Mail,
    format: (value: number) => String(value),
    tone: "text-fuchsia-500",
    bg: "bg-fuchsia-500/10",
  },
  {
    key: "pending_checkouts" as const,
    label: "Pending Checkouts",
    icon: Clock3,
    format: (value: number) => String(value),
    tone: "text-orange-500",
    bg: "bg-orange-500/10",
  },
  {
    key: "open_issues" as const,
    label: "Open Issues",
    icon: AlertTriangle,
    format: (value: number) => String(value),
    tone: "text-rose-500",
    bg: "bg-rose-500/10",
  },
  {
    key: "security_incidents_7d" as const,
    label: "Security Incidents",
    icon: Shield,
    format: (value: number) => String(value),
    tone: "text-purple-500",
    bg: "bg-purple-500/10",
  },
  {
    key: "errors_7d" as const,
    label: "App Errors",
    icon: Activity,
    format: (value: number) => String(value),
    tone: "text-cyan-500",
    bg: "bg-cyan-500/10",
  },
  {
    key: "analytics_events_30d" as const,
    label: "Telemetry Events",
    icon: Zap,
    format: (value: number) => String(value),
    tone: "text-yellow-500",
    bg: "bg-yellow-500/10",
  },
  {
    key: "due_renewals_7d" as const,
    label: "Renewals Due",
    icon: Users,
    format: (value: number) => String(value),
    tone: "text-indigo-500",
    bg: "bg-indigo-500/10",
  },
  {
    key: "operational_events_7d" as const,
    label: "Operational Events",
    icon: Shield,
    format: (value: number) => String(value),
    tone: "text-slate-500",
    bg: "bg-slate-500/10",
  },
];

function formatDate(value: string | null | undefined): string {
  if (!value) {
    return "—";
  }

  return new Date(value).toLocaleString();
}

function statusClass(status: string): string {
  switch (status.toLowerCase()) {
    case "pass":
    case "ok":
    case "completed":
    case "active":
    case "success":
      return "bg-emerald-500/15 text-emerald-300 border-emerald-500/30";
    case "warn":
    case "warning":
    case "pending":
      return "bg-amber-500/15 text-amber-300 border-amber-500/30";
    case "approved":
      return "bg-sky-500/15 text-sky-300 border-sky-500/30";
    case "activated":
      return "bg-emerald-500/15 text-emerald-300 border-emerald-500/30";
    case "fail":
    case "failed":
    case "error":
    case "critical":
      return "bg-rose-500/15 text-rose-300 border-rose-500/30";
    default:
      return "bg-white/10 text-muted-foreground border-white/10";
  }
}

export default function AdminPage() {
  const router = useRouter();
  const { user, isLoading, setAuthenticatedUser } = useAuth();
  const [data, setData] = useState<AdminDashboardResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [impersonatingId, setImpersonatingId] = useState<number | null>(null);
  const [waitlistActionId, setWaitlistActionId] = useState<number | null>(null);
  const [inviteModeSaving, setInviteModeSaving] = useState(false);
  const [supportNote, setSupportNote] = useState("");

  const canAccess = user?.role === "admin";

  useEffect(() => {
    if (isLoading) {
      return;
    }

    if (!user) {
      router.replace("/login?next=/admin");
      return;
    }

    if (!canAccess) {
      router.replace("/dashboard");
    }
  }, [canAccess, isLoading, router, user]);

  const startImpersonation = async (targetUserId: number) => {
    setImpersonatingId(targetUserId);
    try {
      const response = await axiosInstance.post<{ message: string; user: AuthUser }>(`/admin/impersonate/${targetUserId}`, {
        note: supportNote,
      });

      if (response.data.user) {
        setAuthenticatedUser(response.data.user);
        setSupportNote("");
        router.replace("/dashboard");
      }
    } catch (error) {
      console.error("Failed to start impersonation", error);
    } finally {
      setImpersonatingId(null);
    }
  };

  const updateWaitlistSignup = (signupId: number, nextSignup: AdminDashboardResponse["recent_waitlist_signups"][number]) => {
    setData((current) => {
      if (!current) {
        return current;
      }

      return {
        ...current,
        recent_waitlist_signups: current.recent_waitlist_signups.map((signup) =>
          signup.id === signupId ? nextSignup : signup
        ),
      };
    });
  };

  const handleWaitlistAction = async (signupId: number, action: "approve" | "reject") => {
    setWaitlistActionId(signupId);
    try {
      const response = await axiosInstance.post<{ message: string; waitlist_signup: AdminDashboardResponse["recent_waitlist_signups"][number] }>(
        `/admin/waitlist/${signupId}/${action}`
      );

      if (response.data.waitlist_signup) {
        updateWaitlistSignup(signupId, response.data.waitlist_signup);
      }
    } catch (error) {
      console.error(`Failed to ${action} waitlist signup`, error);
    } finally {
      setWaitlistActionId(null);
    }
  };

  const handleInviteModeToggle = async (enabled: boolean) => {
    setInviteModeSaving(true);
    try {
      const response = await axiosInstance.post<{ message: string; invite_mode: AdminDashboardResponse["invite_mode"] }>("/admin/invite-mode", {
        enabled,
      });

      if (response.data.invite_mode) {
        setData((current) => current ? { ...current, invite_mode: response.data.invite_mode } : current);
      }
    } catch (error) {
      console.error("Failed to update invite mode", error);
    } finally {
      setInviteModeSaving(false);
    }
  };

  useEffect(() => {
    if (!canAccess) {
      return;
    }

    let mounted = true;

    const loadDashboard = async () => {
      setLoading(true);
      try {
        const response = await axiosInstance.get<AdminDashboardResponse>("/admin/dashboard");
        if (mounted) {
          setData(response.data);
        }
      } catch (error) {
        console.error("Failed to load admin dashboard", error);
        router.replace("/dashboard");
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadDashboard();

    return () => {
      mounted = false;
    };
  }, [canAccess, router]);

  const healthChecks = useMemo(() => {
    return data ? Object.entries(data.health.checks) : [];
  }, [data]);

  if (isLoading || !user || user.role !== "admin") {
    return (
      <AppShell>
        <div className="space-y-6">
          <Skeleton className="h-10 w-56" />
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {Array.from({ length: 8 }).map((_, index) => (
              <Skeleton key={index} className="h-28 rounded-2xl" />
            ))}
          </div>
        </div>
      </AppShell>
    );
  }

  return (
    <AppShell>
      <div className="space-y-8">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.35em] text-muted-foreground">Operator Console</p>
            <h1 className="mt-2 text-3xl font-bold tracking-tight">Admin Dashboard</h1>
            <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
              Monitor subscriptions, revenue, support load, security signals, and deployment health from one place.
            </p>
          </div>
          <div className="flex flex-col items-start gap-2">
            <div className={`inline-flex w-fit items-center gap-2 rounded-full border px-3 py-2 text-xs font-medium ${statusClass(data?.health.status ?? "warning")}`}>
              <span className="h-2 w-2 rounded-full bg-current" />
              System health: {data?.health.status ?? "loading"}
            </div>
            <div className={`inline-flex w-fit items-center gap-2 rounded-full border px-3 py-2 text-xs font-medium ${data?.invite_mode?.enabled ? "border-amber-500/30 bg-amber-500/10 text-amber-200" : "border-emerald-500/30 bg-emerald-500/10 text-emerald-200"}`}>
              <span className="h-2 w-2 rounded-full bg-current" />
              {data?.invite_mode?.enabled ? "Invite-only active" : "Open registration active"}
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                size="sm"
                variant="outline"
                className="rounded-full"
                disabled={inviteModeSaving || loading || !data || data.invite_mode.enabled === false}
                onClick={() => void handleInviteModeToggle(false)}
              >
                {inviteModeSaving && data?.invite_mode?.enabled ? "Updating..." : "Open registration"}
              </Button>
              <Button
                type="button"
                size="sm"
                variant="secondary"
                className="rounded-full"
                disabled={inviteModeSaving || loading || !data || data.invite_mode.enabled === true}
                onClick={() => void handleInviteModeToggle(true)}
              >
                {inviteModeSaving && !data?.invite_mode?.enabled ? "Updating..." : "Enable invite-only"}
              </Button>
            </div>
          </div>
        </div>

        <div className="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-4">
          {METRIC_CARDS.map((card) => {
            const Icon = card.icon;
            const value = data?.metrics?.[card.key] ?? 0;

            return (
              <Card key={card.key}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">{card.label}</CardTitle>
                  <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${card.bg}`}>
                    <Icon className={`h-4 w-4 ${card.tone}`} />
                  </div>
                </CardHeader>
                <CardContent>
                  {loading ? (
                    <Skeleton className="mt-1 h-8 w-24" />
                  ) : (
                    <div className="text-2xl font-bold">{card.format(value)}</div>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>

        <div className="grid items-start gap-4 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Health Checks</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-14 rounded-xl" />)
              ) : healthChecks.length > 0 ? (
                healthChecks.map(([name, check]) => (
                  <div key={name} className="flex items-start justify-between rounded-xl border border-border bg-secondary/20 p-4">
                    <div>
                      <p className="font-medium capitalize">{name.replace(/_/g, " ")}</p>
                      <p className="mt-1 text-sm text-muted-foreground">{check.message}</p>
                    </div>
                    <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(check.status)}`}>
                      {check.status}
                    </span>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No health data available yet.</p>
              )}
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Plan Breakdown</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {loading ? (
                  Array.from({ length: 3 }).map((_, index) => <Skeleton key={index} className="h-20 rounded-xl" />)
                ) : data?.plan_breakdown?.length ? (
                  data.plan_breakdown.map((plan) => (
                    <div key={plan.code} className="rounded-xl border border-border bg-secondary/20 p-4">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium">{plan.name}</p>
                          <p className="text-xs text-muted-foreground">{plan.code}</p>
                        </div>
                        <span className="text-xs text-muted-foreground">{plan.active_subscriptions} active</span>
                      </div>
                      <div className="mt-3 grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                        <span>Monthly: ₦{plan.monthly_price_ngn.toLocaleString()}</span>
                        <span>Annual: ₦{plan.annual_price_ngn.toLocaleString()}</span>
                        <span>Cancelled: {plan.cancelled_subscriptions}</span>
                        <span>Expired: {plan.expired_subscriptions}</span>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-muted-foreground">No active plans seeded yet.</p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Launch Access</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <p className="text-sm text-muted-foreground">
                  {data?.invite_mode?.enabled
                    ? "Invite-only mode is active. Approved waitlist leads can create accounts."
                    : "Open registration is active. Anyone can create an account again."}
                </p>
                <p className="text-xs text-muted-foreground">
                  Source: {data?.invite_mode?.source ?? "loading"} · Updated: {formatDate(data?.invite_mode?.updated_at)}
                </p>
                <Link
                  href="/admin/security-operations"
                  className="inline-flex items-center rounded-full border border-white/10 px-3 py-2 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/40 hover:text-foreground"
                >
                  Open Security Operations
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>

        <div className="grid items-start gap-4 xl:grid-cols-6">
          <Card className="xl:col-span-2">
            <CardHeader>
              <CardTitle>Recent Users</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="rounded-xl border border-border bg-secondary/10 p-3">
                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                  <div className="md:max-w-[10rem]">
                    <label htmlFor="support-note" className="text-[11px] font-medium uppercase tracking-[0.25em] text-muted-foreground">
                      Support note
                    </label>
                    <p className="mt-2 text-xs leading-6 text-muted-foreground">
                      Optional note saved to the support audit trail before impersonation.
                    </p>
                  </div>
                  <textarea
                    id="support-note"
                    rows={2}
                    className="min-h-[92px] w-full rounded-xl border border-border bg-background px-3 py-2 text-sm outline-none transition-colors placeholder:text-muted-foreground/70 focus:border-primary"
                    placeholder="Why are we viewing this account?"
                    value={supportNote}
                    onChange={(e) => setSupportNote(e.target.value)}
                  />
                </div>
              </div>
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_users?.length ? (
                data.recent_users.map((entry) => (
                  <div key={entry.id} className="flex items-start justify-between rounded-xl border border-border bg-secondary/20 p-4">
                    <div>
                      <p className="font-medium">{entry.name}</p>
                      <p className="text-xs text-muted-foreground">{entry.email}</p>
                      <p className="mt-1 text-xs text-muted-foreground">Signed up: {formatDate(entry.created_at)}</p>
                    </div>
                    <div className="text-right space-y-2">
                      <span className="rounded-full border border-white/10 px-2.5 py-1 text-xs capitalize text-muted-foreground">{entry.role.replace("_", " ")}</span>
                      <p className="mt-2 text-xs text-muted-foreground">Trial ends {formatDate(entry.trial_ends_at)}</p>
                      {entry.role === "owner" && (
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          className="h-8 w-full"
                          disabled={impersonatingId === entry.id}
                          onClick={() => void startImpersonation(entry.id)}
                        >
                          {impersonatingId === entry.id ? "Opening..." : "View as owner"}
                        </Button>
                      )}
                    </div>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No users yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="xl:col-span-1">
            <CardHeader>
              <CardTitle>Recent Clients</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_clients?.length ? (
                data.recent_clients.map((client) => (
                  <div key={client.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{client.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {client.company ?? "No company"} · {client.email ?? "No email"}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(client.status)}`}>
                        {client.status}
                      </span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      Owner: {client.owner_name ?? "Unknown"} {client.owner_email ? `(${client.owner_email})` : ""} · {formatDate(client.created_at)}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No clients found yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="xl:col-span-1">
            <CardHeader>
              <CardTitle>Recent Payments</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_payments?.length ? (
                data.recent_payments.map((payment) => (
                  <div key={payment.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{payment.reference ?? `Payment #${payment.id}`}</p>
                        <p className="text-xs text-muted-foreground">
                          {payment.user_name ?? "Unknown user"} · {payment.invoice_number ?? "No invoice"}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(payment.status)}`}>
                        {payment.status}
                      </span>
                    </div>
                    <div className="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                      <span>{payment.gateway ?? "Gateway"}</span>
                      <span>₦{payment.amount.toLocaleString("en-NG", { minimumFractionDigits: 2 })}</span>
                    </div>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No payment activity yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="xl:col-span-2">
            <CardHeader>
              <CardTitle>Waitlist Signups</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_waitlist_signups?.length ? (
                data.recent_waitlist_signups.map((signup) => (
                  <div key={signup.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{signup.name ?? "Unnamed lead"}</p>
                        <p className="text-xs text-muted-foreground">{signup.email}</p>
                      </div>
                      <div className="flex flex-col items-end gap-2">
                        <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(signup.status)}`}>
                          {signup.status}
                        </span>
                        <span className="rounded-full border border-white/10 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                          {signup.source ?? "homepage"}
                        </span>
                      </div>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {signup.ip_address ?? "Unknown IP"} · {formatDate(signup.created_at)}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {signup.approved_by_name ? `Approved by ${signup.approved_by_name}` : "Not yet approved"}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="h-8"
                        disabled={waitlistActionId === signup.id || signup.status === "activated"}
                        onClick={() => void handleWaitlistAction(signup.id, "approve")}
                      >
                        {waitlistActionId === signup.id
                          ? "Working..."
                          : signup.status === "activated"
                            ? "Activated"
                            : signup.status === "approved"
                              ? "Resend invite"
                              : "Approve"}
                      </Button>
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="h-8 text-rose-300 hover:bg-rose-500/10 hover:text-rose-200"
                        disabled={waitlistActionId === signup.id || signup.status === "rejected" || signup.status === "activated"}
                        onClick={() => void handleWaitlistAction(signup.id, "reject")}
                      >
                        Reject
                      </Button>
                    </div>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No waitlist signups yet.</p>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="grid items-start gap-4 lg:grid-cols-3">
          <Card className="self-start">
            <CardHeader>
              <CardTitle>Recent Support Sessions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_support_sessions?.length ? (
                data.recent_support_sessions.map((event) => (
                  <div key={event.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium capitalize">{event.action}</p>
                        <p className="text-xs text-muted-foreground">
                          {event.impersonator_name ?? "Unknown admin"} → {event.target_name ?? "Unknown user"}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(event.action)}`}>
                        {event.action}
                      </span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {event.note ?? "No note provided"} · {event.ip_address ?? "No IP"} · {formatDate(event.occurred_at)}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No support sessions logged yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="self-start">
            <CardHeader>
              <CardTitle>Recent Operations</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_operational_events?.length ? (
                data.recent_operational_events.map((event) => (
                  <div key={event.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{event.title}</p>
                        <p className="text-xs text-muted-foreground">
                          {event.category} · {event.source ?? "system"}{event.user_name ? ` · ${event.user_name}` : ""}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(event.severity)}`}>
                        {event.severity}
                      </span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {event.message ?? "No message"} · {formatDate(event.occurred_at)}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No operational events recorded yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="self-start">
            <CardHeader>
              <CardTitle>Recent Issues</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_issues?.length ? (
                data.recent_issues.map((issue) => (
                  <div key={issue.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{issue.title}</p>
                        <p className="text-xs text-muted-foreground">
                          {issue.user_name ?? "Unknown user"} · {issue.category}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(issue.status)}`}>
                        {issue.status}
                      </span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      Priority: {issue.priority} · {formatDate(issue.created_at)}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No issues reported yet.</p>
              )}
            </CardContent>
          </Card>

          <Card className="self-start">
            <CardHeader>
              <CardTitle>Security Signals</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {loading ? (
                Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
              ) : data?.recent_security_incidents?.length ? (
                data.recent_security_incidents.map((incident) => (
                  <div key={incident.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{incident.type}</p>
                        <p className="text-xs text-muted-foreground">
                          {incident.method ?? "N/A"} · {incident.path ?? "Unknown path"}
                        </p>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(incident.severity)}`}>
                        {incident.severity}
                      </span>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      Identity: {incident.identity_key ?? "—"} · {formatDate(incident.created_at)}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">No unusual traffic patterns recorded yet.</p>
              )}
            </CardContent>
          </Card>
        </div>

          <Card className="self-start">
            <CardHeader>
              <CardTitle>Recent Errors (7d)</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
            {loading ? (
              Array.from({ length: 5 }).map((_, index) => <Skeleton key={index} className="h-16 rounded-xl" />)
            ) : data?.recent_errors?.length ? (
              data.recent_errors.map((error) => (
                <div key={error.id} className="rounded-xl border border-border bg-secondary/20 p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="font-medium">{error.message}</p>
                      <p className="text-xs text-muted-foreground">{error.source} · {error.path ?? "Unknown path"}</p>
                    </div>
                    <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusClass(error.level)}`}>
                      {error.level}
                    </span>
                  </div>
                  <p className="mt-2 text-xs text-muted-foreground">{formatDate(error.created_at)}</p>
                </div>
              ))
            ) : (
              <p className="text-sm text-muted-foreground">No recent errors captured in the last 7 days.</p>
            )}
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
