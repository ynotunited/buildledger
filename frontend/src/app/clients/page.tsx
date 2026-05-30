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
        <div className="flex items-center justify-between">
          <div className="flex flex-col space-y-1">
            <h1 className="text-2xl font-bold tracking-tight">{isAdmin ? "All Clients" : "Clients"}</h1>
            <p className="text-muted-foreground text-sm">
              {isAdmin
                ? "View and manage clients across every workspace in the platform."
                : "Manage your leads and active clients."}
            </p>
          </div>
          <Button size="sm" onClick={resetForm}>
            <Plus className="w-4 h-4 mr-2" />
            {editingId ? "New Client" : "Add Client"}
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>{editingId ? "Edit Client" : "Create Client"}</CardTitle>
          </CardHeader>
          <CardContent>
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
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  {["Lead", "Negotiation", "Active", "Completed", "Dormant"].map((status) => (
                    <option key={status} value={status}>{status}</option>
                  ))}
                </select>
              </div>
              <div className="md:col-span-2 flex gap-3">
                <Button type="submit" disabled={saving}>
                  {saving ? "Saving..." : editingId ? "Update Client" : "Create Client"}
                </Button>
                {editingId && (
                  <Button type="button" variant="outline" onClick={resetForm}>
                    Cancel
                  </Button>
                )}
              </div>
            </form>
            {isAdmin && (
              <p className="mt-3 text-xs text-muted-foreground">
                Admins can add clients here for the current workspace. If you’re testing, this will create a real client record rather than a user account.
              </p>
            )}
          </CardContent>
        </Card>

        {loading ? (
          <div className="flex items-center justify-center p-8">Loading clients...</div>
        ) : clients.length === 0 ? (
          <div className="text-center p-8 text-muted-foreground border rounded-lg border-dashed">
            No clients found. Add a client to get started.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {clients.map((client) => (
                <Card key={client.id} className="relative overflow-hidden group hover:shadow-md transition-shadow">
                <CardHeader className="pb-2">
                  <div className="flex justify-between items-start">
                    <div className="w-10 h-10 rounded-full bg-secondary flex items-center justify-center">
                      <Building className="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground" onClick={() => startEdit(client)}>
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground" onClick={() => void deleteClient(client.id)}>
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                  <CardTitle className="text-lg mt-2">{client.name}</CardTitle>
                </CardHeader>
                  <CardContent>
                    <div className="text-sm text-muted-foreground mb-4">
                      {client.email || "No email"} • {client.phone || "No phone"}
                    </div>
                    {isAdmin && (
                      <div className="mb-3 text-xs text-muted-foreground">
                        Owned by {client.owner_name ?? "Unknown owner"} {client.owner_email ? `(${client.owner_email})` : ""}
                      </div>
                    )}
                    <div className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
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
