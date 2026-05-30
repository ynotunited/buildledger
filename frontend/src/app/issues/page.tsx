"use client";

import { FormEvent, useEffect, useTransition, useState } from "react";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";

type Issue = {
  id: number;
  title: string;
  description: string;
  status: "open" | "in_progress" | "resolved" | "closed";
  priority: "low" | "medium" | "high" | "urgent";
  category: "general" | "bug" | "billing" | "security" | "support";
  created_at: string;
};

export default function IssuesPage() {
  const [issues, setIssues] = useState<Issue[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [, startTransition] = useTransition();
  const [form, setForm] = useState({
    title: "",
    description: "",
    priority: "medium",
    category: "general",
  });

  const loadIssues = async () => {
    try {
      const response = await axiosInstance.get("/issues");
      setIssues(response.data.data ?? []);
    } catch (error) {
      console.error("Error loading issues", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    startTransition(() => {
      void loadIssues();
    });
  }, []);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await axiosInstance.post("/issues", form);
      setForm({ title: "", description: "", priority: "medium", category: "general" });
      await loadIssues();
    } catch (error) {
      console.error("Error creating issue", error);
    } finally {
      setSaving(false);
    }
  };

  const updateStatus = async (issue: Issue, status: Issue["status"]) => {
    try {
      await axiosInstance.put(`/issues/${issue.id}`, { status });
      await loadIssues();
    } catch (error) {
      console.error("Error updating issue", error);
    }
  };

  return (
    <AppShell>
      <div className="space-y-8">
        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <h1 className="text-3xl font-semibold tracking-tight">Issue tracking</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            Capture bugs, billing questions, and support requests without leaving the workspace.
          </p>
        </section>

        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <h2 className="text-xl font-semibold">Report an issue</h2>
          <form className="mt-6 grid gap-4" onSubmit={(event) => void submit(event)}>
            <input
              value={form.title}
              onChange={(event) => setForm({ ...form, title: event.target.value })}
              placeholder="Short issue summary"
              className="rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              required
            />
            <textarea
              value={form.description}
              onChange={(event) => setForm({ ...form, description: event.target.value })}
              placeholder="Describe what happened, what you expected, and any steps to reproduce."
              className="min-h-32 rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              required
            />
            <div className="grid gap-4 md:grid-cols-2">
              <select
                value={form.priority}
                onChange={(event) => setForm({ ...form, priority: event.target.value as Issue["priority"] })}
                className="rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              >
                <option value="low">Low priority</option>
                <option value="medium">Medium priority</option>
                <option value="high">High priority</option>
                <option value="urgent">Urgent priority</option>
              </select>
              <select
                value={form.category}
                onChange={(event) => setForm({ ...form, category: event.target.value as Issue["category"] })}
                className="rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              >
                <option value="general">General</option>
                <option value="bug">Bug</option>
                <option value="billing">Billing</option>
                <option value="security">Security</option>
                <option value="support">Support</option>
              </select>
            </div>
            <button
              type="submit"
              disabled={saving}
              className="inline-flex w-fit items-center justify-center rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground disabled:opacity-60"
            >
              {saving ? "Submitting..." : "Submit issue"}
            </button>
          </form>
        </section>

        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <h2 className="text-xl font-semibold">Open issues</h2>
          {loading ? (
            <p className="mt-4 text-sm text-muted-foreground">Loading issues...</p>
          ) : issues.length === 0 ? (
            <p className="mt-4 text-sm text-muted-foreground">No issues yet.</p>
          ) : (
            <div className="mt-6 space-y-4">
              {issues.map((issue) => (
                <div key={issue.id} className="rounded-2xl border border-border bg-background/70 p-4">
                  <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                      <h3 className="text-base font-semibold">{issue.title}</h3>
                      <p className="mt-2 text-sm text-muted-foreground">{issue.description}</p>
                      <p className="mt-3 text-xs uppercase tracking-[0.2em] text-primary/70">
                        {issue.category} · {issue.priority} · {issue.status}
                      </p>
                    </div>
                    <div className="flex gap-2">
                      {issue.status !== "resolved" && (
                        <button
                          onClick={() => void updateStatus(issue, "resolved")}
                          className="rounded-xl border border-border px-3 py-2 text-xs font-medium"
                        >
                          Mark resolved
                        </button>
                      )}
                      {issue.status !== "closed" && (
                        <button
                          onClick={() => void updateStatus(issue, "closed")}
                          className="rounded-xl border border-border px-3 py-2 text-xs font-medium"
                        >
                          Close
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </AppShell>
  );
}
