import axios from "axios";

let csrfRequest: Promise<void> | null = null;

export async function ensureCsrfCookie() {
  if (typeof window === "undefined") {
    return;
  }

  if (!csrfRequest) {
    const backendUrl = process.env.NEXT_PUBLIC_BACKEND_URL;

    if (!backendUrl) {
      throw new Error("NEXT_PUBLIC_BACKEND_URL is not configured.");
    }

    csrfRequest = axios
      .get(`${backendUrl}/sanctum/csrf-cookie`, {
        withCredentials: true,
      })
      .then(() => undefined)
      .finally(() => {
        csrfRequest = null;
      });
  }

  await csrfRequest;
}
