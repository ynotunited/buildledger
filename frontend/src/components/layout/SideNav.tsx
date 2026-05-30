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
    <div className="flex flex-col h-full py-6 px-4">
      {/* Logo */}
      <div className="flex items-center gap-2 mb-10 px-2">
        <div className="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
          <span className="text-primary-foreground font-bold text-lg">B</span>
        </div>
        <span className="font-semibold text-lg tracking-tight">BuildLedger</span>
      </div>

      {/* Nav links */}
      <nav className="flex-1 space-y-0.5">
        {navItems.map((item) => {
          const Icon    = item.icon;
          const active  = pathname === item.href || pathname.startsWith(item.href + "/");
          return (
            <Link
              key={item.label}
              href={item.href}
              className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all active:scale-[0.98]",
                active
                  ? "bg-primary/10 text-primary"
                  : "text-muted-foreground hover:text-foreground hover:bg-secondary/50"
              )}
            >
              <Icon className="w-4 h-4 shrink-0" />
              {item.label}
            </Link>
          );
        })}
      </nav>

      {/* User footer */}
      <div className="mt-auto px-2 border-t border-border pt-4">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-secondary border border-border flex items-center justify-center text-xs font-semibold text-muted-foreground">
            {user?.name?.charAt(0).toUpperCase() ?? "U"}
          </div>
          <div className="flex flex-col min-w-0">
            <span className="text-sm font-medium text-foreground truncate">{user?.name ?? "My Account"}</span>
            <span className="text-xs capitalize text-muted-foreground">{user?.role?.replace("_", " ") ?? "Owner"}</span>
          </div>
        </div>
        <button
          onClick={() => void logout()}
          className="mt-3 flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm text-muted-foreground transition-colors hover:bg-secondary/50 hover:text-foreground"
        >
          <LogOut className="h-4 w-4" />
          Sign out
        </button>
      </div>
    </div>
  );
}
