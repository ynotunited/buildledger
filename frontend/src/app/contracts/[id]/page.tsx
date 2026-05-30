"use client";

import { useState, useEffect } from "react";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft, Save, CreditCard } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import axiosInstance from "@/lib/axios";
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { extractResource } from "@/lib/api";

type Contract = {
  id: number;
  title: string;
  status: string;
  body_content: string | null;
  client: {
    name: string;
  };
};

export default function ContractEditorPage({ params }: { params: { id: string } }) {
  const router = useRouter();
  const [contract, setContract] = useState<Contract | null>(null);
  const [loading, setLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isGeneratingInvoice, setIsGeneratingInvoice] = useState(false);

  const editor = useEditor({
    extensions: [StarterKit],
    content: '<p>Loading contract content...</p>',
    editorProps: {
      attributes: {
        class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none border border-border rounded-md p-4 min-h-[400px]',
      },
    },
  });

  useEffect(() => {
    const fetchContract = async () => {
      try {
        const response = await axiosInstance.get(`/contracts/${params.id}`);
        const payload = extractResource<Contract>(response.data);
        setContract(payload);
        if (editor && payload.body_content) {
          editor.commands.setContent(payload.body_content);
        } else if (editor) {
          editor.commands.setContent('<p>Enter contract terms here...</p>');
        }
      } catch (error) {
        console.error("Error fetching contract", error);
      } finally {
        setLoading(false);
      }
    };
    void fetchContract();
  }, [params.id, editor]);

  const handleSave = async () => {
    setIsSaving(true);
    try {
      await axiosInstance.put(`/contracts/${params.id}`, {
        body_content: editor?.getHTML(),
      });
      // Handle success notification
    } catch (error) {
      console.error("Error saving contract", error);
    } finally {
      setIsSaving(false);
    }
  };

  const handleGenerateInvoice = async () => {
    setIsGeneratingInvoice(true);
    try {
      await axiosInstance.post(`/contracts/${params.id}/convert-to-invoice`);
      router.push("/invoices");
    } catch (error) {
      console.error("Error generating invoice", error);
    } finally {
      setIsGeneratingInvoice(false);
    }
  };

  if (loading) return <AppShell><div className="p-8 text-center">Loading contract...</div></AppShell>;
  if (!contract) return <AppShell><div className="p-8 text-center">Contract not found.</div></AppShell>;

  return (
    <AppShell>
      <div className="space-y-6 max-w-4xl mx-auto">
        <div className="flex items-center justify-between mb-8">
          <div className="flex items-center space-x-4">
            <Link href="/contracts">
              <Button variant="ghost" size="icon" className="rounded-full">
                <ArrowLeft className="w-5 h-5" />
              </Button>
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight">{contract.title}</h1>
              <p className="text-sm text-muted-foreground">Client: {contract.client.name}</p>
            </div>
          </div>
          <div className="flex space-x-2">
            <Button variant="outline" onClick={handleGenerateInvoice} disabled={isGeneratingInvoice}>
              <CreditCard className="w-4 h-4 mr-2" />
              {isGeneratingInvoice ? "Generating..." : "Generate Invoice"}
            </Button>
            <Button onClick={handleSave} disabled={isSaving || contract.status === 'Signed'}>
              <Save className="w-4 h-4 mr-2" />
              {isSaving ? "Saving..." : "Save Draft"}
            </Button>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Contract Body</CardTitle>
            {contract.status === 'Signed' && (
              <div className="bg-green-500/10 text-green-500 p-2 text-sm rounded-md border border-green-500/20">
                This contract has been signed by the client and can no longer be edited.
              </div>
            )}
          </CardHeader>
          <CardContent>
            {contract.status === 'Signed' ? (
              <div className="prose prose-sm sm:prose lg:prose-lg xl:prose-2xl p-4 border rounded-md bg-secondary/20" dangerouslySetInnerHTML={{ __html: contract.body_content ?? "" }} />
            ) : (
              <EditorContent editor={editor} />
            )}
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
