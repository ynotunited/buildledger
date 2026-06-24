"use client";

import { useState } from "react";
import { AxiosError } from "axios";
import { ArrowRight, Mail, ShieldCheck } from "lucide-react";
import axiosInstance from "@/lib/axios";
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
    <section id="waitlist" className="border-t border-slate-200 py-16 sm:py-20">
      <div className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr] lg:items-stretch">
        <div className="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-8">
          <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-medium text-emerald-900">
            <Mail className="h-3.5 w-3.5" />
            Private launch queue
          </div>

          <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance text-slate-950 sm:text-4xl">
            Request access to BuildLedger.
          </h2>
          <p className="mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base">
            We open seats in small batches so onboarding stays clean. Leave your details and we&apos;ll email you when the next invitation is ready.
          </p>

          <div className="mt-6 space-y-3">
            {[
              "Invoices stay tied to the client and due date from the start.",
              "Imported payments can be matched without a messy detour.",
              "Ledger entries keep the balance impact visible and readable.",
            ].map((item) => (
              <div key={item} className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                <p className="text-sm leading-6 text-slate-700">{item}</p>
              </div>
            ))}
          </div>
        </div>

        <form onSubmit={handleSubmit} className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-8">
          <div className="flex items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <p className="text-xs uppercase tracking-[0.22em] text-slate-500">Waitlist</p>
              <h3 className="mt-2 text-2xl font-semibold text-slate-950">Join the private launch list</h3>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600">
              Invite only
            </span>
          </div>

          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
              <Label htmlFor="waitlist-name" className="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-500">
                Full name
              </Label>
              <Input
                id="waitlist-name"
                type="text"
                placeholder="Your name"
                autoComplete="name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                className="h-12 rounded-2xl border-slate-200 bg-white px-4 text-slate-900 placeholder:text-slate-400 focus-visible:ring-emerald-300 dark:!border-slate-200 dark:!bg-white dark:!text-slate-900"
              />
            </div>

            <div>
              <Label htmlFor="waitlist-email" className="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-500">
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
                className="h-12 rounded-2xl border-slate-200 bg-white px-4 text-slate-900 placeholder:text-slate-400 focus-visible:ring-emerald-300 dark:!border-slate-200 dark:!bg-white dark:!text-slate-900"
              />
            </div>
          </div>

          <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <button
              type="submit"
              disabled={loading}
              className="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-medium text-white transition-transform hover:-translate-y-0.5 hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {loading ? "Requesting..." : "Request access"}
              <ArrowRight className="h-4 w-4" />
            </button>

            <p className="text-xs leading-5 text-slate-500">
              We&apos;ll only email you about invitations, launch updates, and onboarding.
            </p>
          </div>

          {success ? (
            <div className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
              {success}
            </div>
          ) : null}

          {error ? (
            <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
              {error}
            </div>
          ) : null}
        </form>
      </div>
    </section>
  );
}
