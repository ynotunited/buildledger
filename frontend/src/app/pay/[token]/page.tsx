"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import axiosInstance from "@/lib/axios";
import { extractResource } from "@/lib/api";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";
import { Button } from "@/components/ui/button";
import BrandLogo from "@/components/brand/BrandLogo";

type PublicInvoiceItem = {
  name: string;
  description: string | null;
  quantity: number;
  unit_price: string | number;
  total: string | number;
};

type PublicInvoice = {
  invoice_number: string;
  status: string;
  issue_date: string;
  due_date: string;
  subtotal: string | number;
  tax: string | number;
  discount: string | number;
  total: string | number;
  notes: string | null;
  client: {
    name: string;
    email: string | null;
    phone: string | null;
  };
  company: {
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    website: string | null;
    tax_id: string | null;
    logo_url: string | null;
  } | null;
  items: PublicInvoiceItem[];
  public_payment_path: string | null;
  public_payment_link_expires_at: string | null;
};

export default function PublicInvoicePage({ params }: { params: { token: string } }) {
  const router = useRouter();
  const [invoice, setInvoice] = useState<PublicInvoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    let mounted = true;

    const loadInvoice = async () => {
      try {
        const response = await axiosInstance.get(`/public/invoices/${params.token}`);
        if (mounted) {
          setInvoice(extractResource<PublicInvoice>(response.data));
        }
      } catch (error) {
        console.error("Error loading public invoice", error);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadInvoice();

    return () => {
      mounted = false;
    };
  }, [params.token]);

  const beginPayment = async (selectedGateway: "paystack" | "flutterwave") => {
    setProcessing(true);
    try {
      const idempotencyKey = getOrCreateIdempotencyKey("public-invoice-pay", {
        token: params.token,
        gateway: selectedGateway,
      });

      const response = await axiosInstance.post(`/public/invoices/${params.token}/pay`, {
        gateway: selectedGateway,
        idempotency_key: idempotencyKey,
      }, {
        headers: {
          "Idempotency-Key": idempotencyKey,
        },
      });

      const url = selectedGateway === "paystack"
        ? response.data.authorization_url
        : response.data.link;

      if (url) {
        window.location.assign(url);
        return;
      }

      if (response.data.idempotency_status === "processing") {
        window.alert("Your payment request is still processing. Please retry the same action in a moment.");
      }
    } catch (error) {
      console.error("Error starting public invoice payment", error);
    } finally {
      setProcessing(false);
    }
  };

  const formatCurrency = (amount: string | number) => `₦${Number(amount).toLocaleString("en-NG", { minimumFractionDigits: 2 })}`;

  if (loading) {
    return (
      <div className="min-h-screen bg-background px-4 py-10 text-foreground">
        <div className="mx-auto max-w-4xl rounded-[2rem] border border-white/10 bg-card p-8">
          Loading invoice...
        </div>
      </div>
    );
  }

  if (!invoice) {
    return (
      <div className="min-h-screen bg-background px-4 py-10 text-foreground">
        <div className="mx-auto max-w-4xl rounded-[2rem] border border-white/10 bg-card p-8">
          <h1 className="text-2xl font-semibold">Invoice not found</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            The payment link may be invalid, expired, or not yet sent.
          </p>
        </div>
      </div>
    );
  }

  const isPaid = invoice.status === "Paid";
  const isExpired = invoice.status !== "Sent" && !invoice.public_payment_path;

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),linear-gradient(180deg,#08111f_0%,#0b1020_100%)] px-4 py-8 text-white">
      <div className="mx-auto max-w-5xl">
        <div className="mb-6 flex items-center justify-between text-sm text-white/70">
          <BrandLogo href="/" variant="white" className="h-7 w-auto" priority />
          <span>Secure invoice payment</span>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
          <section className="rounded-[2rem] border border-white/10 bg-white/8 p-6 shadow-2xl shadow-black/20 backdrop-blur-xl md:p-8">
            <div className="flex items-start justify-between gap-4">
              <div>
                {invoice.company?.logo_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={invoice.company.logo_url}
                    alt={`${invoice.company.name} logo`}
                    className="mb-4 h-14 w-14 rounded-2xl object-cover border border-white/10 bg-white/10"
                  />
                ) : null}
                <p className="text-xs uppercase tracking-[0.35em] text-cyan-200/80">Invoice</p>
                <h1 className="mt-2 text-3xl font-semibold tracking-tight">{invoice.invoice_number}</h1>
                <p className="mt-2 text-sm text-white/70">
                  {invoice.company?.name ?? "Your company"} has requested payment.
                </p>
              </div>
              <span className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] ${
                isPaid ? "bg-emerald-500/15 text-emerald-300" : "bg-amber-500/15 text-amber-200"
              }`}>
                {invoice.status}
              </span>
            </div>

            <div className="mt-8 grid gap-4 md:grid-cols-2">
              <div className="rounded-2xl border border-white/10 bg-black/20 p-4">
                <p className="text-xs uppercase tracking-[0.25em] text-white/55">Billed to</p>
                <p className="mt-2 text-lg font-medium">{invoice.client.name}</p>
                <p className="mt-1 text-sm text-white/70">{invoice.client.email}</p>
                {invoice.client.phone ? <p className="text-sm text-white/70">{invoice.client.phone}</p> : null}
              </div>
              <div className="rounded-2xl border border-white/10 bg-black/20 p-4">
                <p className="text-xs uppercase tracking-[0.25em] text-white/55">Due date</p>
                <p className="mt-2 text-lg font-medium">{new Date(invoice.due_date).toLocaleDateString()}</p>
                <p className="mt-1 text-sm text-white/70">Issue date: {new Date(invoice.issue_date).toLocaleDateString()}</p>
              </div>
            </div>

            <div className="mt-8 overflow-hidden rounded-2xl border border-white/10">
              <table className="w-full text-left text-sm">
                <thead className="bg-white/8 text-xs uppercase tracking-[0.2em] text-white/55">
                  <tr>
                    <th className="px-4 py-3">Item</th>
                    <th className="px-4 py-3 text-right">Qty</th>
                    <th className="px-4 py-3 text-right">Price</th>
                    <th className="px-4 py-3 text-right">Total</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/10 bg-black/15">
                  {invoice.items.map((item, index) => (
                    <tr key={`${item.name}-${index}`}>
                      <td className="px-4 py-4">
                        <p className="font-medium text-white">{item.name}</p>
                        {item.description ? <p className="mt-1 text-xs text-white/60">{item.description}</p> : null}
                      </td>
                      <td className="px-4 py-4 text-right text-white/80">{item.quantity}</td>
                      <td className="px-4 py-4 text-right text-white/80">{formatCurrency(item.unit_price)}</td>
                      <td className="px-4 py-4 text-right font-medium">{formatCurrency(item.total)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {invoice.notes ? (
              <div className="mt-6 rounded-2xl border border-white/10 bg-black/20 p-4">
                <p className="text-xs uppercase tracking-[0.25em] text-white/55">Notes</p>
                <p className="mt-2 text-sm text-white/75">{invoice.notes}</p>
              </div>
            ) : null}
          </section>

          <aside className="space-y-4 rounded-[2rem] border border-white/10 bg-white/8 p-6 shadow-2xl shadow-black/20 backdrop-blur-xl">
            <div>
              <p className="text-xs uppercase tracking-[0.35em] text-cyan-200/80">Amount due</p>
              <p className="mt-2 text-4xl font-semibold">{formatCurrency(invoice.total)}</p>
              <p className="mt-2 text-sm text-white/60">
                {isExpired ? "This payment link is no longer active." : "Choose a payment method to complete the invoice."}
              </p>
            </div>

            {isPaid ? (
              <div className="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-100">
                This invoice has already been paid. Thank you.
              </div>
            ) : (
              <div className="space-y-3">
                <Button
                  className="w-full justify-between rounded-2xl bg-white px-5 py-4 text-sm font-semibold text-slate-950 hover:bg-white/90"
                  onClick={() => void beginPayment("paystack")}
                  disabled={processing || isExpired}
                >
                  <span>Pay with Paystack</span>
                  <span>Instant</span>
                </Button>
                <Button
                  className="w-full justify-between rounded-2xl border border-white/15 bg-transparent px-5 py-4 text-sm font-semibold text-white hover:bg-white/10"
                  onClick={() => void beginPayment("flutterwave")}
                  disabled={processing || isExpired}
                  variant="outline"
                >
                  <span>Pay with Flutterwave</span>
                  <span>Instant</span>
                </Button>
              </div>
            )}

            <div className="rounded-2xl border border-white/10 bg-black/15 p-4 text-sm text-white/70">
              {invoice.company ? (
                <>
                  <p className="font-medium text-white">{invoice.company.name}</p>
                  {invoice.company.email ? <p>{invoice.company.email}</p> : null}
                  {invoice.company.phone ? <p>{invoice.company.phone}</p> : null}
                  {invoice.company.website ? <p>{invoice.company.website}</p> : null}
                </>
              ) : (
                <p>Payment handled by the invoice owner.</p>
              )}
            </div>

            <Button
              variant="ghost"
              className="w-full text-white/80 hover:bg-white/10 hover:text-white"
              onClick={() => router.back()}
            >
              Back
            </Button>
          </aside>
        </div>
      </div>
    </div>
  );
}
