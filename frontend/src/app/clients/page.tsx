"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Plus, Pencil, Trash2, Building } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { extractCollection } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/components/auth/AuthProvider";

interface Client {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  company: string | null;
  status: string;
  owner_name?: string | null;
  owner_email?: string | null;
}

const emptyForm = {
  name: "",
  email: "",
  phone: "",
  company: "",
  status: "Lead",
};

export default function ClientsPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === "admin";
  const [clients, setClients] = useState<Client[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyForm);

  const fetchClients = async () => {
    try {
      const response = await axiosInstance.get("/clients");
      setClients(extractCollection<Client>(response.data));
    } catch (error) {
      console.error("Error fetching clients", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadClients = async () => {
      try {
        const response = await axiosInstance.get("/clients");
        if (mounted) {
          setClients(extractCollection<Client>(response.data));
        }
      } catch (error) {
        console.error("Error fetching clients", error);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadClients();
    return () => {
      mounted = false;
    };
  }, []);

  const resetForm = () => {
    setForm(emptyForm);
    setEditingId(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);

    try {
      if (editingId) {
        await axiosInstance.put(`/clients/${editingId}`, form);
      } else {
        await axiosInstance.post("/clients", form);
      }

      resetForm();
      await fetchClients();
    } catch (error) {
      console.error("Error saving client", error);
    } finally {
      setSaving(false);
    }
  };

  const startEdit = (client: Client) => {
    setEditingId(client.id);
    setForm({
      name: client.name,
      email: client.email ?? "",
      phone: client.phone ?? "",
      company: client.company ?? "",
      status: client.status,
    });
  };

  const deleteClient = async (id: number) => {
    try {
      await axiosInstance.delete(`/clients/${id}`);
      if (editingId === id) {
        resetForm();
      }
      await fetchClients();
    } catch (error) {
      console.error("Error deleting client", error);
    }
  };

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
              Clients
            </p>
            <h1 className="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
              {isAdmin ? "All clients" : "Clients"}
            </h1>
            <p className="mt-2 text-sm leading-6 text-slate-600 sm:text-base">
              {isAdmin
                ? "View and manage clients across every workspace in the platform."
                : "Manage your leads and active clients."}
            </p>
          </div>
          <Button size="sm" onClick={resetForm} className="rounded-full bg-slate-950 px-4 text-white hover:bg-slate-800">
            <Plus className="mr-2 h-4 w-4" />
            {editingId ? "New client" : "Add client"}
          </Button>
        </div>

        <Card className="rounded-[1.75rem] border-slate-200 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
          <CardHeader className="border-b border-slate-100 pb-4">
            <CardTitle className="text-lg text-slate-950">{editingId ? "Edit client" : "Create client"}</CardTitle>
          </CardHeader>
          <CardContent className="p-5 md:p-6">
            <form onSubmit={handleSubmit} className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="company">Company</Label>
                <Input id="company" value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">Phone</Label>
                <Input id="phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </div>
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="status">Status</Label>
                <select
                  id="status"
                  value={form.status}
                  onChange={(e) => setForm({ ...form, status: e.target.value })}
                  className="flex h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm"
                >
                  {["Lead", "Negotiation", "Active", "Completed", "Dormant"].map((status) => (
                    <option key={status} value={status}>{status}</option>
                  ))}
                </select>
              </div>
              <div className="md:col-span-2 flex gap-3">
                <Button type="submit" disabled={saving} className="rounded-full bg-slate-950 text-white hover:bg-slate-800">
                  {saving ? "Saving..." : editingId ? "Update client" : "Create client"}
                </Button>
                {editingId && (
                  <Button type="button" variant="outline" onClick={resetForm} className="rounded-full border-slate-200 bg-white hover:bg-slate-50">
                    Cancel
                  </Button>
                )}
              </div>
            </form>
            {isAdmin && (
              <p className="mt-3 text-xs text-slate-500">
                Admins can add clients here for the current workspace. If you’re testing, this will create a real client record rather than a user account.
              </p>
            )}
          </CardContent>
        </Card>

        {loading ? (
          <div className="rounded-[1.5rem] border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">Loading clients...</div>
        ) : clients.length === 0 ? (
          <div className="rounded-[1.5rem] border border-dashed border-slate-200 bg-white p-8 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
            No clients found. Add a client to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {clients.map((client) => (
                <Card key={client.id} className="group relative overflow-hidden rounded-[1.5rem] border-slate-200 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)] transition-shadow hover:shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                <CardHeader className="pb-3">
                  <div className="flex justify-between items-start">
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100">
                      <Building className="h-5 w-5 text-slate-500" />
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500 hover:bg-slate-100 hover:text-slate-950" onClick={() => startEdit(client)}>
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500 hover:bg-slate-100 hover:text-slate-950" onClick={() => void deleteClient(client.id)}>
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                  <CardTitle className="mt-2 text-lg text-slate-950">{client.name}</CardTitle>
                </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="text-sm text-slate-600">
                      {client.email || "No email"} • {client.phone || "No phone"}
                    </div>
                    {isAdmin && (
                      <div className="text-xs text-slate-500">
                        Owned by {client.owner_name ?? "Unknown owner"} {client.owner_email ? `(${client.owner_email})` : ""}
                      </div>
                    )}
                    <div className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                      {client.status}
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
