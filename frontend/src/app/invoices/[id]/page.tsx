"use client";

import { useState, useEffect } from "react";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { ArrowLeft, Download, CreditCard, Send } from "lucide-react";
import Link from "next/link";
import axiosInstance from "@/lib/axios";
import { extractResource } from "@/lib/api";

type InvoiceItem = {
  id: number;
  name: string;
  description: string | null;
  quantity: number;
  unit_price: string | number;
  total: string | number;
};

type Invoice = {
  id: number;
  invoice_number: string;
  status: string;
  issue_date: string;
  due_date: string;
  subtotal: string | number;
  tax: string | number;
  discount: string | number;
  total: string | number;
  notes: string | null;
  public_payment_path: string | null;
  client: {
    name: string;
    email?: string | null;
    phone?: string | null;
  };
  items: InvoiceItem[];
};

export default function InvoiceDetailPage({ params }: { params: { id: string } }) {
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [isUpdating, setIsUpdating] = useState(false);
  const [isSendingLink, setIsSendingLink] = useState(false);

  useEffect(() => {
    const fetchInvoice = async () => {
      try {
        const response = await axiosInstance.get(`/invoices/${params.id}`);
        setInvoice(extractResource<Invoice>(response.data));
      } catch (error) {
        console.error("Error fetching invoice", error);
      } finally {
        setLoading(false);
      }
    };
    void fetchInvoice();
  }, [params.id]);

  const downloadPdf = async () => {
    if (!invoice) {
      return;
    }

    try {
      const response = await axiosInstance.get(`/invoices/${params.id}/pdf`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${invoice.invoice_number}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error("Error downloading PDF", error);
    }
  };

  const sendPaymentLink = async () => {
    if (!invoice) {
      return;
    }

    setIsSendingLink(true);

    try {
      const response = await axiosInstance.post(`/invoices/${params.id}/send-payment-link`);
      const updatedInvoice = extractResource<Invoice>(response.data);
      const paymentPath = updatedInvoice.public_payment_path;

      if (!paymentPath) {
        throw new Error("Payment link unavailable.");
      }

      setInvoice(updatedInvoice);
      await navigator.clipboard.writeText(`${window.location.origin}${paymentPath}`);
    } catch (error) {
      console.error("Error copying payment link", error);
    } finally {
      setIsSendingLink(false);
    }
  };

  const markAsPaid = async () => {
    setIsUpdating(true);
    try {
      const response = await axiosInstance.put(`/invoices/${params.id}`, {
        status: 'Paid'
      });
      setInvoice(extractResource<Invoice>(response.data));
    } catch (error) {
      console.error("Error marking as paid", error);
    } finally {
      setIsUpdating(false);
    }
  };

  if (loading) return <AppShell><div className="p-8 text-center">Loading invoice...</div></AppShell>;
  if (!invoice) return <AppShell><div className="p-8 text-center">Invoice not found.</div></AppShell>;

  return (
    <AppShell>
      <div className="space-y-6 max-w-4xl mx-auto">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div className="flex items-center space-x-4">
            <Link href="/invoices">
              <Button variant="ghost" size="icon" className="rounded-full">
                <ArrowLeft className="w-5 h-5" />
              </Button>
            </Link>
            <div>
              <div className="flex items-center gap-3">
                <h1 className="text-2xl font-bold tracking-tight">{invoice.invoice_number}</h1>
                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                  invoice.status === 'Paid' ? 'bg-green-500/10 text-green-500' :
                  invoice.status === 'Overdue' ? 'bg-destructive/10 text-destructive' :
                  'bg-secondary text-foreground'
                }`}>
                  {invoice.status}
                </span>
              </div>
              <p className="text-sm text-muted-foreground">Client: {invoice.client.name}</p>
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" onClick={downloadPdf}>
              <Download className="w-4 h-4 mr-2" />
              Download PDF
            </Button>
            {invoice.status !== 'Paid' && (
              <Button variant="outline" onClick={() => void sendPaymentLink()} disabled={isSendingLink}>
                <Send className="w-4 h-4 mr-2" />
                {isSendingLink ? "Sending..." : invoice.public_payment_path ? "Resend Payment Link" : "Send Payment Link"}
              </Button>
            )}
            {invoice.status !== 'Paid' && (
              <Button onClick={markAsPaid} disabled={isUpdating} className="bg-green-600 hover:bg-green-700 text-white">
                <CreditCard className="w-4 h-4 mr-2" />
                {isUpdating ? "Processing..." : "Mark as Paid"}
              </Button>
            )}
          </div>
        </div>

        <Card className="p-2 md:p-6 shadow-sm border-muted">
          <CardHeader className="flex flex-col md:flex-row md:justify-between items-start gap-4">
            <div>
                <h2 className="text-3xl font-bold tracking-tighter text-primary">INVOICE</h2>
                <p className="text-sm text-muted-foreground mt-1"># {invoice.invoice_number}</p>
              </div>
              <div className="text-left md:text-right text-sm space-y-1">
                <p><span className="font-medium text-muted-foreground">Date of Issue:</span> {new Date(invoice.issue_date).toLocaleDateString()}</p>
                <p><span className="font-medium text-muted-foreground">Due Date:</span> {new Date(invoice.due_date).toLocaleDateString()}</p>
              </div>
            </CardHeader>
            <CardContent className="space-y-8 mt-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-2">
                <p className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Billed To</p>
                <div className="text-sm">
                  <p className="font-bold text-base">{invoice.client.name}</p>
                  <p>{invoice.client.email}</p>
                  {invoice.client.phone && <p>{invoice.client.phone}</p>}
                </div>
              </div>
              <div className="space-y-2 md:text-right">
                <p className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Amount Due</p>
                <p className="text-4xl font-bold">₦{Number(invoice.total).toLocaleString("en-NG")}</p>
              </div>
            </div>

            <div className="border border-border rounded-lg overflow-hidden mt-8">
              <table className="w-full text-sm text-left">
                <thead className="bg-secondary/50 text-muted-foreground uppercase text-xs font-semibold">
                  <tr>
                    <th className="px-4 py-3">Description</th>
                    <th className="px-4 py-3 text-right w-24">Qty</th>
                    <th className="px-4 py-3 text-right w-32">Price</th>
                    <th className="px-4 py-3 text-right w-32">Total</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {invoice.items.map((item) => (
                    <tr key={item.id} className="bg-card hover:bg-muted/30 transition-colors">
                      <td className="px-4 py-4">
                        <p className="font-medium">{item.name}</p>
                        {item.description && <p className="text-muted-foreground text-xs mt-1">{item.description}</p>}
                      </td>
                      <td className="px-4 py-4 text-right">{item.quantity}</td>
                      <td className="px-4 py-4 text-right">₦{Number(item.unit_price).toLocaleString("en-NG")}</td>
                      <td className="px-4 py-4 text-right font-medium">₦{Number(item.total).toLocaleString("en-NG")}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="flex justify-end">
              <div className="w-full md:w-64 space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span className="font-medium">₦{Number(invoice.subtotal).toLocaleString("en-NG")}</span>
                </div>
                {Number(invoice.discount) > 0 && (
                  <div className="flex justify-between text-destructive">
                    <span>Discount</span>
                    <span className="font-medium">-₦{Number(invoice.discount).toLocaleString("en-NG")}</span>
                  </div>
                )}
                {Number(invoice.tax) > 0 && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Tax</span>
                    <span className="font-medium">₦{Number(invoice.tax).toLocaleString("en-NG")}</span>
                  </div>
                )}
                <div className="flex justify-between font-bold text-lg pt-4 border-t border-border">
                  <span>Total</span>
                  <span>₦{Number(invoice.total).toLocaleString("en-NG")}</span>
                </div>
              </div>
            </div>

            {invoice.notes && (
              <div className="pt-8 mt-8 border-t border-border">
                <p className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-2">Notes</p>
                <p className="text-sm text-muted-foreground">{invoice.notes}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
