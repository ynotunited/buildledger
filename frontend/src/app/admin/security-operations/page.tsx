"use client";

import Link from "next/link";
import AppShell from "@/components/layout/AppShell";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft, KeyRound, RotateCcw, ShieldAlert, ShieldCheck, TriangleAlert } from "lucide-react";

const SECRET_ITEMS = [
  "APP_KEY",
  "Database credentials",
  "Google OAuth client secret",
  "Paystack / Flutterwave keys",
  "SMTP credentials",
  "Sentry / PostHog / Better Stack tokens",
  "Webhook signing secrets",
];

const ROTATION_STEPS = [
  "Confirm which secret is affected and where it is used.",
  "Revoke or disable the exposed secret with the vendor first.",
  "Create a replacement secret and update the VPS environment.",
  "Clear config caches and restart the affected service.",
  "Smoke test login, email, payments, and webhooks.",
  "Revoke the old secret after the new one is confirmed working.",
  "Record the rotation date and incident notes.",
];

const RECOVERY_STEPS = [
  "Run the health scan and confirm the scope of the issue.",
  "Take a fresh encrypted backup before any manual repair.",
  "Restore only if the live database is beyond repair.",
  "Keep backups on a rolling 30-day retention window.",
  "Remember that backups are database snapshots only, not file-storage copies.",
];

export default function SecurityOperationsPage() {
  return (
    <AppShell>
      <div className="space-y-8">
        <div className="space-y-3">
          <Link
            href="/admin"
            className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to admin
          </Link>
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-3xl font-bold tracking-tight">Security Operations</h1>
            <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-muted-foreground">
              Team only
            </span>
          </div>
          <p className="max-w-3xl text-sm text-muted-foreground">
            Keep rotation and recovery in one place. Use this page when a secret is exposed, a vendor token
            needs replacement, or the live system needs a controlled recovery step.
          </p>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-300">
                <KeyRound className="h-5 w-5" />
              </div>
              <CardTitle>Secrets to watch</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-muted-foreground">
              {SECRET_ITEMS.map((item) => (
                <div key={item} className="rounded-xl border border-border bg-secondary/20 px-3 py-2">
                  {item}
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-300">
                <RotateCcw className="h-5 w-5" />
              </div>
              <CardTitle>Rotation flow</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-muted-foreground">
              {ROTATION_STEPS.map((step, index) => (
                <div key={step} className="flex gap-3 rounded-xl border border-border bg-secondary/20 px-3 py-2">
                  <span className="font-medium text-foreground">{index + 1}</span>
                  <span>{step}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-300">
                <ShieldCheck className="h-5 w-5" />
              </div>
              <CardTitle>Recovery flow</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-muted-foreground">
              {RECOVERY_STEPS.map((step, index) => (
                <div key={step} className="flex gap-3 rounded-xl border border-border bg-secondary/20 px-3 py-2">
                  <span className="font-medium text-foreground">{index + 1}</span>
                  <span>{step}</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Incident checklist</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm text-muted-foreground">
              <div className="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4">
                <ShieldAlert className="mt-0.5 h-4 w-4 text-rose-300" />
                <p>Confirm the exposure, scope, and environment before changing anything.</p>
              </div>
              <div className="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4">
                <TriangleAlert className="mt-0.5 h-4 w-4 text-amber-300" />
                <p>Rotate the vendor secret first, then update the VPS, then clear caches and restart services.</p>
              </div>
              <div className="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4">
                <ShieldCheck className="mt-0.5 h-4 w-4 text-emerald-300" />
                <p>Verify the affected flow, document the rotation, and only then revoke the old secret.</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recovery commands</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm text-muted-foreground">
              <div className="rounded-xl border border-border bg-secondary/20 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-muted-foreground">Health scan</p>
                <code className="rounded-md bg-background px-2 py-1 text-xs text-foreground">php artisan ops:health-scan</code>
              </div>
              <div className="rounded-xl border border-border bg-secondary/20 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-muted-foreground">Backup</p>
                <code className="rounded-md bg-background px-2 py-1 text-xs text-foreground">php artisan ops:backup</code>
              </div>
              <div className="rounded-xl border border-border bg-secondary/20 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-muted-foreground">Restore</p>
                <code className="rounded-md bg-background px-2 py-1 text-xs text-foreground">
                  php artisan ops:restore-backup backups/buildledger-db-YYYYMMDD_HHMMSS.json.enc --force
                </code>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppShell>
  );
}
