"use client";

import { createContext, useContext, useEffect, useRef, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import axiosInstance from "@/lib/axios";
import { AuthUser, clearAuthToken, getPostLoginRedirect } from "@/lib/auth";

type AuthContextValue = {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  refreshUser: () => Promise<AuthUser | null>;
  setAuthenticatedUser: (user: AuthUser | null) => void;
  logout: () => Promise<void>;
  setUserFromToken: () => Promise<AuthUser | null>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

const PUBLIC_PATHS = new Set([
  "/",
  "/login",
  "/register",
  "/forgot-password",
  "/reset-password",
  "/verify-email",
  "/auth/callback",
]);

function isPublicPath(pathname: string): boolean {
  return PUBLIC_PATHS.has(pathname) || pathname.startsWith("/contracts/sign/");
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const pathnameRef = useRef(pathname);
  const routerRef = useRef(router);
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const fetchUser = async (): Promise<AuthUser | null> => {
    try {
      const response = await axiosInstance.get<AuthUser>("/user");
      setUser(response.data);
      return response.data;
    } catch {
      setUser(null);
      return null;
    }
  };

  useEffect(() => {
    let mounted = true;

    const hydrateUser = async () => {
      setIsLoading(true);
      try {
        const response = await axiosInstance.get<AuthUser>("/user");
        if (mounted) {
          setUser(response.data);
        }
      } catch {
        if (mounted) {
          setUser(null);
          if (!isPublicPath(pathnameRef.current)) {
            clearAuthToken();
            routerRef.current.replace(`/login?next=${encodeURIComponent(pathnameRef.current)}`);
          }
        }
      } finally {
        if (mounted) {
          setIsLoading(false);
        }
      }
    };

    void hydrateUser();

    return () => {
      mounted = false;
    };
  }, []);

  useEffect(() => {
    if (isLoading || !user || !isPublicPath(pathname)) {
      return;
    }

    router.replace(getPostLoginRedirect(window.location.search, user.role));
  }, [isLoading, pathname, router, user]);

  const logout = async () => {
    try {
      await axiosInstance.post("/logout");
    } catch {
      // Best-effort logout. We still clear the client session below.
    } finally {
      clearAuthToken();
      setUser(null);
      router.push("/login");
    }
  };

  const value: AuthContextValue = {
    user,
    isAuthenticated: !!user,
    isLoading,
    refreshUser: fetchUser,
    setAuthenticatedUser: setUser,
    logout,
    setUserFromToken: async () => {
      return await fetchUser();
    },
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }

  return context;
}
