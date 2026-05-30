"use client";

import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";

function BillingVerifyContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const reference = searchParams.get("reference") ?? searchParams.get("tx_ref") ?? "";
  const gateway = (searchParams.get("gateway") ?? "paystack") as "paystack" | "flutterwave";
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");

  useEffect(() => {
    const verify = async () => {
      if (!reference) {
        setStatus("error");
        return;
      }

      try {
        const idempotencyKey = getOrCreateIdempotencyKey("billing-verify", { reference, gateway });
        const response = await axiosInstance.post("/billing/verify", { reference, gateway, idempotency_key: idempotencyKey }, {
          headers: {
            "Idempotency-Key": idempotencyKey,
          },
        });
        if (response.data.idempotency_status === "processing") {
          return;
        }
        setStatus("success");
      } catch (error) {
        console.error("Error verifying billing payment", error);
        setStatus("error");
      }
    };

    void verify();
  }, [gateway, reference]);

  return (
    <AppShell>
      <div className="mx-auto max-w-xl rounded-[2rem] border border-white/10 bg-card p-8 text-center">
        <h1 className="text-3xl font-semibold tracking-tight">Billing verification</h1>
        {status === "loading" ? (
          <p className="mt-4 text-sm text-muted-foreground">Verifying your subscription payment...</p>
        ) : status === "success" ? (
          <>
            <p className="mt-4 text-sm text-emerald-400">Your subscription has been activated successfully.</p>
            <button
              onClick={() => router.push("/billing")}
              className="mt-6 inline-flex rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground"
            >
              Back to billing
            </button>
          </>
        ) : (
          <>
            <p className="mt-4 text-sm text-red-400">We could not verify this subscription payment.</p>
            <button
              onClick={() => router.push("/billing")}
              className="mt-6 inline-flex rounded-2xl border border-border px-5 py-3 text-sm font-medium"
            >
              Return to billing
            </button>
          </>
        )}
      </div>
    </AppShell>
  );
}

export default function BillingVerifyPage() {
  return (
    <Suspense>
      <BillingVerifyContent />
    </Suspense>
  );
}
