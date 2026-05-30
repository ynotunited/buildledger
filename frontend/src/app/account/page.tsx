"use client";

import { FormEvent, useState } from "react";
import { AxiosError } from "axios";
import Link from "next/link";
import AppShell from "@/components/layout/AppShell";
import { useAuth } from "@/components/auth/AuthProvider";
import axiosInstance from "@/lib/axios";
import { AuthUser } from "@/lib/auth";

type ValidationErrors = Record<string, string[]>;

function getErrorMessage(error: unknown, fallback: string) {
  if (error instanceof AxiosError) {
    return error.response?.data?.message ?? fallback;
  }

  return fallback;
}

function getValidationErrors(error: unknown): ValidationErrors {
  if (error instanceof AxiosError) {
    return (error.response?.data?.errors as ValidationErrors | undefined) ?? {};
  }

  return {};
}

function AccountPageContent({ user }: { user: AuthUser }) {
  const { refreshUser } = useAuth();
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [profileMessage, setProfileMessage] = useState<string | null>(null);
  const [passwordMessage, setPasswordMessage] = useState<string | null>(null);
  const [profileErrors, setProfileErrors] = useState<ValidationErrors>({});
  const [passwordErrors, setPasswordErrors] = useState<ValidationErrors>({});
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);

  const usesGoogle = user.is_google_account;

  const handleProfileSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSavingProfile(true);
    setProfileMessage(null);
    setProfileErrors({});

    try {
      const response = await axiosInstance.put("/user/profile", { name, email });
      setProfileMessage(response.data.message ?? "Profile updated successfully.");
      await refreshUser();
    } catch (error) {
      setProfileMessage(getErrorMessage(error, "We couldn't update your profile right now."));
      setProfileErrors(getValidationErrors(error));
    } finally {
      setSavingProfile(false);
    }
  };

  const handlePasswordSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSavingPassword(true);
    setPasswordMessage(null);
    setPasswordErrors({});

    try {
      const response = await axiosInstance.put("/user/password", {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: passwordConfirmation,
      });
      setPasswordMessage(response.data.message ?? "Password updated successfully.");
      setCurrentPassword("");
      setNewPassword("");
      setPasswordConfirmation("");
    } catch (error) {
      setPasswordMessage(getErrorMessage(error, "We couldn't update your password right now."));
      setPasswordErrors(getValidationErrors(error));
    } finally {
      setSavingPassword(false);
    }
  };

  return (
    <div className="space-y-8">
        <section className="rounded-[2rem] border border-white/10 bg-card/80 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur md:p-8">
          <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <p className="text-sm uppercase tracking-[0.25em] text-primary/70">Account</p>
              <h1 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">Your profile settings</h1>
              <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                Keep your account details current and use one place to see how you signed in.
              </p>
            </div>
            <div className="rounded-2xl border border-white/10 bg-background/70 px-4 py-3 text-sm text-muted-foreground">
              <span className="block font-medium text-foreground">{user?.role?.replace("_", " ") ?? "Owner"}</span>
              <span>{usesGoogle ? "Signed in with Google" : "Signed in with email and password"}</span>
            </div>
          </div>
          <div className="mt-5 rounded-2xl border border-white/10 bg-background/60 p-4">
            <p className="text-sm font-medium text-foreground">Business branding</p>
            <p className="mt-1 text-sm text-muted-foreground">
              Set the company name, logo, and contact details that appear on proposals, invoices, and client-facing contract flows.
            </p>
            <Link
              href="/company"
              className="mt-4 inline-flex items-center justify-center rounded-2xl border border-border px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-secondary/60"
            >
              Open company settings
            </Link>
          </div>
        </section>

        <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
          <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
            <div className="mb-6">
              <h2 className="text-xl font-semibold text-foreground">Profile details</h2>
              <p className="mt-2 text-sm text-muted-foreground">
                Update the basics your workspace uses to identify you.
              </p>
            </div>

            <form className="space-y-5" onSubmit={(event) => void handleProfileSubmit(event)}>
              <div className="space-y-2">
                <label htmlFor="name" className="text-sm font-medium text-foreground">Full name</label>
                <input
                  id="name"
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                  placeholder="Your full name"
                />
                {profileErrors.name?.map((error) => (
                  <p key={error} className="text-sm text-red-400">{error}</p>
                ))}
              </div>

              <div className="space-y-2">
                <label htmlFor="email" className="text-sm font-medium text-foreground">Email address</label>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  disabled={usesGoogle}
                  className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary disabled:cursor-not-allowed disabled:opacity-60"
                  placeholder="you@example.com"
                />
                <p className="text-sm text-muted-foreground">
                  {usesGoogle
                    ? "This email comes from your Google account, so we keep it locked here."
                    : "If you change this, we will ask you to verify the new email address."}
                </p>
                {profileErrors.email?.map((error) => (
                  <p key={error} className="text-sm text-red-400">{error}</p>
                ))}
              </div>

              <div className="flex flex-wrap gap-3 text-sm">
                <div className="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-emerald-300">
                  Email status: {user?.email_verified_at ? "Verified" : "Not verified yet"}
                </div>
                <div className="rounded-2xl border border-sky-500/20 bg-sky-500/10 px-4 py-3 text-sky-300">
                  Sign-in method: {usesGoogle ? "Google OAuth" : "Email and password"}
                </div>
              </div>

              {profileMessage ? (
                <p className={`text-sm ${Object.keys(profileErrors).length ? "text-red-400" : "text-emerald-400"}`}>
                  {profileMessage}
                </p>
              ) : null}

              <button
                type="submit"
                disabled={savingProfile}
                className="inline-flex items-center justify-center rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground transition-transform hover:translate-y-[-1px] disabled:opacity-60"
              >
                {savingProfile ? "Saving..." : "Save profile"}
              </button>
            </form>
          </section>

          <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
            <div className="mb-6">
              <h2 className="text-xl font-semibold text-foreground">Password</h2>
              <p className="mt-2 text-sm text-muted-foreground">
                {usesGoogle
                  ? "Google accounts sign in through Google, so password changes are not managed here."
                  : "Choose a strong password with at least 8 characters."}
              </p>
            </div>

            {usesGoogle ? (
              <div className="rounded-3xl border border-amber-500/20 bg-amber-500/10 p-5 text-sm text-amber-100">
                <p className="font-medium text-amber-200">This account is linked to Google.</p>
                <p className="mt-2 text-amber-100/80">
                  If you want to manage sign-in security for this account, do that from your Google account settings.
                </p>
              </div>
            ) : (
              <form className="space-y-5" onSubmit={(event) => void handlePasswordSubmit(event)}>
                <div className="space-y-2">
                  <label htmlFor="current-password" className="text-sm font-medium text-foreground">Current password</label>
                  <input
                    id="current-password"
                    type="password"
                    value={currentPassword}
                    onChange={(event) => setCurrentPassword(event.target.value)}
                    className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                  />
                  {passwordErrors.current_password?.map((error) => (
                    <p key={error} className="text-sm text-red-400">{error}</p>
                  ))}
                </div>

                <div className="space-y-2">
                  <label htmlFor="new-password" className="text-sm font-medium text-foreground">New password</label>
                  <input
                    id="new-password"
                    type="password"
                    value={newPassword}
                    onChange={(event) => setNewPassword(event.target.value)}
                    className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                  />
                  {passwordErrors.password?.map((error) => (
                    <p key={error} className="text-sm text-red-400">{error}</p>
                  ))}
                </div>

                <div className="space-y-2">
                  <label htmlFor="password-confirmation" className="text-sm font-medium text-foreground">Confirm new password</label>
                  <input
                    id="password-confirmation"
                    type="password"
                    value={passwordConfirmation}
                    onChange={(event) => setPasswordConfirmation(event.target.value)}
                    className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                  />
                </div>

                {passwordMessage ? (
                  <p className={`text-sm ${Object.keys(passwordErrors).length ? "text-red-400" : "text-emerald-400"}`}>
                    {passwordMessage}
                  </p>
                ) : null}

                <button
                  type="submit"
                  disabled={savingPassword}
                  className="inline-flex items-center justify-center rounded-2xl bg-foreground px-5 py-3 text-sm font-medium text-background transition-transform hover:translate-y-[-1px] disabled:opacity-60"
                >
                  {savingPassword ? "Updating..." : "Update password"}
                </button>
              </form>
            )}
          </section>
        </div>
    </div>
  );
}

export default function AccountPage() {
  const { user, isLoading } = useAuth();

  return (
    <AppShell>
      {!user && isLoading ? (
        <div className="rounded-[2rem] border border-white/10 bg-card p-8 text-sm text-muted-foreground">
          Loading your account...
        </div>
      ) : user ? (
        <AccountPageContent key={`${user.id}-${user.email}-${user.name}`} user={user} />
      ) : (
        <div className="rounded-[2rem] border border-white/10 bg-card p-8 text-sm text-muted-foreground">
          We couldn&apos;t load your account right now.
        </div>
      )}
    </AppShell>
  );
}
