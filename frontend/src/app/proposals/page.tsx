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
        <div className="flex items-center justify-between">
          <div className="flex flex-col space-y-1">
            <h1 className="text-2xl font-bold tracking-tight">Proposals</h1>
            <p className="text-muted-foreground text-sm">Manage your client proposals and estimates.</p>
          </div>
          <Link href="/proposals/create">
            <Button size="sm">
              <Plus className="w-4 h-4 mr-2" />
              Create Proposal
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="flex items-center justify-center p-8">Loading proposals...</div>
        ) : proposals.length === 0 ? (
          <div className="text-center p-8 text-muted-foreground border rounded-lg border-dashed">
            No proposals found. Create one to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {proposals.map((proposal) => (
              <Card key={proposal.id} className="relative overflow-hidden group hover:shadow-md transition-shadow">
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center space-x-2">
                      <div className="w-10 h-10 rounded-full bg-secondary flex items-center justify-center">
                        <FileText className="w-5 h-5 text-muted-foreground" />
                      </div>
                      <div>
                        <CardTitle className="text-lg">{proposal.title}</CardTitle>
                        <p className="text-sm text-muted-foreground">{proposal.client.name}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground" onClick={() => void deleteProposal(proposal.id)}>
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-between items-center mt-4">
                    <div className="flex items-center text-sm font-medium">
                      {getStatusIcon(proposal.status)}
                      {proposal.status}
                    </div>
                    <div className="font-bold text-lg">
                      ${Number(proposal.total).toLocaleString()}
                    </div>
                  </div>
                  <div className="mt-4 pt-4 border-t border-border flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Issued: {new Date(proposal.issue_date).toLocaleDateString()}</span>
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" onClick={() => void convertToContract(proposal.id)}>
                        <FileUp className="w-4 h-4 mr-2" />
                        Contract
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => downloadPdf(proposal.id)}>
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
