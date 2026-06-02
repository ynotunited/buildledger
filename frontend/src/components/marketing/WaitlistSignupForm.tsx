"use client";

import { useState } from "react";
import { AxiosError } from "axios";
import { ArrowRight, Mail, ShieldCheck } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function WaitlistSignupForm() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState("");
  const [error, setError] = useState("");

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const response = await axiosInstance.post("/waitlist", {
        name,
        email,
        source: "homepage",
      });

      setSuccess(response.data?.message ?? "You're on the waitlist. We'll email you when an invitation is ready.");
      setName("");
      setEmail("");
    } catch (err) {
      const message = err instanceof AxiosError ? err.response?.data?.message : null;
      setError(message || "We couldn't save your signup right now. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <section id="waitlist" className="landing-fade-up mt-12 overflow-hidden rounded-[2.5rem] border border-emerald-400/15 bg-gradient-to-br from-emerald-400/14 via-white/6 to-transparent p-6 shadow-2xl shadow-black/20 backdrop-blur md:p-8">
      <div className="grid gap-6 lg:grid-cols-[1.08fr_0.92fr] lg:items-stretch">
        <div className="relative flex h-full min-w-0 flex-col justify-between overflow-hidden rounded-[2rem] border border-white/10 bg-black/15 p-6 md:p-7">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">
              <Mail className="h-3.5 w-3.5" />
              Controlled access
            </div>
            <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
              Request access to BuildLedger.
            </h2>
            <p className="mt-3 max-w-xl text-sm leading-6 text-zinc-300">
              We&apos;re opening access in small batches so every team gets a proper rollout. Leave your name and email and we&apos;ll send an invitation when the next seat opens.
            </p>

            <div className="mt-5 flex flex-wrap gap-2 text-xs text-zinc-300">
              <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Private launch updates</span>
              <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Release announcements</span>
              <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Priority onboarding</span>
            </div>

            <div className="mt-6 w-full max-w-full overflow-hidden rounded-[1.5rem] border border-white/10 bg-black/20 p-3 shadow-inner shadow-black/25">
              <div className="grid gap-3 sm:grid-cols-3">
              {[
                {
                  title: "Proposal",
                  accent: "from-emerald-400/25 to-emerald-400/5",
                  rows: ["Scope approval", "Pricing summary", "Client notes"],
                  pill: "Ready to approve",
                },
                {
                  title: "Invoice",
                  accent: "from-cyan-400/20 to-cyan-400/5",
                  rows: ["Invoice #014", "Due in 7 days", "Payment link included"],
                  pill: "Awaiting payment",
                },
                {
                  title: "Payment",
                  accent: "from-white/15 to-white/5",
                  rows: ["Paid", "Receipt sent", "Ledger updated"],
                  pill: "Closed out",
                },
              ].map((shot) => (
                <article
                  key={shot.title}
                  className="min-w-0 overflow-hidden rounded-[1.35rem] border border-white/10 bg-black/20"
                >
                  <div className={`h-2 bg-gradient-to-r ${shot.accent}`} />
                  <div className="p-2.5">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-[0.65rem] uppercase tracking-[0.24em] text-zinc-500">Workflow</p>
                        <h3 className="mt-1 text-[0.78rem] font-semibold uppercase tracking-[0.18em] text-zinc-100">
                          {shot.title}
                        </h3>
                      </div>
                      <div className="h-7 w-7 shrink-0 rounded-2xl border border-white/10 bg-white/5" />
                    </div>

                    <div className="mt-4 space-y-2 rounded-2xl border border-white/10 bg-white/5 p-2.5">
                      <div className="h-2 w-3/5 rounded-full bg-white/15" />
                      <div className="h-2 w-4/5 rounded-full bg-white/10" />
                      <div className="h-2 w-1/2 rounded-full bg-white/10" />
                    </div>

                    <div className="mt-4 space-y-2">
                      {shot.rows.map((row, index) => (
                        <div key={row} className="flex items-center justify-between gap-2 rounded-2xl border border-white/8 bg-white/5 px-2.5 py-2">
                          <span className="text-[0.8rem] leading-5 text-zinc-200">{row}</span>
                          <span className="text-[0.65rem] uppercase tracking-[0.22em] text-zinc-500">
                            {index === 0 ? "Live" : index === 1 ? "Today" : "Done"}
                          </span>
                        </div>
                      ))}
                    </div>

                    <div className="mt-4 inline-flex max-w-full rounded-full border border-white/10 bg-black/20 px-2.5 py-1 text-[0.65rem] font-medium uppercase tracking-[0.22em] text-zinc-300">
                      {shot.pill}
                    </div>
                  </div>
                </article>
              ))}
              </div>
            </div>
          </div>

          <div className="mt-8 rounded-[1.5rem] border border-emerald-400/15 bg-emerald-400/8 p-4">
            <div className="flex items-start gap-3">
              <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-200" />
              <div>
                <p className="text-sm font-medium text-emerald-100">Private launch. No public signup.</p>
                <p className="mt-1 text-sm leading-6 text-emerald-100/75">
                  Invitations are reviewed in batches so each team gets a clean, high-touch onboarding experience.
                </p>
              </div>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="w-full rounded-[2rem] border border-white/10 bg-black/20 p-6 md:p-8">
          <div className="mb-5 flex items-center justify-between gap-4 border-b border-white/10 pb-5">
            <div>
              <p className="text-xs uppercase tracking-[0.24em] text-zinc-500">Waitlist</p>
              <h3 className="mt-2 text-2xl font-semibold text-white">Join the private launch list</h3>
            </div>
            <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-zinc-300">
              Invite-only
            </span>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-1">
              <Label htmlFor="waitlist-name" className="mb-2 block text-xs uppercase tracking-[0.2em] text-zinc-400">
                Full name
              </Label>
              <Input
                id="waitlist-name"
                type="text"
                placeholder="Your name"
                autoComplete="name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                className="h-12 rounded-2xl border-white/10 bg-white/5 px-4 text-white placeholder:text-zinc-500 focus-visible:ring-emerald-400/30"
              />
            </div>

            <div className="sm:col-span-1">
              <Label htmlFor="waitlist-email" className="mb-2 block text-xs uppercase tracking-[0.2em] text-zinc-400">
                Email address
              </Label>
              <Input
                id="waitlist-email"
                type="email"
                required
                placeholder="you@example.com"
                autoComplete="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="h-12 rounded-2xl border-white/10 bg-white/5 px-4 text-white placeholder:text-zinc-500 focus-visible:ring-emerald-400/30"
              />
            </div>
          </div>

          <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
              type="submit"
              disabled={loading}
              size="lg"
              className="h-12 rounded-2xl bg-emerald-400 px-5 text-sm font-medium text-zinc-950 hover:bg-emerald-300"
            >
              {loading ? "Requesting..." : "Request access"}
              <ArrowRight className="h-4 w-4" />
            </Button>

            <p className="text-xs leading-5 text-zinc-400">
              We&apos;ll only email you about invitations, launch updates, and onboarding.
            </p>
          </div>

          {success ? (
            <div className="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
              {success}
            </div>
          ) : null}

          {error ? (
            <div className="mt-4 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
              {error}
            </div>
          ) : null}
        </form>
      </div>
    </section>
  );
}
