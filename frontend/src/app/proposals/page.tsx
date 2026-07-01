"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Plus, Trash2, FileText, Download, CheckCircle, Clock, FileUp } from "lucide-react";
import axiosInstance from "@/lib/axios";
import Link from "next/link";
import { extractCollection } from "@/lib/api";

interface Proposal {
  id: number;
  title: string;
  client: { name: string };
  status: string;
  issue_date: string;
  total: string;
}

export default function ProposalsPage() {
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchProposals = async () => {
    try {
      const response = await axiosInstance.get("/proposals");
      setProposals(extractCollection<Proposal>(response.data));
    } catch (error) {
      console.error("Error fetching proposals", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadProposals = async () => {
      try {
        const response = await axiosInstance.get("/proposals");
        if (mounted) {
          setProposals(extractCollection<Proposal>(response.data));
        }
      } catch (error) {
        console.error("Error fetching proposals", error);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadProposals();
    return () => {
      mounted = false;
    };
  }, []);

  const downloadPdf = async (id: number) => {
    try {
      const response = await axiosInstance.get(`/proposals/${id}/pdf`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `proposal-${id}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error("Error downloading PDF", error);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Approved': return <CheckCircle className="w-4 h-4 text-green-500 mr-1" />;
      case 'Sent': return <FileText className="w-4 h-4 text-blue-500 mr-1" />;
      default: return <Clock className="w-4 h-4 text-orange-500 mr-1" />;
    }
  };

  const convertToContract = async (id: number) => {
    try {
      await axiosInstance.post(`/proposals/${id}/convert-to-contract`);
      await fetchProposals();
    } catch (error) {
      console.error("Error converting proposal", error);
    }
  };

  const deleteProposal = async (id: number) => {
    try {
      await axiosInstance.delete(`/proposals/${id}`);
      await fetchProposals();
    } catch (error) {
      console.error("Error deleting proposal", error);
    }
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 rounded-[1.75rem] border border-emerald-100 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] lg:flex-row lg:items-end lg:justify-between">
          <div className="flex flex-col space-y-1">
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Proposals</p>
            <h1 className="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Manage your client proposals and estimates.</h1>
            <p className="text-sm text-slate-600">Keep drafts, approvals, and PDF exports in one readable workspace.</p>
          </div>
          <Link href="/proposals/create">
            <Button size="sm" className="rounded-full bg-emerald-600 text-white hover:bg-emerald-500">
              <Plus className="w-4 h-4 mr-2" />
              Create Proposal
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="rounded-[1.5rem] border border-emerald-100 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">Loading proposals...</div>
        ) : proposals.length === 0 ? (
          <div className="rounded-[1.5rem] border border-dashed border-emerald-100 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
            No proposals found. Create one to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {proposals.map((proposal) => (
              <Card key={proposal.id} className="group relative overflow-hidden rounded-[1.5rem] border-emerald-100 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)] transition-shadow hover:shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center space-x-2">
                      <div className="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center">
                        <FileText className="w-5 h-5 text-emerald-700" />
                      </div>
                      <div>
                        <CardTitle className="text-lg text-slate-950">{proposal.title}</CardTitle>
                        <p className="text-sm text-slate-500">{proposal.client.name}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500 hover:bg-emerald-50 hover:text-emerald-700" onClick={() => void deleteProposal(proposal.id)}>
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-between items-center mt-4">
                    <div className="flex items-center text-sm font-medium text-slate-700">
                      {getStatusIcon(proposal.status)}
                      {proposal.status}
                    </div>
                    <div className="text-lg font-semibold tracking-tight text-slate-950">
                      ${Number(proposal.total).toLocaleString()}
                    </div>
                  </div>
                  <div className="mt-4 flex items-center justify-between border-t border-emerald-100 pt-4">
                    <span className="text-xs text-slate-500">Issued: {new Date(proposal.issue_date).toLocaleDateString()}</span>
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" onClick={() => void convertToContract(proposal.id)} className="rounded-full border-emerald-200 bg-white hover:bg-emerald-50">
                        <FileUp className="w-4 h-4 mr-2" />
                        Contract
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => downloadPdf(proposal.id)} className="rounded-full border-emerald-200 bg-white hover:bg-emerald-50">
                        <Download className="w-4 h-4 mr-2" />
                        PDF
                      </Button>
                    </div>
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
