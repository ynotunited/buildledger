"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Home, Users, FileText, Briefcase,
  CreditCard, FolderKanban, Wallet, LogOut, UserCircle2, Building2,
  Bug, BarChart3, BadgeDollarSign, Shield,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuth } from "@/components/auth/AuthProvider";
import BrandLogo from "@/components/brand/BrandLogo";
import { APP_VERSION_LABEL } from "@/lib/app-version";

export default function SideNav() {
  const pathname = usePathname();
  const { user, logout } = useAuth();
  const navItems = [
    { icon: Home,         label: "Dashboard", href: "/dashboard" },
    { icon: Users,        label: "Clients",   href: "/clients" },
    { icon: FileText,     label: "Proposals", href: "/proposals" },
    { icon: Briefcase,    label: "Contracts", href: "/contracts" },
    { icon: CreditCard,   label: "Invoices",  href: "/invoices" },
    { icon: FolderKanban, label: "Projects",   href: "/projects" },
    { icon: Wallet,       label: "Payments",   href: "/payments" },
    { icon: BarChart3,    label: "Analytics",  href: "/analytics" },
    { icon: Bug,          label: "Issues",     href: "/issues" },
    { icon: BadgeDollarSign, label: "Billing", href: "/billing" },
    { icon: Building2,    label: "Company",    href: "/company" },
    { icon: UserCircle2,  label: "Account",    href: "/account" },
    ...(user?.role === "admin" ? [{ icon: Shield, label: "Admin", href: "/admin" }] : []),
  ];

  return (
    <div className="flex h-full flex-col px-4 py-6">
      {/* Logo */}
      <div className="mb-8 px-2">
        <BrandLogo href="/dashboard" variant="color" className="h-9 w-auto max-w-[11rem]" />
      </div>

      {/* Nav links */}
      <nav className="flex-1 space-y-1">
        {navItems.map((item) => {
          const Icon    = item.icon;
          const active  = pathname === item.href || pathname.startsWith(item.href + "/");
          return (
            <Link
              key={item.label}
              href={item.href}
              className={cn(
                "flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all active:scale-[0.98]",
                active
                  ? "bg-emerald-500 text-white shadow-[0_8px_24px_rgba(16,185,129,0.28)]"
                  : "text-slate-500 hover:bg-emerald-50 hover:text-emerald-700"
              )}
            >
              <Icon className={cn("h-4 w-4 shrink-0", active ? "text-white" : "text-slate-400")} />
              {item.label}
            </Link>
          );
        })}
      </nav>

      {/* User footer */}
      <div className="mt-auto border-t border-slate-200 px-2 pt-4">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-600">
            {user?.name?.charAt(0).toUpperCase() ?? "U"}
          </div>
          <div className="flex flex-col min-w-0">
            <span className="truncate text-sm font-medium text-slate-950">{user?.name ?? "My Account"}</span>
            <span className="text-xs capitalize text-slate-500">{user?.role?.replace("_", " ") ?? "Owner"}</span>
          </div>
        </div>
        <button
          onClick={() => void logout()}
          className="mt-3 flex w-full items-center gap-2 rounded-2xl px-3 py-2.5 text-sm text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
        >
          <LogOut className="h-4 w-4" />
          Sign out
        </button>
        <p className="mt-4 px-2 text-[11px] uppercase tracking-[0.22em] text-slate-400">
          BuildLedger {APP_VERSION_LABEL}
        </p>
      </div>
    </div>
  );
}
