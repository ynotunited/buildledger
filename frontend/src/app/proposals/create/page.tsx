"use client";

import { useState, useEffect } from "react";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import axiosInstance from "@/lib/axios";
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { extractCollection } from "@/lib/api";

interface Client {
  id: number;
  name: string;
}

type ProposalItem = {
  name: string;
  description: string;
  quantity: number;
  unit_price: number;
};

export default function CreateProposalPage() {
  const router = useRouter();
  const [clients, setClients] = useState<Client[]>([]);
  const [clientId, setClientId] = useState("");
  const [title, setTitle] = useState("");
  const [issueDate, setIssueDate] = useState("");
  const [items, setItems] = useState<ProposalItem[]>([{ name: "", description: "", quantity: 1, unit_price: 0 }]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const editor = useEditor({
    extensions: [StarterKit],
    content: '<p>Enter your proposal details here...</p>',
    editorProps: {
      attributes: {
        class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none border border-border rounded-md p-4 min-h-[200px]',
      },
    },
  });

  useEffect(() => {
    const fetchClients = async () => {
      try {
        const response = await axiosInstance.get("/clients");
        setClients(extractCollection<Client>(response.data));
      } catch (error) {
        console.error("Error fetching clients", error);
      }
    };
    fetchClients();
  }, []);

  const addItem = () => {
    setItems([...items, { name: "", description: "", quantity: 1, unit_price: 0 }]);
  };

  const removeItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  const updateItem = <K extends keyof ProposalItem>(index: number, field: K, value: ProposalItem[K]) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], [field]: value };
    setItems(newItems);
  };

  const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!clientId || !title || !issueDate) return;
    
    setIsSubmitting(true);
    try {
      await axiosInstance.post("/proposals", {
        client_id: clientId,
        title,
        issue_date: issueDate,
        notes: editor?.getHTML(),
        items,
      });
      router.push("/proposals");
    } catch (error) {
      console.error("Error creating proposal", error);
      setIsSubmitting(false);
    }
  };

  return (
    <AppShell>
      <div className="space-y-6 max-w-4xl mx-auto">
        <div className="flex items-center space-x-4 mb-8">
          <Link href="/proposals">
            <Button variant="ghost" size="icon" className="rounded-full">
              <ArrowLeft className="w-5 h-5" />
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Create Proposal</h1>
            <p className="text-sm text-muted-foreground">Draft a new proposal for a client.</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-8">
          <Card>
            <CardHeader>
              <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Client</label>
                  <select 
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    value={clientId}
                    onChange={(e) => setClientId(e.target.value)}
                    required
                  >
                    <option value="">Select a client</option>
                    {clients.map(client => (
                      <option key={client.id} value={client.id}>{client.name}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Proposal Title</label>
                  <input 
                    type="text" 
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="e.g. Website Redesign"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Issue Date</label>
                  <input 
                    type="date" 
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    value={issueDate}
                    onChange={(e) => setIssueDate(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="space-y-2 mt-6">
                <label className="text-sm font-medium">Proposal Body</label>
                <div className="border border-border rounded-md">
                  {/* TipTap Editor Toolbar could go here */}
                  <EditorContent editor={editor} />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>Pricing Items</CardTitle>
              <Button type="button" variant="outline" size="sm" onClick={addItem}>
                <Plus className="w-4 h-4 mr-2" /> Add Item
              </Button>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {items.map((item, index) => (
                  <div key={index} className="flex flex-col md:flex-row gap-4 p-4 border border-border rounded-lg relative group">
                    <Button 
                      type="button" 
                      variant="ghost" 
                      size="icon" 
                      className="absolute -right-2 -top-2 bg-destructive text-destructive-foreground rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                      onClick={() => removeItem(index)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                    <div className="flex-1 space-y-2">
                      <label className="text-xs font-medium text-muted-foreground">Item Name</label>
                      <input 
                        type="text" 
                        placeholder="Name" 
                        value={item.name} 
                        onChange={(e) => updateItem(index, 'name', e.target.value)} 
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        required
                      />
                      <input 
                        type="text" 
                        placeholder="Description (Optional)" 
                        value={item.description} 
                        onChange={(e) => updateItem(index, 'description', e.target.value)} 
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      />
                    </div>
                    <div className="w-full md:w-24 space-y-2">
                      <label className="text-xs font-medium text-muted-foreground">Qty</label>
                      <input 
                        type="number" 
                        min="1" 
                        value={item.quantity} 
                        onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value) || 0)} 
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        required
                      />
                    </div>
                    <div className="w-full md:w-32 space-y-2">
                      <label className="text-xs font-medium text-muted-foreground">Price</label>
                      <input 
                        type="number" 
                        min="0" 
                        step="0.01" 
                        value={item.unit_price} 
                        onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)} 
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        required
                      />
                    </div>
                    <div className="w-full md:w-32 space-y-2 flex flex-col justify-end">
                      <div className="px-3 py-2 text-sm font-bold bg-secondary/50 rounded-md text-right">
                        ${(item.quantity * item.unit_price).toFixed(2)}
                      </div>
                    </div>
                  </div>
                ))}

                <div className="flex justify-end pt-6 border-t border-border">
                  <div className="w-full md:w-64 space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">Subtotal</span>
                      <span>${subtotal.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between font-bold text-lg pt-2 border-t border-border">
                      <span>Total</span>
                      <span>${subtotal.toFixed(2)}</span>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <div className="flex justify-end space-x-4">
            <Button type="button" variant="outline" onClick={() => router.push("/proposals")}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating..." : "Create Proposal"}
            </Button>
          </div>
        </form>
      </div>
    </AppShell>
  );
}
