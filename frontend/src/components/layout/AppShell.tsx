"use client";

import React, { useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import BottomNav from "./BottomNav";
import SideNav from "./SideNav";
import NotificationBell from "./NotificationBell";
import { useAuth } from "@/components/auth/AuthProvider";
import axiosInstance from "@/lib/axios";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { AuthUser } from "@/lib/auth";

export default function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, refreshUser, isLoading, setAuthenticatedUser } = useAuth();
  const [resendingVerification, setResendingVerification] = useState(false);
  const isAdminUser = user?.role === "admin";
  const isImpersonating = !!user?.is_impersonating;
  const shouldShowVerificationBanner = !!user && !user.email_verified_at && !isAdminUser && !isImpersonating && !pathname.startsWith("/admin");

  const resendVerification = async () => {
    if (!user) {
      return;
    }

    setResendingVerification(true);
    try {
      await axiosInstance.post("/email/resend");
    } finally {
      setResendingVerification(false);
      await refreshUser();
    }
  };

  const stopImpersonation = async () => {
    try {
      const response = await axiosInstance.post<{ message: string; user: AuthUser }>("/admin/impersonation/stop");
      if (response.data.user) {
        setAuthenticatedUser(response.data.user);
        router.replace("/admin");
      }
    } catch (error) {
      console.error("Failed to stop impersonation", error);
      await refreshUser();
    }
  };

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background px-4">
        <div className="w-full max-w-md space-y-4">
          <Skeleton className="h-8 w-40" />
          <Skeleton className="h-32 w-full rounded-2xl" />
          <Skeleton className="h-32 w-full rounded-2xl" />
        </div>
      </div>
    );
  }

  return (
    <div className="flex h-screen w-full bg-background overflow-hidden">
      {/* Desktop Side Navigation */}
      <aside className="hidden md:flex flex-col w-64 border-r border-border bg-card">
        <SideNav />
      </aside>

      {/* Main Content Area */}
      <main className="flex-1 flex flex-col h-full overflow-y-auto pb-16 md:pb-0 relative">
        {/* Top bar — desktop only */}
        <header className="hidden md:flex items-center justify-end px-8 py-3 border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-10">
          <NotificationBell />
        </header>

        <div className="flex-1 p-4 md:p-8 max-w-6xl mx-auto w-full">
          {isImpersonating && user && (
            <div className="mb-6 flex flex-col gap-3 rounded-2xl border border-sky-500/20 bg-sky-500/10 p-4 text-sm text-sky-100 md:flex-row md:items-center md:justify-between">
              <div>
                <p className="font-medium text-sky-200">
                  You are viewing this account as {user.name}.
                </p>
                <p className="text-sky-100/80">
                  Support mode is active{user.impersonator_name ? ` on behalf of ${user.impersonator_name}` : ""}. Use this to inspect the workspace exactly as the customer sees it.
                </p>
              </div>
              <Button
                onClick={() => void stopImpersonation()}
                variant="secondary"
                className="rounded-xl bg-sky-200/10 text-sky-100 hover:bg-sky-200/20"
              >
                Return to admin
              </Button>
            </div>
          )}
          {shouldShowVerificationBanner && (
            <div className="mb-6 flex flex-col gap-3 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-100 md:flex-row md:items-center md:justify-between">
              <div>
                <p className="font-medium text-amber-200">Verify your email to finish setting up your account.</p>
                <p className="text-amber-100/80">We&apos;ve added email verification, password reset, and Google sign-in support to the app.</p>
              </div>
              <button
                onClick={() => void resendVerification()}
                disabled={resendingVerification}
                className="rounded-xl border border-amber-300/20 px-3 py-2 font-medium text-amber-100 transition-colors hover:bg-amber-400/10 disabled:opacity-60"
              >
                {resendingVerification ? "Sending..." : "Resend verification"}
              </button>
            </div>
          )}
          {children}
        </div>
      </main>

      {/* Mobile Bottom Navigation */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 h-16 border-t border-border bg-background/80 backdrop-blur-xl z-50 px-4 pb-safe">
        <BottomNav />
      </nav>

      {/* Mobile notification bell — top-right floating */}
      <div className="md:hidden fixed top-3 right-3 z-50">
        <NotificationBell />
      </div>
    </div>
  );
}
