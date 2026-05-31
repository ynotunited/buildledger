import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import BrandLogo from "@/components/brand/BrandLogo";

export default function LegalLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-[linear-gradient(180deg,_rgba(24,24,27,0.98),_rgba(9,9,11,1))] text-white">
      {/* Top bar */}
      <div className="sticky top-0 z-10 border-b border-white/8 bg-zinc-950/80 backdrop-blur-md">
        <div className="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
          <Link
            href="/"
            className="flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-zinc-200"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to BuildLedger
          </Link>
          <BrandLogo href="/" variant="white" className="h-6 w-auto" />
        </div>
      </div>

      {/* Content */}
      <div className="mx-auto max-w-4xl px-6 py-16">
        {children}
      </div>

      {/* Footer */}
      <footer className="border-t border-white/8 py-8">
        <div className="mx-auto flex max-w-4xl flex-col items-center justify-between gap-4 px-6 sm:flex-row">
          <span className="text-sm text-zinc-500">
            © {new Date().getFullYear()} | Webxpress Technologies MadeIT
          </span>
          <nav className="flex flex-wrap items-center justify-center gap-5">
            <Link href="/privacy-policy"   className="text-xs text-zinc-500 transition-colors hover:text-zinc-300">Privacy Policy</Link>
            <Link href="/terms-of-use"     className="text-xs text-zinc-500 transition-colors hover:text-zinc-300">Terms of Use</Link>
            <Link href="/data-compliance"  className="text-xs text-zinc-500 transition-colors hover:text-zinc-300">Data &amp; Compliance</Link>
            <Link href="/ip-infringement"  className="text-xs text-zinc-500 transition-colors hover:text-zinc-300">IP Infringement</Link>
          </nav>
        </div>
      </footer>
    </div>
  );
}
