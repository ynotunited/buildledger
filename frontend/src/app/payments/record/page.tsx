"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import AppShell from "@/components/layout/AppShell";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import axiosInstance from "@/lib/axios";
import { extractCollection } from "@/lib/api";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";

interface Invoice {
  id: number;
  invoice_number: string;
  total: string;
  status: string;
  client: { name: string };
}

export default function RecordPaymentPage() {
  const router = useRouter();
  const [invoices, setInvoices]   = useState<Invoice[]>([]);
  const [loading, setLoading]     = useState(false);
  const [initiating, setInitiating] = useState(false);
  const [form, setForm] = useState({
    invoice_id: "",
    amount:     "",
    notes:      "",
    paid_at:    "",
    gateway:    "manual" as "manual" | "paystack" | "flutterwave",
  });

  useEffect(() => {
    axiosInstance.get("/invoices")
      .then((r) => setInvoices(
        extractCollection<Invoice>(r.data).filter((inv) => inv.status !== "Paid")
      ))
      .catch(console.error);
  }, []);

  const selectedInvoice = invoices.find((inv) => String(inv.id) === form.invoice_id);

  const handleManualSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const idempotencyKey = getOrCreateIdempotencyKey("manual-payment", {
        invoice_id: form.invoice_id,
        amount: form.amount,
        notes: form.notes,
        paid_at: form.paid_at,
      });

      await axiosInstance.post("/payments/manual", {
        invoice_id: Number(form.invoice_id),
        amount:     Number(form.amount),
        notes:      form.notes || null,
        paid_at:    form.paid_at || null,
        idempotency_key: idempotencyKey,
      }, {
        headers: {
          "Idempotency-Key": idempotencyKey,
        },
      });
      router.push("/payments");
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleGatewayInitiate = async () => {
    if (!form.invoice_id) return;
    setInitiating(true);
    try {
      const idempotencyKey = getOrCreateIdempotencyKey("invoice-payment", {
        invoice_id: form.invoice_id,
        gateway: form.gateway,
      });

      const endpoint = form.gateway === "paystack"
        ? "/payments/initiate/paystack"
        : "/payments/initiate/flutterwave";

      const res = await axiosInstance.post(endpoint, {
        invoice_id: Number(form.invoice_id),
        idempotency_key: idempotencyKey,
      }, {
        headers: {
          "Idempotency-Key": idempotencyKey,
        },
      });

      const url = form.gateway === "paystack"
        ? res.data.authorization_url
        : res.data.link;

      if (url) {
        window.location.href = url;
        return;
      }

      if (res.data.idempotency_status === "processing") {
        window.alert("The payment request is still processing. Please retry the same action in a moment.");
      }
    } catch (err) {
      console.error(err);
    } finally {
      setInitiating(false);
    }
  };

  return (
    <AppShell>
      <div className="max-w-2xl mx-auto space-y-6">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Record Payment</h1>
          <p className="text-muted-foreground text-sm">Log a payment or send a gateway link to your client.</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Payment Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-5">
            {/* Invoice selector */}
            <div className="space-y-1.5">
              <Label htmlFor="invoice_id">Invoice *</Label>
              <select
                id="invoice_id"
                required
                value={form.invoice_id}
                onChange={(e) => {
                  const inv = invoices.find((i) => String(i.id) === e.target.value);
                  setForm({ ...form, invoice_id: e.target.value, amount: inv ? inv.total : "" });
                }}
                className="w-full h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option value="">Select an invoice</option>
                {invoices.map((inv) => (
                  <option key={inv.id} value={inv.id}>
                    {inv.invoice_number} — {inv.client.name} (₦{Number(inv.total).toLocaleString()})
                  </option>
                ))}
              </select>
            </div>

            {/* Gateway selector */}
            <div className="space-y-1.5">
              <Label>Payment Method</Label>
              <div className="grid grid-cols-3 gap-2">
                {(["manual", "paystack", "flutterwave"] as const).map((gw) => (
                  <button
                    key={gw}
                    type="button"
                    onClick={() => setForm({ ...form, gateway: gw })}
                    className={`py-2 px-3 rounded-lg border text-sm font-medium capitalize transition-colors ${
                      form.gateway === gw
                        ? "border-primary bg-primary/10 text-primary"
                        : "border-border text-muted-foreground hover:border-foreground/30"
                    }`}
                  >
                    {gw}
                  </button>
                ))}
              </div>
            </div>

            {form.gateway === "manual" ? (
              <form onSubmit={handleManualSubmit} className="space-y-4">
                <div className="space-y-1.5">
                  <Label htmlFor="amount">Amount (₦) *</Label>
                  <Input
                    id="amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    placeholder="0.00"
                    value={form.amount}
                    onChange={(e) => setForm({ ...form, amount: e.target.value })}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="paid_at">Date Paid</Label>
                  <Input
                    id="paid_at"
                    type="date"
                    value={form.paid_at}
                    onChange={(e) => setForm({ ...form, paid_at: e.target.value })}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="notes">Notes</Label>
                  <textarea
                    id="notes"
                    rows={2}
                    placeholder="Bank transfer, cheque, etc."
                    value={form.notes}
                    onChange={(e) => setForm({ ...form, notes: e.target.value })}
                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                  />
                </div>
                <div className="flex gap-3 pt-1">
                  <Button type="submit" disabled={loading || !form.invoice_id} className="flex-1">
                    {loading ? "Saving..." : "Record Payment"}
                  </Button>
                  <Button type="button" variant="outline" onClick={() => router.back()}>
                    Cancel
                  </Button>
                </div>
              </form>
            ) : (
              <div className="space-y-4">
                {selectedInvoice && (
                  <div className="p-3 rounded-lg bg-secondary/40 text-sm">
                    <p className="font-medium">{selectedInvoice.invoice_number}</p>
                    <p className="text-muted-foreground">
                      {selectedInvoice.client.name} · ₦{Number(selectedInvoice.total).toLocaleString()}
                    </p>
                  </div>
                )}
                <p className="text-sm text-muted-foreground">
                  A payment link will be generated and you can share it with your client, or redirect them directly.
                </p>
                <div className="flex gap-3">
                  <Button
                    onClick={handleGatewayInitiate}
                    disabled={initiating || !form.invoice_id}
                    className="flex-1 capitalize"
                  >
                    {initiating ? "Generating link..." : `Pay with ${form.gateway}`}
                  </Button>
                  <Button type="button" variant="outline" onClick={() => router.back()}>
                    Cancel
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
