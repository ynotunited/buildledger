"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import axiosInstance from "@/lib/axios";
import { ShieldAlert } from "lucide-react";

type LaunchStatus = {
  invite_only: boolean;
  source: string;
  updated_at: string | null;
};

export default function InviteOnlyBanner({ compact = false }: { compact?: boolean }) {
  const [status, setStatus] = useState<LaunchStatus | null>(null);

  useEffect(() => {
    let mounted = true;

    const load = async () => {
      try {
        const response = await axiosInstance.get<LaunchStatus>("/launch");
        if (mounted) {
          setStatus(response.data);
        }
      } catch {
        if (mounted) {
          setStatus(null);
        }
      }
    };

    void load();

    return () => {
      mounted = false;
    };
  }, []);

  if (!status?.invite_only) {
    return null;
  }

  if (compact) {
    return (
      <div className="mb-4 flex items-start gap-2.5 rounded-2xl border border-amber-500/20 bg-amber-500/10 px-3 py-3 text-sm text-amber-100 sm:gap-3 sm:px-4">
        <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-200" />
        <div>
          <p className="font-medium leading-5 text-amber-200">Invite-only mode is active.</p>
          <p className="mt-1 leading-5 text-amber-100/80">
            New accounts need an approved invitation.{" "}
            <Link href="/#waitlist" className="underline underline-offset-2 hover:text-amber-50">
              Request access
            </Link>
            .
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="landing-fade-up mt-4 rounded-2xl border border-amber-500/20 bg-amber-500/10 px-3 py-3 text-sm text-amber-100 sm:mt-6 sm:px-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-start gap-2.5 sm:gap-3">
          <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-200" />
          <div>
            <p className="font-medium leading-5 text-amber-200">Invite-only mode is active.</p>
            <p className="mt-1 leading-5 text-amber-100/80">
              New accounts need an approved invitation.{" "}
              <Link href="/#waitlist" className="underline underline-offset-2 hover:text-amber-50">
                Request access
              </Link>
              .
            </p>
          </div>
        </div>
        <span className="text-[0.65rem] uppercase tracking-[0.24em] text-amber-200/70">Controlled launch</span>
      </div>
    </div>
  );
}
