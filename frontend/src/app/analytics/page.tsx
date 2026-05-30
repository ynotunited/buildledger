"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";

type AnalyticsSummary = {
  current_plan: string | null;
  totals: {
    events_30d: number;
    page_views_30d: number;
    payment_events_30d: number;
  };
  top_events: Array<{ event_name: string; aggregate: number }>;
};

export default function AnalyticsPage() {
  const [summary, setSummary] = useState<AnalyticsSummary | null>(null);
  const [requiresUpgrade, setRequiresUpgrade] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const response = await axiosInstance.get("/analytics/summary");
        setSummary(response.data);
      } catch (error: unknown) {
        const status = (error as { response?: { status?: number } })?.response?.status;
        if (status === 402) {
          setRequiresUpgrade(true);
        } else {
          console.error("Error loading analytics", error);
        }
      } finally {
        setLoading(false);
      }
    };

    void load();
  }, []);

  return (
    <AppShell>
      <div className="space-y-8">
        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <h1 className="text-3xl font-semibold tracking-tight">Analytics</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            Track usage, payment signals, and operational activity across the last 30 days.
          </p>
        </section>

        {loading ? (
          <div className="rounded-[2rem] border border-white/10 bg-card p-6 text-sm text-muted-foreground">Loading analytics...</div>
        ) : requiresUpgrade ? (
          <div className="rounded-[2rem] border border-amber-500/20 bg-amber-500/10 p-6">
            <h2 className="text-xl font-semibold text-amber-100">Growth plan required</h2>
            <p className="mt-2 text-sm text-amber-100/80">
              Analytics is part of the Growth plan. Upgrade to unlock usage insights and operational reporting.
            </p>
            <Link
              href="/billing"
              className="mt-4 inline-flex rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground"
            >
              View billing plans
            </Link>
          </div>
        ) : summary ? (
          <>
            <section className="grid gap-4 md:grid-cols-3">
              <div className="rounded-[2rem] border border-white/10 bg-card p-6">
                <p className="text-sm text-muted-foreground">Events (30d)</p>
                <p className="mt-3 text-3xl font-semibold">{summary.totals.events_30d}</p>
              </div>
              <div className="rounded-[2rem] border border-white/10 bg-card p-6">
                <p className="text-sm text-muted-foreground">Page views (30d)</p>
                <p className="mt-3 text-3xl font-semibold">{summary.totals.page_views_30d}</p>
              </div>
              <div className="rounded-[2rem] border border-white/10 bg-card p-6">
                <p className="text-sm text-muted-foreground">Payment events (30d)</p>
                <p className="mt-3 text-3xl font-semibold">{summary.totals.payment_events_30d}</p>
              </div>
            </section>

            <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
              <h2 className="text-xl font-semibold">Top events</h2>
              <div className="mt-6 space-y-3">
                {summary.top_events.map((event) => (
                  <div key={event.event_name} className="flex items-center justify-between rounded-2xl border border-border bg-background/70 px-4 py-3">
                    <span className="text-sm font-medium">{event.event_name}</span>
                    <span className="text-sm text-muted-foreground">{event.aggregate}</span>
                  </div>
                ))}
              </div>
            </section>
          </>
        ) : (
          <div className="rounded-[2rem] border border-white/10 bg-card p-6 text-sm text-muted-foreground">
            Analytics is unavailable right now.
          </div>
        )}
      </div>
    </AppShell>
  );
}
