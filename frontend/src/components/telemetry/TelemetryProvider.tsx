"use client";

import type { ReactNode } from "react";
import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { captureFrontendError, trackEvent } from "@/lib/telemetry";

export default function TelemetryProvider({ children }: { children: ReactNode }) {
  const pathname = usePathname();

  useEffect(() => {
    void trackEvent("page_view", { pathname });
  }, [pathname]);

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
          rejection: typeof reason === "string" ? reason : JSON.stringify(reason),
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
