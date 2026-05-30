export type AuthUser = {
  id: number;
  name: string;
  email: string;
  role: "owner" | "admin" | "team_member" | "client";
  email_verified_at: string | null;
  is_google_account: boolean;
  trial_ends_at: string | null;
  is_impersonating: boolean;
  impersonator_name: string | null;
  impersonator_email: string | null;
  impersonated_at: string | null;
};

export function clearAuthToken() {
  if (typeof window === "undefined") {
    return;
  }

  localStorage.removeItem("auth_token");
  document.cookie = "buildledger_auth=; path=/; max-age=0; samesite=lax";
}

export function getDefaultDashboardPath(role?: AuthUser["role"] | null): string {
  return role === "admin" ? "/admin" : "/dashboard";
}

export function getPostLoginRedirect(search: string, role?: AuthUser["role"] | null): string {
  const params = new URLSearchParams(search);
  const next = params.get("next");

  return next && next.startsWith("/") ? next : getDefaultDashboardPath(role);
}
