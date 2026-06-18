"use client";

import * as Sentry from "@sentry/nextjs";
import posthog from "posthog-js";
import type { AuthUser } from "./auth";
import { ensureCsrfCookie } from "./csrf";

const SESSION_KEY = "buildledger_session_id";
let posthogInitialized = false;
let posthogUnavailable = false;

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

function getPosthogHost(): string {
  return process.env.NEXT_PUBLIC_POSTHOG_HOST ?? "https://us.i.posthog.com";
}

function isPlaceholderPosthogKey(key: string): boolean {
  const normalized = key.trim().toLowerCase();

  return normalized.length === 0
    || normalized.startsWith("your_")
    || normalized.includes("placeholder");
}

function ensurePosthogInitialized() {
  if (typeof window === "undefined" || posthogInitialized || posthogUnavailable) {
    return;
  }

  const key = process.env.NEXT_PUBLIC_POSTHOG_KEY;
  if (!key || isPlaceholderPosthogKey(key)) {
    posthogUnavailable = true;
    return;
  }

  posthog.init(key, {
    api_host: getPosthogHost(),
    capture_pageview: false,
    capture_pageleave: true,
    person_profiles: "identified_only",
  });

  posthogInitialized = true;
}

function getUserContext(user: Pick<AuthUser, "id" | "name" | "email" | "role">) {
  return {
    id: String(user.id),
    name: user.name,
    email: user.email,
    role: user.role,
  };
}

function safeStringify(value: unknown): string {
  if (typeof value === "string") {
    return value;
  }

  try {
    return JSON.stringify(value);
  } catch {
    return String(value);
  }
}

export function setObservabilityUser(user: Pick<AuthUser, "id" | "name" | "email" | "role"> | null) {
  if (typeof window === "undefined") {
    return;
  }

  ensurePosthogInitialized();

  if (!user) {
    if (posthogInitialized) {
      posthog.reset();
    }
    Sentry.setUser(null);
    return;
  }

  const userContext = getUserContext(user);

  if (posthogInitialized) {
    posthog.identify(userContext.id, {
      email: userContext.email,
      name: userContext.name,
      role: userContext.role,
    });
  }

  Sentry.setUser(userContext);
}

export async function trackEvent(eventName: string, properties: Record<string, unknown> = {}) {
  if (typeof window === "undefined") {
    return;
  }

  ensurePosthogInitialized();
  await ensureCsrfCookie();

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

  if (posthogInitialized) {
    try {
      posthog.capture(eventName, {
        path: window.location.pathname,
        source: "frontend",
        session_id: getSessionId(),
        ...properties,
      });
    } catch {
      // Intentionally swallow analytics failures.
    }
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

  await ensureCsrfCookie();

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

  try {
    const error = new Error(payload.message);
    if (payload.stack) {
      error.stack = payload.stack;
    }

    Sentry.withScope(scope => {
      scope.setTag("telemetry_source", "frontend");
      scope.setTag("telemetry_path", payload.path ?? window.location.pathname);
      scope.setContext("frontend_error", {
        ...payload.context,
        path: payload.path ?? window.location.pathname,
        component_stack: payload.component_stack ?? null,
      });

      Sentry.captureException(error);
    });
  } catch {
    // Intentionally swallow Sentry failures.
  }
}

export function formatTelemetryContext(value: unknown): string {
  return safeStringify(value);
}
