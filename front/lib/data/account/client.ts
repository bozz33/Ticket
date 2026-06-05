export const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

/**
 * Typed result from an authenticated API call.
 * Callers can distinguish between successful responses, auth failures,
 * and other error conditions instead of treating all failures as null.
 */
export type ApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; status: number; message: string };

/**
 * Perform an authenticated fetch and return a typed ApiResult.
 * Use this when the caller needs to distinguish between error categories
 * (e.g., redirect on 401, show error on 422, ignore on 404).
 */
export async function apiFetchResult<T>(
  path: string,
  token: string,
  init: RequestInit = {},
): Promise<ApiResult<T>> {
  if (!apiBase) {
    return { ok: false, status: 0, message: "API base URL not configured." };
  }

  try {
    const res = await fetch(`${apiBase}${path}`, {
      cache: "no-store",
      ...init,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        ...(init.headers ?? {}),
      },
    });

    if (!res.ok) {
      let message = `Request failed with status ${res.status}.`;
      try {
        const body = (await res.json()) as { message?: string };
        if (body?.message) message = body.message;
      } catch {
        // Body is not JSON - keep default message.
      }
      return { ok: false, status: res.status, message };
    }

    return { ok: true, data: (await res.json()) as T };
  } catch (error) {
    return {
      ok: false,
      status: 0,
      message: error instanceof Error ? error.message : "Network error.",
    };
  }
}

/**
 * Perform an authenticated fetch and return the parsed body or null on any error.
 * Preserved for backward compatibility with existing callers that do null-checks.
 * For new code, prefer apiFetchResult which exposes the status code.
 */
export async function apiFetch<T>(
  path: string,
  token: string,
  init: RequestInit = {},
): Promise<T | null> {
  const result = await apiFetchResult<T>(path, token, init);
  return result.ok ? result.data : null;
}
