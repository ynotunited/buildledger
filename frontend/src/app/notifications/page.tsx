"use client";

import { useEffect, useState } from "react";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Bell, CheckCheck, Trash2, ExternalLink } from "lucide-react";
import axiosInstance from "@/lib/axios";
import Link from "next/link";
import { extractPagination } from "@/lib/api";

interface Notification {
  id: string;
  type: string | null;
  title: string;
  message: string;
  link: string | null;
  read_at: string | null;
  created_at: string;
}

interface PaginatedResponse {
  data: Notification[];
  current_page: number;
  last_page: number;
}

const TYPE_COLORS: Record<string, string> = {
  invoice_paid:      "bg-green-500/10 text-green-500",
  invoice_overdue:   "bg-red-500/10 text-red-500",
  contract_signed:   "bg-blue-500/10 text-blue-500",
  proposal_approved: "bg-purple-500/10 text-purple-500",
};

const TYPE_LABELS: Record<string, string> = {
  invoice_paid:      "Payment",
  invoice_overdue:   "Overdue",
  contract_signed:   "Contract",
  proposal_approved: "Proposal",
};

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [page, setPage]     = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading]   = useState(true);

  const fetchPage = async (p: number) => {
    setLoading(true);
    try {
      const res = await axiosInstance.get<PaginatedResponse>(`/notifications?page=${p}`);
      const pagination = extractPagination(res.data);
      setNotifications(res.data.data);
      setPage(pagination.currentPage);
      setLastPage(pagination.lastPage);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadInitialPage = async () => {
      setLoading(true);
      try {
        const res = await axiosInstance.get<PaginatedResponse>("/notifications?page=1");
        const pagination = extractPagination(res.data);
        if (mounted) {
          setNotifications(res.data.data);
          setPage(pagination.currentPage);
          setLastPage(pagination.lastPage);
        }
      } catch (err) {
        console.error(err);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadInitialPage();
    return () => {
      mounted = false;
    };
  }, []);

  const markAllRead = async () => {
    await axiosInstance.post("/notifications/mark-all-read");
    setNotifications((prev) => prev.map((n) => ({ ...n, read_at: new Date().toISOString() })));
  };

  const markRead = async (id: string) => {
    await axiosInstance.post(`/notifications/${id}/read`);
    setNotifications((prev) => prev.map((n) => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
  };

  const deleteOne = async (id: string) => {
    await axiosInstance.delete(`/notifications/${id}`);
    setNotifications((prev) => prev.filter((n) => n.id !== id));
  };

  const formatTimestamp = (dateStr: string) =>
    new Date(dateStr).toLocaleString([], {
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    });

  const unreadCount = notifications.filter((n) => !n.read_at).length;

  return (
    <AppShell>
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Notifications</h1>
            <p className="text-muted-foreground text-sm">
              {unreadCount > 0 ? `${unreadCount} unread` : "All caught up"}
            </p>
          </div>
          {unreadCount > 0 && (
            <Button variant="outline" size="sm" onClick={markAllRead}>
              <CheckCheck className="w-3.5 h-3.5 mr-1.5" />
              Mark all read
            </Button>
          )}
        </div>

        {loading ? (
          <div className="space-y-3">
            {[...Array(5)].map((_, i) => (
              <div key={i} className="h-16 rounded-xl bg-secondary/40 animate-pulse" />
            ))}
          </div>
        ) : notifications.length === 0 ? (
          <div className="text-center py-16 text-muted-foreground border rounded-xl border-dashed">
            <Bell className="w-10 h-10 mx-auto mb-3 opacity-20" />
            <p className="font-medium">No notifications yet</p>
            <p className="text-sm mt-1">Activity from your business will appear here.</p>
          </div>
        ) : (
          <div className="space-y-2">
            {notifications.map((n) => (
              <div
                key={n.id}
                className={`flex items-start gap-4 p-4 rounded-xl border transition-colors group ${
                  n.read_at
                    ? "border-border bg-card opacity-70"
                    : "border-border bg-card ring-1 ring-primary/10"
                }`}
              >
                {/* Type badge */}
                <span className={`mt-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full shrink-0 ${TYPE_COLORS[n.type ?? ""] ?? "bg-secondary text-muted-foreground"}`}>
                  {TYPE_LABELS[n.type ?? ""] ?? "Info"}
                </span>

                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2">
                    <p className={`text-sm font-medium ${!n.read_at ? "text-foreground" : "text-muted-foreground"}`}>
                      {n.title}
                    </p>
                    <span className="text-[10px] text-muted-foreground/60 shrink-0">{formatTimestamp(n.created_at)}</span>
                  </div>
                  <p className="text-xs text-muted-foreground mt-0.5">{n.message}</p>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                  {n.link && (
                    <Link href={n.link} onClick={() => !n.read_at && markRead(n.id)}>
                      <ExternalLink className="w-3.5 h-3.5 text-muted-foreground hover:text-foreground" />
                    </Link>
                  )}
                  {!n.read_at && (
                    <button onClick={() => markRead(n.id)} title="Mark as read">
                      <CheckCheck className="w-3.5 h-3.5 text-muted-foreground hover:text-foreground" />
                    </button>
                  )}
                  <button onClick={() => deleteOne(n.id)} title="Delete">
                    <Trash2 className="w-3.5 h-3.5 text-muted-foreground hover:text-destructive" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Pagination */}
        {lastPage > 1 && (
          <div className="flex items-center justify-center gap-3 pt-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1 || loading}
              onClick={() => fetchPage(page - 1)}
            >
              Previous
            </Button>
            <span className="text-sm text-muted-foreground">
              Page {page} of {lastPage}
            </span>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= lastPage || loading}
              onClick={() => fetchPage(page + 1)}
            >
              Next
            </Button>
          </div>
        )}
      </div>
    </AppShell>
  );
}
