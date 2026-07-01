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
            className="inline-flex items-center gap-2 text-sm text-slate-500 transition-colors hover:text-emerald-700"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to admin
          </Link>
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-3xl font-semibold tracking-tight text-slate-950">Security Operations</h1>
            <span className="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs uppercase tracking-[0.2em] text-emerald-700">
              Team only
            </span>
          </div>
          <p className="max-w-3xl text-sm text-slate-600">
            Keep rotation and recovery in one place. Use this page when a secret is exposed, a vendor token
            needs replacement, or the live system needs a controlled recovery step.
          </p>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                <KeyRound className="h-5 w-5" />
              </div>
              <CardTitle>Secrets to watch</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-slate-600">
              {SECRET_ITEMS.map((item) => (
                <div key={item} className="rounded-xl border border-emerald-100 bg-emerald-50/30 px-3 py-2">
                  {item}
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                <RotateCcw className="h-5 w-5" />
              </div>
              <CardTitle>Rotation flow</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-slate-600">
              {ROTATION_STEPS.map((step, index) => (
                <div key={step} className="flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50/30 px-3 py-2">
                  <span className="font-medium text-slate-950">{index + 1}</span>
                  <span>{step}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="space-y-2">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <ShieldCheck className="h-5 w-5" />
              </div>
              <CardTitle>Recovery flow</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm text-slate-600">
              {RECOVERY_STEPS.map((step, index) => (
                <div key={step} className="flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50/30 px-3 py-2">
                  <span className="font-medium text-slate-950">{index + 1}</span>
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
              <p className="text-slate-600">Confirm the exposure, scope, and environment before changing anything.</p>
            </div>
              <div className="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                <TriangleAlert className="mt-0.5 h-4 w-4 text-amber-600" />
                <p className="text-slate-600">Rotate the vendor secret first, then update the VPS, then clear caches and restart services.</p>
              </div>
              <div className="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                <ShieldCheck className="mt-0.5 h-4 w-4 text-emerald-700" />
                <p className="text-slate-600">Verify the affected flow, document the rotation, and only then revoke the old secret.</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recovery commands</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm text-slate-600">
              <div className="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-slate-500">Health scan</p>
                <code className="rounded-md bg-white px-2 py-1 text-xs text-slate-950">php artisan ops:health-scan</code>
              </div>
              <div className="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-slate-500">Backup</p>
                <code className="rounded-md bg-white px-2 py-1 text-xs text-slate-950">php artisan ops:backup</code>
              </div>
              <div className="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                <p className="mb-2 text-xs uppercase tracking-[0.25em] text-slate-500">Restore</p>
                <code className="rounded-md bg-white px-2 py-1 text-xs text-slate-950">
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
