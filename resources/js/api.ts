import type { ApiErrorPayload } from "./types";

const API_BASE = "/api/v1";

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(status: number, payload: ApiErrorPayload) {
    super(payload.message || "خطایی در ارتباط با سرور رخ داد.");
    this.status = status;
    this.errors = payload.errors || {};
  }
}

function cookieValue(name: string): string | null {
  const prefix = `${name}=`;
  const item = document.cookie.split("; ").find((cookie) => cookie.startsWith(prefix));
  return item ? decodeURIComponent(item.slice(prefix.length)) : null;
}

async function csrfCookie(): Promise<void> {
  await fetch("/sanctum/csrf-cookie", {
    credentials: "include",
    headers: { Accept: "application/json" },
  });
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = (options.method || "GET").toUpperCase();
  const mutating = !["GET", "HEAD", "OPTIONS"].includes(method);

  if (mutating) await csrfCookie();

  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (options.body && !(options.body instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }

  const xsrf = cookieValue("XSRF-TOKEN");
  if (mutating && xsrf) headers.set("X-XSRF-TOKEN", xsrf);

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
    credentials: "include",
  });

  if (response.status === 204) return undefined as T;

  const payload = (await response.json().catch(() => ({}))) as T & ApiErrorPayload;

  if (!response.ok) throw new ApiError(response.status, payload);

  return payload;
}

export function jsonBody(value: unknown): Pick<RequestInit, "body"> {
  return { body: JSON.stringify(value) };
}
