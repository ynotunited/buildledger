"use client";

import { useEffect, useRef, useState } from "react";
import { Bell, CheckCheck, ExternalLink, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import axiosInstance from "@/lib/axios";
import Link from "next/link";

interface NotificationItem {
  id: string;
  type: string | null;
  title: string;
  message: string;
  link: string | null;
  created_at: string;
}

interface UnreadResponse {
  count: number;
  items: NotificationItem[];
}

const TYPE_COLORS: Record<string, string> = {
  invoice_paid:      "bg-green-500",
  invoice_overdue:   "bg-red-500",
  contract_signed:   "bg-blue-500",
  proposal_approved: "bg-purple-500",
};

export default function NotificationBell() {
  const [open, setOpen]   = useState(false);
  const [data, setData]   = useState<UnreadResponse>({ count: 0, items: [] });
  const [loading, setLoading] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  // Poll every 30 s
  useEffect(() => {
    let mounted = true;

    const fetchUnread = async () => {
      try {
        const res = await axiosInstance.get<UnreadResponse>("/notifications/unread");
        if (mounted) {
          setData(res.data);
        }
      } catch {
        // silently fail — user may not be logged in yet
      }
    };

    void fetchUnread();
    const interval = setInterval(() => {
      void fetchUnread();
    }, 30_000);

    return () => {
      mounted = false;
      clearInterval(interval);
    };
  }, []);

  // Close on outside click
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  const markAllRead = async () => {
    setLoading(true);
    try {
      await axiosInstance.post("/notifications/mark-all-read");
      setData({ count: 0, items: [] });
    } finally {
      setLoading(false);
    }
  };

  const markOneRead = async (id: string) => {
    try {
      await axiosInstance.post(`/notifications/${id}/read`);
      setData((prev) => ({
        count: Math.max(0, prev.count - 1),
        items: prev.items.filter((n) => n.id !== id),
      }));
    } catch {/* ignore */}
  };

  const formatTimestamp = (dateStr: string) =>
    new Date(dateStr).toLocaleString([], {
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });

  return (
    <div ref={ref} className="relative">
      <Button
        variant="ghost"
        size="icon"
        onClick={() => setOpen((o) => !o)}
        aria-label="Notifications"
        className="relative"
      >
        <Bell className="w-4 h-4" />
        {data.count > 0 && (
          <span className="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none">
            {data.count > 99 ? "99+" : data.count}
          </span>
        )}
      </Button>

      {open && (
        <div className="absolute right-0 top-10 w-80 z-50 rounded-xl border border-border bg-card shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <span className="text-sm font-semibold">Notifications</span>
            {data.count > 0 && (
              <button
                onClick={markAllRead}
                disabled={loading}
                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
              >
                <CheckCheck className="w-3.5 h-3.5" />
                Mark all read
              </button>
            )}
          </div>

          {/* Items */}
          <div className="max-h-80 overflow-y-auto divide-y divide-border">
            {data.items.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-10 text-muted-foreground">
                <Bell className="w-8 h-8 mb-2 opacity-20" />
                <p className="text-sm">You&apos;re all caught up</p>
              </div>
            ) : (
              data.items.map((n) => (
                <div key={n.id} className="flex items-start gap-3 px-4 py-3 hover:bg-secondary/30 transition-colors group">
                  {/* Colour dot */}
                  <span className={`mt-1.5 w-2 h-2 rounded-full shrink-0 ${TYPE_COLORS[n.type ?? ""] ?? "bg-primary"}`} />

                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium leading-snug">{n.title}</p>
                    <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{n.message}</p>
                    <p className="text-[10px] text-muted-foreground/60 mt-1">{formatTimestamp(n.created_at)}</p>
                  </div>

                  <div className="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    {n.link && (
                      <Link href={n.link} onClick={() => { markOneRead(n.id); setOpen(false); }}>
                        <ExternalLink className="w-3.5 h-3.5 text-muted-foreground hover:text-foreground" />
                      </Link>
                    )}
                    <button onClick={() => markOneRead(n.id)}>
                      <X className="w-3.5 h-3.5 text-muted-foreground hover:text-foreground" />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          <div className="border-t border-border px-4 py-2.5">
            <Link
              href="/notifications"
              onClick={() => setOpen(false)}
              className="text-xs text-primary hover:underline"
            >
              View all notifications →
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}
