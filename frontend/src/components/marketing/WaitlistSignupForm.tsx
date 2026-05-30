"use client";

import { useState } from "react";
import { AxiosError } from "axios";
import { ArrowRight, Mail } from "lucide-react";
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
    <section id="waitlist" className="landing-fade-up mt-12 rounded-[2.25rem] border border-emerald-400/15 bg-gradient-to-br from-emerald-400/12 via-white/6 to-transparent p-6 shadow-2xl shadow-black/20 backdrop-blur md:p-8">
      <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div className="max-w-2xl">
          <div className="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">
            <Mail className="h-3.5 w-3.5" />
            Launch invitations
          </div>
          <h2 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
            Request an invite to join BuildLedger.
          </h2>
          <p className="mt-3 max-w-xl text-sm leading-6 text-zinc-300">
            We&apos;re opening access in batches. Leave your name and email and we&apos;ll send an invitation when a seat opens up.
            No spam. Just the useful stuff.
          </p>

          <div className="mt-5 flex flex-wrap gap-2 text-xs text-zinc-300">
            <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Early access updates</span>
            <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Launch announcements</span>
            <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Priority onboarding</span>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="w-full max-w-xl rounded-[1.75rem] border border-white/10 bg-black/20 p-5 md:p-6">
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
              {loading ? "Requesting..." : "Request invite"}
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
