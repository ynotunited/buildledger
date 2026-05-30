"use client";

import { useEffect, useState } from "react";
import AppShell from "@/components/layout/AppShell";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, CreditCard, CheckCircle, Clock, XCircle, DollarSign } from "lucide-react";
import axiosInstance from "@/lib/axios";
import Link from "next/link";

interface Payment {
  id: number;
  amount: string;
  currency: string;
  status: string;
  gateway: string;
  paid_at: string | null;
  invoice: { invoice_number: string };
  client: { name: string };
}

const STATUS_ICON: Record<string, React.ReactNode> = {
  Completed: <CheckCircle className="w-4 h-4 text-green-500" />,
  Pending:   <Clock className="w-4 h-4 text-yellow-500" />,
  Failed:    <XCircle className="w-4 h-4 text-red-500" />,
  Refunded:  <XCircle className="w-4 h-4 text-orange-500" />,
};

export default function PaymentsPage() {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading]   = useState(true);

  const totalReceived = payments
    .filter((p) => p.status === "Completed")
    .reduce((sum, p) => sum + Number(p.amount), 0);

  useEffect(() => {
    axiosInstance.get("/payments")
      .then((r) => setPayments(r.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Payments</h1>
            <p className="text-muted-foreground text-sm">Track revenue and outstanding balances.</p>
          </div>
          <Link href="/payments/record">
            <Button size="sm">
              <Plus className="w-4 h-4 mr-2" />
              Record Payment
            </Button>
          </Link>
        </div>

        {/* Summary card */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Received</CardTitle>
            <DollarSign className="w-4 h-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">
              ₦{totalReceived.toLocaleString("en-NG", { minimumFractionDigits: 2 })}
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              {payments.filter((p) => p.status === "Completed").length} completed payments
            </p>
          </CardContent>
        </Card>

        {loading ? (
          <div className="space-y-3">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="h-16 rounded-xl bg-secondary/40 animate-pulse" />
            ))}
          </div>
        ) : payments.length === 0 ? (
          <div className="text-center p-12 text-muted-foreground border rounded-xl border-dashed">
            <CreditCard className="w-10 h-10 mx-auto mb-3 opacity-30" />
            <p className="font-medium">No payments yet</p>
            <p className="text-sm mt-1">Record a payment or send a payment link to a client.</p>
          </div>
        ) : (
          <div className="space-y-2">
            {payments.map((payment) => (
              <div
                key={payment.id}
                className="flex items-center justify-between p-4 rounded-xl border border-border bg-card hover:bg-secondary/30 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 rounded-full bg-secondary flex items-center justify-center">
                    <CreditCard className="w-4 h-4 text-muted-foreground" />
                  </div>
                  <div>
                    <p className="text-sm font-medium">{payment.client.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {payment.invoice.invoice_number} · {payment.gateway}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="text-right">
                    <p className="text-sm font-semibold">
                      ₦{Number(payment.amount).toLocaleString("en-NG", { minimumFractionDigits: 2 })}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {payment.paid_at ? new Date(payment.paid_at).toLocaleDateString() : "—"}
                    </p>
                  </div>
                  {STATUS_ICON[payment.status] ?? <Clock className="w-4 h-4 text-muted-foreground" />}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AppShell>
  );
}
