"use client";

import type { ReactNode } from "react";
import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { useAuth } from "@/components/auth/AuthProvider";
import { captureFrontendError, formatTelemetryContext, setObservabilityUser, trackEvent } from "@/lib/telemetry";

export default function TelemetryProvider({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const { isLoading, user } = useAuth();

  useEffect(() => {
    void trackEvent("page_view", { pathname });
  }, [pathname]);

  useEffect(() => {
    if (isLoading) {
      return;
    }

    setObservabilityUser(
      user
        ? {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role,
          }
        : null,
    );
  }, [isLoading, user]);

  useEffect(() => {
    const onError = (event: ErrorEvent) => {
      void captureFrontendError({
        message: event.message || "Unhandled browser error",
        stack: event.error?.stack ?? null,
        context: {
          source: event.filename,
          line: event.lineno,
          column: event.colno,
        },
      });
    };

    const onUnhandledRejection = (event: PromiseRejectionEvent) => {
      const reason = event.reason;
      void captureFrontendError({
        message: reason instanceof Error ? reason.message : "Unhandled promise rejection",
        stack: reason instanceof Error ? reason.stack : null,
        context: {
          rejection: formatTelemetryContext(reason),
        },
      });
    };

    window.addEventListener("error", onError);
    window.addEventListener("unhandledrejection", onUnhandledRejection);

    return () => {
      window.removeEventListener("error", onError);
      window.removeEventListener("unhandledrejection", onUnhandledRejection);
    };
  }, []);

  return <>{children}</>;
}
