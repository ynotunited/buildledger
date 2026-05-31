"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { AxiosError } from "axios";
import axiosInstance from "@/lib/axios";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import BrandLogo from "@/components/brand/BrandLogo";

function VerifyEmailContent() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const email = searchParams.get("email") ?? "";
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");
  const [message, setMessage] = useState("Verifying your email...");

  useEffect(() => {
    let mounted = true;

    const verify = async () => {
      if (!token || !email) {
        if (mounted) {
          setStatus("error");
          setMessage("This verification link is incomplete.");
        }
        return;
      }

      try {
        const response = await axiosInstance.post("/email/verify", { email, token });
        if (mounted) {
          setStatus("success");
          setMessage(response.data.message ?? "Email verified successfully.");
        }
      } catch (err) {
        const message = err instanceof AxiosError ? err.response?.data?.message : null;
        if (mounted) {
          setStatus("error");
          setMessage(message ?? "This verification link is invalid or expired.");
        }
      }
    };

    void verify();
    return () => {
      mounted = false;
    };
  }, [email, token]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-sm">
        <CardHeader className="text-center">
          <div className="mb-2 flex justify-center">
            <BrandLogo href="/" variant="color" className="h-10 w-auto" />
          </div>
          <CardTitle>Email verification</CardTitle>
          <CardDescription>{email || "BuildLedger account"}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4 text-center">
          <p className={status === "error" ? "text-destructive" : status === "success" ? "text-green-500" : "text-muted-foreground"}>
            {message}
          </p>
          <Link href="/login" className="block">
            <Button className="w-full">{status === "success" ? "Continue to sign in" : "Back to sign in"}</Button>
          </Link>
        </CardContent>
      </Card>
    </div>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense>
      <VerifyEmailContent />
    </Suspense>
  );
}
