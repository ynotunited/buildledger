"use client";

import { Suspense, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/components/auth/AuthProvider";
import { getPostLoginRedirect } from "@/lib/auth";

function AuthCallbackContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { refreshUser } = useAuth();

  useEffect(() => {
    const status = searchParams.get("status");

    if (status !== "success") {
      router.replace("/login?error=oauth_callback");
      return;
    }

    void (async () => {
      const signedIn = await refreshUser();

      if (signedIn) {
        router.replace(getPostLoginRedirect(window.location.search, signedIn.role));
        return;
      }

      router.replace("/login?error=oauth_callback");
    })();
  }, [refreshUser, router, searchParams]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4 text-muted-foreground">
      Signing you in...
    </div>
  );
}

export default function AuthCallbackPage() {
  return (
    <Suspense>
      <AuthCallbackContent />
    </Suspense>
  );
}
