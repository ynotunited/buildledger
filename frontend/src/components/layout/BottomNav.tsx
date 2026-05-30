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
      className="grid h-full w-full max-w-md mx-auto"
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
              "flex flex-col items-center justify-center w-full h-full transition-colors",
              active ? "text-primary" : "text-muted-foreground hover:text-foreground"
            )}
          >
            <Icon className={cn("w-5 h-5 mb-1 transition-transform", active && "scale-110")} />
            <span className={cn("text-[10px] font-medium", active && "font-semibold")}>
              {item.label}
            </span>
          </Link>
        );
      })}
    </div>
  );
}
