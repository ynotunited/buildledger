import type { Metadata } from "next";
import { AuthProvider } from "@/components/auth/AuthProvider";
import TelemetryProvider from "@/components/telemetry/TelemetryProvider";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL("https://buildledger.madeitcodes.online"),
  title: "BuildLedger | client finance workspace",
  description: "Create invoices, reconcile transactions, and review ledger entries in one live SaaS workspace.",
  icons: {
    icon: "/icon.png",
    apple: "/apple-icon.png",
  },
  openGraph: {
    title: "BuildLedger | client finance workspace",
    description: "Create invoices, reconcile transactions, and review ledger entries in one live SaaS workspace.",
    url: "https://buildledger.madeitcodes.online",
    siteName: "BuildLedger",
  },
  twitter: {
    card: "summary_large_image",
    title: "BuildLedger | client finance workspace",
    description: "Create invoices, reconcile transactions, and review ledger entries in one live SaaS workspace.",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="h-full antialiased">
      <body className="min-h-full flex flex-col bg-background text-foreground selection:bg-primary/10">
        <AuthProvider>
          <TelemetryProvider>{children}</TelemetryProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
