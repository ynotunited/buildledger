"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Eye, EyeOff, ArrowRight } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { AxiosError } from "axios";
import { getPostLoginRedirect } from "@/lib/auth";
import { useAuth } from "@/components/auth/AuthProvider";
import InviteOnlyBanner from "@/components/marketing/InviteOnlyBanner";

// 4K dark mountain landscape — served locally from /public
const BG_IMAGE = "/auth-bg.png";

const GoogleIcon = () => (
  <svg viewBox="0 0 24 24" className="h-4 w-4" xmlns="http://www.w3.org/2000/svg">
    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
  </svg>
);

function RegisterPageContent() {
  const searchParams = useSearchParams();
  const [name, setName]         = useState("");
  const [email, setEmail]       = useState(() => searchParams.get("email") ?? "");
  const [password, setPassword] = useState("");
  const [showPw, setShowPw]     = useState(false);
  const [agreed, setAgreed]     = useState(false);
  const [error, setError]       = useState("");
  const [loading, setLoading]   = useState(false);
  const router = useRouter();
  const { isAuthenticated, isLoading, user, setAuthenticatedUser } = useAuth();

  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      router.replace(getPostLoginRedirect(window.location.search, user?.role));
    }
  }, [isAuthenticated, isLoading, router, user]);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!agreed) { setError("Please agree to the Terms & Conditions to continue."); return; }
    setError("");
    setLoading(true);
    try {
      const res = await axiosInstance.post("/register", {
        name,
        email,
        password,
        password_confirmation: password,
      });
      if (res.data.user) {
        setAuthenticatedUser(res.data.user);
        router.push(getPostLoginRedirect(window.location.search, res.data.user.role));
      }
    } catch (err) {
      if (err instanceof AxiosError) {
        const responseData = err.response?.data as Record<string, unknown> | undefined;
        const validationMessage = responseData?.errors
          ? Object.values(responseData.errors as Record<string, string[]>)
              .flat()
              .find(Boolean)
          : null;
        const msg = typeof responseData?.message === "string"
          ? responseData.message
          : null;

        setError(validationMessage || msg || "Registration failed. Please try again.");
        return;
      }

      setError("Registration failed. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen bg-[#1a1a2e]">

      {/* ── Left panel — 4K image ── */}
      <div className="relative hidden w-[45%] lg:flex flex-col overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={BG_IMAGE}
          alt="Dark mountain landscape"
          className="absolute inset-0 h-full w-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/30" />

        {/* Top bar */}
        <div className="relative z-10 flex items-center justify-between px-8 pt-8">
          <span className="text-lg font-semibold uppercase tracking-[0.25em] text-white">
            BuildLedger
          </span>
          <Link
            href="/"
            className="flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-white/20"
          >
            Back to website <ArrowRight className="h-3 w-3" />
          </Link>
        </div>

        {/* Bottom tagline */}
        <div className="relative z-10 mt-auto px-8 pb-10">
          <p className="text-3xl font-semibold leading-snug text-white">
            Run your business,<br />from proposal to payment.
          </p>
          <div className="mt-6 flex items-center gap-2">
            <span className="h-1 w-6 rounded-full bg-white/40" />
            <span className="h-1 w-6 rounded-full bg-white/40" />
            <span className="h-1 w-10 rounded-full bg-white" />
          </div>
        </div>
      </div>

      {/* ── Right panel — form ── */}
      <div className="flex flex-1 flex-col items-center justify-center px-6 py-12">
        {/* Mobile logo */}
        <div className="mb-8 flex items-center gap-2 lg:hidden">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-600 text-sm font-bold text-white">B</div>
          <span className="text-base font-semibold tracking-wide text-white">BuildLedger</span>
        </div>

        <div className="w-full max-w-md">
          <InviteOnlyBanner compact />
          <h1 className="text-3xl font-bold text-white">Create an account</h1>
          <p className="mt-2 text-sm text-zinc-400">
            Approved invites can create an account. Already have one?{" "}
            <Link href="/login" className="text-violet-400 underline underline-offset-2 hover:text-violet-300">
              Log in
            </Link>
          </p>

          {error && (
            <div className="mt-5 rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-400">
              {error}
            </div>
          )}

          <form onSubmit={handleRegister} className="mt-8 space-y-4">
            {/* Name */}
            <input
              type="text"
              required
              placeholder="Full name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              disabled={loading}
              className="auth-input"
            />

            {/* Email */}
            <input
              type="email"
              required
              placeholder="Email address"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={loading}
              className="auth-input"
            />

            {/* Password */}
            <div className="relative">
              <input
                type={showPw ? "text" : "password"}
                required
                placeholder="Create a password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={loading}
                className="auth-input pr-11"
              />
              <button
                type="button"
                onClick={() => setShowPw((v) => !v)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300"
              >
                {showPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>

            {/* Terms checkbox */}
            <label className="flex cursor-pointer items-start gap-3">
              <div className="relative mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center">
                <input
                  type="checkbox"
                  checked={agreed}
                  onChange={(e) => setAgreed(e.target.checked)}
                  className="peer h-5 w-5 cursor-pointer appearance-none rounded border border-white/20 bg-white/5 checked:border-violet-500 checked:bg-violet-600 focus:outline-none"
                />
                {/* checkmark */}
                <svg
                  className="pointer-events-none absolute hidden h-3 w-3 text-white peer-checked:block"
                  viewBox="0 0 12 12" fill="none"
                >
                  <path d="M2 6l3 3 5-5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <span className="text-sm text-zinc-400">
                I agree to the{" "}
                <Link href="/terms-of-use" className="text-violet-400 underline underline-offset-2 hover:text-violet-300">
                  Terms &amp; Conditions
                </Link>
                {" "}and{" "}
                <Link href="/privacy-policy" className="text-violet-400 underline underline-offset-2 hover:text-violet-300">
                  Privacy Policy
                </Link>
              </span>
            </label>

            <button
              type="submit"
              disabled={loading || !agreed}
              className="auth-btn-primary disabled:opacity-50"
            >
              {loading ? "Creating account…" : "Create account"}
            </button>
          </form>

          {/* Divider */}
          <div className="my-6 flex items-center gap-3">
            <span className="h-px flex-1 bg-white/10" />
            <span className="text-xs text-zinc-500">Or register with</span>
            <span className="h-px flex-1 bg-white/10" />
          </div>

          {/* Social */}
          <button
            type="button"
            disabled={loading}
            onClick={() => { window.location.href = `${process.env.NEXT_PUBLIC_API_URL}/auth/google/redirect`; }}
            className="auth-btn-social"
          >
            <GoogleIcon />
            Google
          </button>
        </div>
      </div>
    </div>
  );
}

export default function RegisterPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-[#1a1a2e]" />}>
      <RegisterPageContent />
    </Suspense>
  );
}
