"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { CheckCircle } from "lucide-react";
import axiosInstance from "@/lib/axios";

type PublicContract = {
  title: string;
  status: string;
  body_content: string | null;
  company?: {
    name: string;
    logo_url: string | null;
    email?: string | null;
    website?: string | null;
  } | null;
  client: {
    name: string;
  };
};

export default function PublicContractSignPage({ params }: { params: { uuid: string } }) {
  const [contract, setContract] = useState<PublicContract | null>(null);
  const [loading, setLoading] = useState(true);
  const [signatureName, setSignatureName] = useState("");
  const [isSigning, setIsSigning] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchContract = async () => {
      try {
        const response = await axiosInstance.get(`/public/contracts/${params.uuid}`);
        setContract(response.data);
        if (response.data.status === 'Signed') {
          setIsSuccess(true);
        }
      } catch (err) {
        console.error("Error fetching contract", err);
        setError("Contract not found or invalid link.");
      } finally {
        setLoading(false);
      }
    };

    void fetchContract();
  }, [params.uuid]);

  const handleSign = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!signatureName) return;

    setIsSigning(true);
    try {
      await axiosInstance.post(`/public/contracts/${params.uuid}/sign`, {
        signature_name: signatureName,
      });
      setIsSuccess(true);
    } catch (err) {
      console.error("Error signing contract", err);
      setError("Failed to sign contract. Please try again.");
    } finally {
      setIsSigning(false);
    }
  };

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center bg-background"><p>Loading contract...</p></div>;
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background p-4">
        <Card className="w-full max-w-md text-center">
          <CardContent className="pt-6">
            <p className="text-destructive">{error}</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (!contract) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background p-4">
        <Card className="w-full max-w-md text-center">
          <CardContent className="pt-6">
            <p className="text-muted-foreground">Contract unavailable.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (isSuccess) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background p-4">
        <Card className="w-full max-w-md text-center border-green-500/20 shadow-green-500/10">
          <CardContent className="pt-8 pb-8 flex flex-col items-center">
            <CheckCircle className="w-16 h-16 text-green-500 mb-4" />
            <h2 className="text-2xl font-bold mb-2">Contract Signed!</h2>
            <p className="text-muted-foreground">
              Thank you. The contract has been successfully signed and legally binding. A copy has been saved for your records.
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background p-4 py-12">
      <div className="max-w-3xl mx-auto space-y-8">
        <div className="text-center space-y-2">
          {contract.company?.logo_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={contract.company.logo_url}
              alt={`${contract.company.name} logo`}
              className="mx-auto mb-4 max-h-20 max-w-[220px] object-contain"
            />
          ) : null}
          {contract.company?.name ? (
            <p className="text-sm uppercase tracking-[0.25em] text-primary/70">{contract.company.name}</p>
          ) : null}
          <h1 className="text-3xl font-bold tracking-tight">{contract.title}</h1>
          <p className="text-muted-foreground">Prepared for {contract.client.name}</p>
          {(contract.company?.email || contract.company?.website) ? (
            <p className="text-sm text-muted-foreground">
              {[contract.company?.email, contract.company?.website].filter(Boolean).join(" • ")}
            </p>
          ) : null}
        </div>

        <Card className="shadow-lg">
          <CardContent className="p-8 prose prose-sm sm:prose lg:prose-lg mx-auto bg-card rounded-lg" dangerouslySetInnerHTML={{ __html: contract.body_content ?? "" }} />
        </Card>

        <Card className="border-primary/20 shadow-md">
          <CardHeader>
            <CardTitle>Sign Contract</CardTitle>
          </CardHeader>
          <form onSubmit={handleSign}>
            <CardContent className="space-y-4">
              <p className="text-sm text-muted-foreground">
                By typing your name below and clicking &quot;Sign & Accept&quot;, you agree to the terms outlined in this contract. This acts as your legally binding electronic signature.
              </p>
              <div className="space-y-2">
                <label className="text-sm font-medium">Full Name (Electronic Signature)</label>
                <input 
                  type="text" 
                  className="flex h-12 w-full rounded-md border border-input bg-background px-3 py-2 text-lg ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                  placeholder="e.g. Jane Doe"
                  value={signatureName}
                  onChange={(e) => setSignatureName(e.target.value)}
                  required
                />
              </div>
            </CardContent>
            <CardFooter>
              <Button type="submit" className="w-full h-12 text-lg font-medium" disabled={isSigning || !signatureName}>
                {isSigning ? "Signing..." : "Sign & Accept"}
              </Button>
            </CardFooter>
          </form>
        </Card>
      </div>
    </div>
  );
}
