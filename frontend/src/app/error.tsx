"use client";

import { useEffect } from "react";
import { captureFrontendError } from "@/lib/telemetry";

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    void captureFrontendError({
      message: error.message,
      stack: error.stack ?? null,
      context: {
        digest: error.digest ?? null,
      },
    });
  }, [error]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <div className="max-w-lg rounded-[2rem] border border-white/10 bg-card p-8 text-center">
        <h2 className="text-3xl font-semibold tracking-tight">Something went wrong</h2>
        <p className="mt-3 text-sm text-muted-foreground">
          We captured the error and can use it for follow-up. Please try the action again.
        </p>
        <button
          onClick={() => reset()}
          className="mt-6 inline-flex rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground"
        >
          Try again
        </button>
      </div>
    </div>
  );
}
