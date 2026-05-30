import type { Metadata } from "next";
import { AuthProvider } from "@/components/auth/AuthProvider";
import TelemetryProvider from "@/components/telemetry/TelemetryProvider";
import "./globals.css";

export const metadata: Metadata = {
  title: "BuildLedger | Business OS for Digital Agencies",
  description: "Run your digital business from one place. From proposal to payment.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="dark h-full antialiased">
      <body className="min-h-full flex flex-col bg-background text-foreground selection:bg-primary/10">
        <AuthProvider>
          <TelemetryProvider>{children}</TelemetryProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
