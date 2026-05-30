"use client";

import { useEffect, useState, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import AppShell from "@/components/layout/AppShell";
import { CheckCircle, XCircle, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";

function VerifyContent() {
  const searchParams = useSearchParams();
  const router       = useRouter();
  const reference    = searchParams.get("reference") || searchParams.get("trxref");
  const gateway      = searchParams.get("gateway") ?? "paystack";
  const missingReference = !reference;

  const [status, setStatus] = useState<"loading" | "success" | "failed">("loading");

  useEffect(() => {
    if (!reference) {
      return;
    }

    const idempotencyKey = getOrCreateIdempotencyKey("payment-verify", { reference, gateway });

    axiosInstance.post("/payments/verify", { reference, gateway, idempotency_key: idempotencyKey }, {
      headers: {
        "Idempotency-Key": idempotencyKey,
      },
    })
      .then((res) => {
        if (res.data.idempotency_status === "processing") {
          return;
        }
        setStatus(res.data.status === "Completed" ? "success" : "failed");
      })
      .catch(() => setStatus("failed"));
  }, [reference, gateway]);

  return (
    <AppShell>
      <div className="flex flex-col items-center justify-center min-h-[60vh] text-center space-y-4">
        {missingReference || status === "failed" ? (
          <>
            <div className="w-16 h-16 rounded-full bg-red-500/10 flex items-center justify-center">
              <XCircle className="w-8 h-8 text-red-500" />
            </div>
            <h2 className="text-xl font-bold">Payment Failed</h2>
            <p className="text-muted-foreground text-sm">We could not verify your payment. Please try again.</p>
            <Button variant="outline" onClick={() => router.push("/payments/record")}>Try Again</Button>
          </>
        ) : status === "loading" ? (
          <>
            <Loader2 className="w-12 h-12 animate-spin text-primary" />
            <p className="text-muted-foreground">Verifying your payment...</p>
          </>
        ) : (
          <>
            <div className="w-16 h-16 rounded-full bg-green-500/10 flex items-center justify-center">
              <CheckCircle className="w-8 h-8 text-green-500" />
            </div>
            <h2 className="text-xl font-bold">Payment Successful</h2>
            <p className="text-muted-foreground text-sm">Your payment has been confirmed and recorded.</p>
            <Button onClick={() => router.push("/payments")}>View Payments</Button>
          </>
        )}
      </div>
    </AppShell>
  );
}

export default function PaymentVerifyPage() {
  return (
    <Suspense>
      <VerifyContent />
    </Suspense>
  );
}
