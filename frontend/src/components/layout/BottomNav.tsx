"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Home, Users, FolderKanban, CreditCard, Wallet, UserCircle2, Shield } from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuth } from "@/components/auth/AuthProvider";

export default function BottomNav() {
  const pathname = usePathname();
  const { user } = useAuth();
  const navItems = [
    { icon: Home,         label: "Home",     href: "/dashboard" },
    { icon: Users,        label: "Clients",  href: "/clients" },
    { icon: FolderKanban, label: "Projects", href: "/projects" },
    { icon: CreditCard,   label: "Invoices", href: "/invoices" },
    { icon: Wallet,       label: "Payments", href: "/payments" },
    { icon: UserCircle2,  label: "Account",  href: "/account" },
    ...(user?.role === "admin" ? [{ icon: Shield, label: "Admin", href: "/admin" }] : []),
  ];

  return (
    <div
      className="mx-auto grid h-full w-full max-w-md"
      style={{ gridTemplateColumns: `repeat(${navItems.length}, minmax(0, 1fr))` }}
    >
      {navItems.map((item) => {
        const Icon   = item.icon;
        const active = pathname === item.href || pathname.startsWith(item.href + "/");
        return (
          <Link
            key={item.label}
          href={item.href}
          className={cn(
              "flex h-full w-full flex-col items-center justify-center transition-colors",
              active ? "text-slate-950" : "text-slate-400 hover:text-slate-950"
            )}
          >
            <Icon className={cn("mb-1 h-5 w-5 transition-transform", active && "scale-110")} />
            <span className={cn("text-[10px] font-medium", active && "font-semibold")}>
              {item.label}
            </span>
          </Link>
        );
      })}
    </div>
  );
}
