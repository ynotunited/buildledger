"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Copy, Pencil, Plus, Trash2, Briefcase, CheckCircle, Clock } from "lucide-react";
import axiosInstance from "@/lib/axios";
import Link from "next/link";
import { extractCollection } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

interface Contract {
  id: number;
  title: string;
  client: { name: string };
  status: string;
  public_signing_path: string | null;
  signing_link_expires_at: string | null;
  created_at: string;
}

export default function ContractsPage() {
  const [contracts, setContracts] = useState<Contract[]>([]);
  const [loading, setLoading] = useState(true);
  const [clients, setClients] = useState<{ id: number; name: string }[]>([]);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ client_id: "", title: "", body_content: "" });

  const fetchContracts = async () => {
    try {
      const response = await axiosInstance.get("/contracts");
      setContracts(extractCollection<Contract>(response.data));
    } catch (error) {
      console.error("Error fetching contracts", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadPage = async () => {
      try {
        const [contractsResponse, clientsResponse] = await Promise.all([
          axiosInstance.get("/contracts"),
          axiosInstance.get("/clients"),
        ]);

        if (mounted) {
          setContracts(extractCollection<Contract>(contractsResponse.data));
          setClients(extractCollection<{ id: number; name: string }>(clientsResponse.data));
        }
      } catch (error) {
        console.error("Error loading contracts page", error);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadPage();
    return () => {
      mounted = false;
    };
  }, []);

  const createContract = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await axiosInstance.post("/contracts", {
        client_id: Number(form.client_id),
        title: form.title,
        body_content: form.body_content || null,
      });
      setForm({ client_id: "", title: "", body_content: "" });
      await fetchContracts();
    } catch (error) {
      console.error("Error creating contract", error);
    } finally {
      setSaving(false);
    }
  };

  const deleteContract = async (id: number) => {
    try {
      await axiosInstance.delete(`/contracts/${id}`);
      await fetchContracts();
    } catch (error) {
      console.error("Error deleting contract", error);
    }
  };

  const copySigningLink = async (contract: Contract) => {
    try {
      let signingPath = contract.public_signing_path;

      if (!signingPath) {
        const response = await axiosInstance.put(`/contracts/${contract.id}`, {
          status: "Sent",
        });

        const updatedContract = response.data?.data ?? response.data;
        signingPath = updatedContract.public_signing_path ?? null;
        await fetchContracts();
      }

      if (!signingPath) {
        throw new Error("Signing link unavailable.");
      }

      await navigator.clipboard.writeText(`${window.location.origin}${signingPath}`);
    } catch (error) {
      console.error("Error copying signing link", error);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Signed': return <CheckCircle className="w-4 h-4 text-green-500 mr-1" />;
      case 'Sent': return <Briefcase className="w-4 h-4 text-blue-500 mr-1" />;
      default: return <Clock className="w-4 h-4 text-orange-500 mr-1" />;
    }
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex flex-col space-y-1">
            <h1 className="text-2xl font-bold tracking-tight">Contracts</h1>
            <p className="text-muted-foreground text-sm">Manage and track client contracts.</p>
          </div>
          <Link href="/proposals">
            <Button variant="outline" size="sm">View proposals</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Create Contract</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={createContract} className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="client_id">Client</Label>
                <select
                  id="client_id"
                  value={form.client_id}
                  onChange={(e) => setForm({ ...form, client_id: e.target.value })}
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  required
                >
                  <option value="">Select client</option>
                  {clients.map((client) => (
                    <option key={client.id} value={client.id}>{client.name}</option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input id="title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
              </div>
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="body_content">Body</Label>
                <textarea
                  id="body_content"
                  rows={4}
                  value={form.body_content}
                  onChange={(e) => setForm({ ...form, body_content: e.target.value })}
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  placeholder="Key contract terms..."
                />
              </div>
              <div className="md:col-span-2">
                <Button type="submit" disabled={saving}>
                  <Plus className="w-4 h-4 mr-2" />
                  {saving ? "Creating..." : "Create Contract"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {loading ? (
          <div className="flex items-center justify-center p-8">Loading contracts...</div>
        ) : contracts.length === 0 ? (
          <div className="text-center p-8 text-muted-foreground border rounded-lg border-dashed">
            No contracts found. Convert a proposal to a contract to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {contracts.map((contract) => (
              <Card key={contract.id} className="relative overflow-hidden group hover:shadow-md transition-shadow">
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="flex items-center space-x-2">
                      <div className="w-10 h-10 rounded-full bg-secondary flex items-center justify-center">
                        <Briefcase className="w-5 h-5 text-muted-foreground" />
                      </div>
                      <div>
                        <Link href={`/contracts/${contract.id}`} className="hover:underline">
                          <CardTitle className="text-lg">{contract.title}</CardTitle>
                        </Link>
                        <p className="text-sm text-muted-foreground">{contract.client.name}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <Link href={`/contracts/${contract.id}`}>
                        <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground">
                          <Pencil className="w-4 h-4" />
                        </Button>
                      </Link>
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground" onClick={() => void deleteContract(contract.id)}>
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-between items-center mt-4">
                    <div className="flex items-center text-sm font-medium">
                      {getStatusIcon(contract.status)}
                      {contract.status}
                    </div>
                  </div>
                  <div className="mt-4 pt-4 border-t border-border flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Created: {new Date(contract.created_at).toLocaleDateString()}</span>
                    {contract.status !== 'Signed' && (
                      <Button variant="outline" size="sm" onClick={() => void copySigningLink(contract)}>
                        <Copy className="w-4 h-4 mr-2" />
                        {contract.public_signing_path ? "Copy Link" : "Send Link"}
                      </Button>
                    )}
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
