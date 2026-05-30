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
        <div className="flex items-center justify-between">
          <div className="flex flex-col space-y-1">
            <h1 className="text-2xl font-bold tracking-tight">Invoices</h1>
            <p className="text-muted-foreground text-sm">Manage billing and collect payments.</p>
          </div>
          <Link href="/invoices/create">
            <Button size="sm">
              <Plus className="w-4 h-4 mr-2" />
              New Invoice
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="flex items-center justify-center p-8">Loading invoices...</div>
        ) : invoices.length === 0 ? (
          <div className="text-center p-8 text-muted-foreground border rounded-lg border-dashed">
            No invoices found. Convert a contract to an invoice to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {invoices.map((invoice) => (
              <Card key={invoice.id} className={`relative overflow-hidden group hover:shadow-md transition-shadow ${invoice.status === 'Overdue' ? 'border-destructive/50 shadow-destructive/10' : ''}`}>
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center space-x-2">
                      <div className={`w-10 h-10 rounded-full flex items-center justify-center ${invoice.status === 'Overdue' ? 'bg-destructive/10' : 'bg-secondary'}`}>
                        <CreditCard className={`w-5 h-5 ${invoice.status === 'Overdue' ? 'text-destructive' : 'text-muted-foreground'}`} />
                      </div>
                      <div>
                        <Link href={`/invoices/${invoice.id}`} className="hover:underline">
                          <CardTitle className="text-lg">{invoice.invoice_number}</CardTitle>
                        </Link>
                        <p className="text-sm text-muted-foreground">{invoice.client.name}</p>
                      </div>
                    </div>
                    <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground" onClick={() => void deleteInvoice(invoice.id)}>
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-between items-center mt-4">
                    <div className={`flex items-center text-sm font-medium ${invoice.status === 'Overdue' ? 'text-destructive' : ''}`}>
                      {getStatusIcon(invoice.status)}
                      {invoice.status}
                    </div>
                    <div className="font-bold text-lg">
                      ${Number(invoice.total).toLocaleString()}
                    </div>
                  </div>
                  <div className="mt-4 pt-4 border-t border-border flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Due: {new Date(invoice.due_date).toLocaleDateString()}</span>
                    <Button variant="outline" size="sm" onClick={() => downloadPdf(invoice.id, invoice.invoice_number)}>
                      <Download className="w-4 h-4 mr-2" />
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
