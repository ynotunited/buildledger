"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Plus, Trash2, CreditCard, Download, CheckCircle, Clock, AlertTriangle } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { extractCollection } from "@/lib/api";
import Link from "next/link";

interface Invoice {
  id: number;
  invoice_number: string;
  client: { name: string };
  status: string;
  issue_date: string;
  due_date: string;
  total: string;
}

const currencyFormatter = new Intl.NumberFormat("en-NG", {
  style: "currency",
  currency: "NGN",
  maximumFractionDigits: 0,
});

export default function InvoicesPage() {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchInvoices = async () => {
    try {
      const response = await axiosInstance.get("/invoices");
      setInvoices(extractCollection<Invoice>(response.data));
    } catch (error) {
      console.error("Error fetching invoices", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadInvoices = async () => {
      try {
        const response = await axiosInstance.get("/invoices");
        if (mounted) {
          setInvoices(extractCollection<Invoice>(response.data));
        }
      } catch (error) {
        console.error("Error fetching invoices", error);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadInvoices();
    return () => {
      mounted = false;
    };
  }, []);

  const downloadPdf = async (id: number, number: string) => {
    try {
      const response = await axiosInstance.get(`/invoices/${id}/pdf`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${number}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error("Error downloading PDF", error);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Paid': return <CheckCircle className="w-4 h-4 text-green-500 mr-1" />;
      case 'Overdue': return <AlertTriangle className="w-4 h-4 text-destructive mr-1" />;
      case 'Sent': return <CreditCard className="w-4 h-4 text-blue-500 mr-1" />;
      default: return <Clock className="w-4 h-4 text-orange-500 mr-1" />;
    }
  };

  const deleteInvoice = async (id: number) => {
    try {
      await axiosInstance.delete(`/invoices/${id}`);
      await fetchInvoices();
    } catch (error) {
      console.error("Error deleting invoice", error);
    }
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 rounded-[1.75rem] border border-emerald-100 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Invoices</p>
            <h1 className="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
              Manage billing and collect payments.
            </h1>
            <p className="mt-2 text-sm leading-6 text-slate-600 sm:text-base">
              Keep your invoices in one readable queue with the same card rhythm used across the dashboard.
            </p>
          </div>
          <Link href="/invoices/create">
            <Button size="sm" className="rounded-full bg-emerald-600 text-white hover:bg-emerald-500">
              <Plus className="mr-2 h-4 w-4" />
              New invoice
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="rounded-[1.5rem] border border-emerald-100 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">Loading invoices...</div>
        ) : invoices.length === 0 ? (
          <div className="rounded-[1.5rem] border border-dashed border-emerald-100 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
            No invoices found. Convert a contract to an invoice to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {invoices.map((invoice) => (
              <Card key={invoice.id} className={`group relative overflow-hidden rounded-[1.5rem] border-emerald-100 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)] transition-shadow hover:shadow-[0_18px_40px_rgba(15,23,42,0.08)] ${invoice.status === 'Overdue' ? 'border-amber-300' : ''}`}>
                <CardHeader className="pb-3">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center space-x-2">
                      <div className={`flex h-10 w-10 items-center justify-center rounded-2xl ${invoice.status === 'Overdue' ? 'bg-amber-100' : 'bg-emerald-50'}`}>
                        <CreditCard className={`h-5 w-5 ${invoice.status === 'Overdue' ? 'text-amber-700' : 'text-emerald-700'}`} />
                      </div>
                      <div>
                        <Link href={`/invoices/${invoice.id}`} className="hover:underline">
                          <CardTitle className="text-lg text-slate-950">{invoice.invoice_number}</CardTitle>
                        </Link>
                        <p className="text-sm text-slate-600">{invoice.client.name}</p>
                      </div>
                    </div>
                    <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500 hover:bg-emerald-50 hover:text-emerald-700" onClick={() => void deleteInvoice(invoice.id)}>
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex justify-between items-center mt-4">
                    <div className={`flex items-center text-sm font-medium ${invoice.status === 'Overdue' ? 'text-amber-700' : 'text-slate-700'}`}>
                      {getStatusIcon(invoice.status)}
                      {invoice.status}
                    </div>
                    <div className="text-lg font-semibold tracking-tight text-slate-950">
                      {currencyFormatter.format(Number(invoice.total))}
                    </div>
                  </div>
                  <div className="flex items-center justify-between border-t border-slate-100 pt-4">
                    <span className="text-xs text-slate-500">Due: {new Date(invoice.due_date).toLocaleDateString()}</span>
                    <Button variant="outline" size="sm" onClick={() => downloadPdf(invoice.id, invoice.invoice_number)} className="rounded-full border-slate-200 bg-white hover:bg-slate-50">
                      <Download className="mr-2 h-4 w-4" />
                      PDF
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </AppShell>
  );
}
