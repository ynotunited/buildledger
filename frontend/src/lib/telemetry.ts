const SESSION_KEY = "buildledger_session_id";

function getSessionId(): string {
  if (typeof window === "undefined") {
    return "server";
  }

  const existing = window.sessionStorage.getItem(SESSION_KEY);
  if (existing) {
    return existing;
  }

  const id = crypto.randomUUID();
  window.sessionStorage.setItem(SESSION_KEY, id);
  return id;
}

export async function trackEvent(eventName: string, properties: Record<string, unknown> = {}) {
  if (typeof window === "undefined") {
    return;
  }

  const payload = {
    event_name: eventName,
    path: window.location.pathname,
    source: "frontend",
    session_id: getSessionId(),
    properties,
  };

  try {
    await fetch(`${process.env.NEXT_PUBLIC_API_URL}/telemetry/events`, {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
      keepalive: true,
    });
  } catch {
    // Intentionally swallow telemetry failures.
  }
}

export async function captureFrontendError(payload: {
  message: string;
  path?: string;
  stack?: string | null;
  component_stack?: string | null;
  context?: Record<string, unknown>;
}) {
  if (typeof window === "undefined") {
    return;
  }

  try {
    await fetch(`${process.env.NEXT_PUBLIC_API_URL}/telemetry/frontend-errors`, {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        ...payload,
        path: payload.path ?? window.location.pathname,
      }),
      keepalive: true,
    });
  } catch {
    // Intentionally swallow telemetry failures.
  }
}
