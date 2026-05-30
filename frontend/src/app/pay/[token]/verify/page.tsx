"use client";

import { Suspense, useEffect, useState } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import axiosInstance from "@/lib/axios";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";
import { Button } from "@/components/ui/button";

function PublicInvoiceVerifyContent() {
  const router = useRouter();
  const params = useParams<{ token: string }>();
  const searchParams = useSearchParams();
  const reference = searchParams.get("reference") ?? searchParams.get("tx_ref") ?? searchParams.get("trxref") ?? "";
  const gateway = (searchParams.get("gateway") ?? "paystack") as "paystack" | "flutterwave";
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");

  const token = Array.isArray(params.token) ? params.token[0] : params.token;

  useEffect(() => {
    const verify = async () => {
      if (!reference || !token) {
        setStatus("error");
        return;
      }

      try {
        const idempotencyKey = getOrCreateIdempotencyKey("public-invoice-verify", {
          token,
          reference,
          gateway,
        });

        const response = await axiosInstance.post(`/public/invoices/${token}/verify`, {
          reference,
          gateway,
          idempotency_key: idempotencyKey,
        }, {
          headers: {
            "Idempotency-Key": idempotencyKey,
          },
        });
        if (response.data.idempotency_status === "processing") {
          return;
        }
        setStatus("success");
      } catch (error) {
        console.error("Error verifying public invoice payment", error);
        setStatus("error");
      }
    };

    void verify();
  }, [gateway, reference, token]);

  return (
    <div className="min-h-screen bg-slate-950 px-4 py-10 text-white">
      <div className="mx-auto max-w-xl rounded-[2rem] border border-white/10 bg-white/8 p-8 text-center backdrop-blur-xl">
        <p className="text-xs uppercase tracking-[0.35em] text-cyan-200/80">Invoice payment</p>
        <h1 className="mt-3 text-3xl font-semibold tracking-tight">Verifying payment</h1>
        {status === "loading" ? (
          <p className="mt-4 text-sm text-white/70">We are confirming your payment with the gateway now.</p>
        ) : status === "success" ? (
          <>
            <p className="mt-4 text-sm text-emerald-300">Your payment was verified successfully.</p>
            <div className="mt-6 flex justify-center gap-3">
              <Button onClick={() => router.push(`/pay/${token}`)}>View invoice</Button>
              <Button variant="outline" onClick={() => router.push("/")}>Home</Button>
            </div>
          </>
        ) : (
          <>
            <p className="mt-4 text-sm text-red-300">We could not verify this payment.</p>
            <div className="mt-6 flex justify-center gap-3">
              <Button onClick={() => router.push(`/pay/${token}`)}>Return to invoice</Button>
              <Button variant="outline" onClick={() => router.push("/")}>Home</Button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default function PublicInvoiceVerifyPage() {
  return (
    <Suspense>
      <PublicInvoiceVerifyContent />
    </Suspense>
  );
}
