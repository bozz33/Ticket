"use client";

import { useEffect, useMemo, useRef, useState } from "react";

type FollowPayload = {
  authenticated: boolean;
  following: boolean;
  followers: number;
  message?: string;
  error?: string;
};

type LocalFollowState = {
  following: boolean;
  version: number;
};

const LOCAL_FOLLOW_PREFIX = "ticket:organizer-follow";
const LOCAL_FOLLOW_MAX_AGE_MS = 1000 * 60 * 60 * 24 * 30;
const LOCAL_FOLLOW_EVENT = "ticket:organizer-follow-updated";

export function OrganizerFollowPill({
  slug,
  accountSessionKey,
  followLabel = "Suivre l'orga",
  followedLabel = "Orga suivie",
  initialAuthenticated,
  initialFollowing,
  organizerName,
}: {
  slug: string;
  accountSessionKey?: string;
  followLabel?: string;
  followedLabel?: string;
  initialAuthenticated?: boolean;
  initialFollowing?: boolean;
  organizerName?: string;
}) {
  const [authenticated, setAuthenticated] = useState<boolean | undefined>(initialAuthenticated);
  const [following, setFollowing] = useState(initialFollowing === true);
  const [loading, setLoading] = useState(false);
  const mutationIdRef = useRef(0);
  const retryTimersRef = useRef<ReturnType<typeof setTimeout>[]>([]);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;

    return `/compte/connexion?tenant=${encodeURIComponent(slug)}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [slug]);
  const localStateKey = useMemo(
    () => accountSessionKey ? `${LOCAL_FOLLOW_PREFIX}:${accountSessionKey}:${slug}` : "",
    [accountSessionKey, slug],
  );
  const actionLabel = following ? followedLabel : followLabel;
  const accessibleLabel = following
    ? `Ne plus suivre l'organisateur ${organizerName || slug}`
    : `Suivre l'organisateur ${organizerName || slug}`;

  useEffect(() => {
    setAuthenticated(initialAuthenticated);
  }, [initialAuthenticated]);

  useEffect(() => {
    if (typeof initialFollowing === "boolean") {
      if (authenticated === true && localStateKey && typeof window !== "undefined" && readLocalFollowState(localStateKey) !== null) {
        return;
      }

      setFollowing(initialFollowing);
    }
  }, [authenticated, initialFollowing, localStateKey]);

  useEffect(() => {
    if (authenticated !== true || !localStateKey || typeof window === "undefined") {
      return;
    }

    const localState = readLocalFollowState(localStateKey);

    if (localState === null) {
      return;
    }

    setFollowing(localState.following);
  }, [authenticated, localStateKey]);

  useEffect(() => {
    if (!localStateKey || typeof window === "undefined") {
      return;
    }

    function syncLocalState(event: Event) {
      const detail = (event as CustomEvent<{ key?: string; following?: boolean; version?: number }>).detail;

      if (detail?.key !== localStateKey || typeof detail.following !== "boolean") {
        return;
      }

      if (isStaleLocalFollowVersion(localStateKey, detail.version)) {
        return;
      }

      setFollowing(detail.following);
    }

    window.addEventListener(LOCAL_FOLLOW_EVENT, syncLocalState);

    return () => window.removeEventListener(LOCAL_FOLLOW_EVENT, syncLocalState);
  }, [localStateKey]);

  useEffect(() => () => {
    retryTimersRef.current.forEach((timer) => clearTimeout(timer));
    retryTimersRef.current = [];
  }, []);

  async function toggleFollow() {
    if (authenticated === false) {
      setLoading(true);
      window.location.assign(redirectHref);
      return;
    }

    const previousFollowing = following;
    const nextFollowing = !previousFollowing;
    const mutationId = mutationIdRef.current + 1;
    const mutationVersion = nextLocalFollowVersion(localStateKey);
    mutationIdRef.current = mutationId;

    setFollowing(nextFollowing);
    setLoading(true);
    publishLocalFollowState(localStateKey, {
      following: nextFollowing,
      version: mutationVersion,
    });

    try {
      const response = await fetch(`/api/organizers/${encodeURIComponent(slug)}/follow`, {
        method: previousFollowing ? "DELETE" : "POST",
        headers: {
          Accept: "application/json",
        },
      });

      if (mutationId !== mutationIdRef.current) {
        return;
      }

      if (response.status === 401) {
        if (isStaleLocalFollowVersion(localStateKey, mutationVersion)) {
          return;
        }

        setFollowing(previousFollowing);
        publishLocalFollowState(localStateKey, {
          following: previousFollowing,
          version: mutationVersion,
        });
        setAuthenticated(false);
        window.location.assign(redirectHref);
        return;
      }

      const payload = (await response.json().catch(() => null)) as FollowPayload | null;

      if (!response.ok || !payload) {
        if (response.status === 429 || response.status >= 500) {
          setAuthenticated(true);
          scheduleFollowRetry(response, mutationVersion, nextFollowing);
          return;
        }

        if (isStaleLocalFollowVersion(localStateKey, mutationVersion)) {
          return;
        }

        setFollowing(previousFollowing);
        publishLocalFollowState(localStateKey, {
          following: previousFollowing,
          version: mutationVersion,
        });
        return;
      }

      if (isStaleLocalFollowVersion(localStateKey, mutationVersion)) {
        return;
      }

      setFollowing(Boolean(payload.following));
      setAuthenticated(Boolean(payload.authenticated));
      publishLocalFollowState(localStateKey, {
        following: Boolean(payload.following),
        version: mutationVersion,
      });
    } catch {
      if (mutationId === mutationIdRef.current) {
        setAuthenticated(true);
        scheduleFollowRetry(null, mutationVersion, nextFollowing);
      }
    } finally {
      if (mutationId === mutationIdRef.current) {
        setLoading(false);
      }
    }
  }

  function scheduleFollowRetry(
    response: Response | null,
    version: number,
    desiredFollowing: boolean,
    attempt = 1,
  ) {
    if (attempt > 3 || isStaleLocalFollowVersion(localStateKey, version)) {
      return;
    }

    const timer = setTimeout(() => {
      void retryFollowMutation(version, desiredFollowing, attempt);
    }, retryDelayMs(response, attempt));

    retryTimersRef.current.push(timer);
  }

  async function retryFollowMutation(version: number, desiredFollowing: boolean, attempt: number) {
    if (!slug?.trim() || isStaleLocalFollowVersion(localStateKey, version)) {
      return;
    }

    const localState = readLocalFollowState(localStateKey);

    if (!localState || localState.following !== desiredFollowing) {
      return;
    }

    try {
      const response = await fetch(`/api/organizers/${encodeURIComponent(slug)}/follow`, {
        method: desiredFollowing ? "POST" : "DELETE",
        headers: {
          Accept: "application/json",
        },
      });

      if (response.status === 401) {
        setAuthenticated(false);
        return;
      }

      const payload = (await response.json().catch(() => null)) as FollowPayload | null;

      if (!response.ok || !payload) {
        if (response.status === 429 || response.status >= 500) {
          scheduleFollowRetry(response, version, desiredFollowing, attempt + 1);
        }

        return;
      }

      if (isStaleLocalFollowVersion(localStateKey, version)) {
        return;
      }

      setFollowing(Boolean(payload.following));
      setAuthenticated(Boolean(payload.authenticated));
      publishLocalFollowState(localStateKey, {
        following: Boolean(payload.following),
        version,
      });
    } catch {
      scheduleFollowRetry(null, version, desiredFollowing, attempt + 1);
    }
  }

  return (
    <button
      aria-label={accessibleLabel}
      className={`content-card__publisher-action${following ? " is-active" : ""}`}
      disabled={loading}
      onClick={toggleFollow}
      title={accessibleLabel}
      type="button"
    >
      {actionLabel}
    </button>
  );
}

function readLocalFollowState(key: string): LocalFollowState | null {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(key) || "null") as {
      following?: unknown;
      version?: unknown;
      updatedAt?: unknown;
    } | null;

    if (
      !parsed
      || typeof parsed.following !== "boolean"
      || typeof parsed.updatedAt !== "number"
      || Date.now() - parsed.updatedAt > LOCAL_FOLLOW_MAX_AGE_MS
    ) {
      return null;
    }

    return {
      following: parsed.following,
      version: typeof parsed.version === "number" ? parsed.version : 0,
    };
  } catch {
    return null;
  }
}

function nextLocalFollowVersion(key: string): number {
  const currentVersion = key ? readLocalFollowState(key)?.version ?? 0 : 0;

  return Math.max(currentVersion + 1, Date.now());
}

function isStaleLocalFollowVersion(key: string, version: number | undefined): boolean {
  if (!key || typeof version !== "number") {
    return false;
  }

  return (readLocalFollowState(key)?.version ?? 0) > version;
}

function publishLocalFollowState(key: string, state: LocalFollowState) {
  if (writeLocalFollowState(key, state)) {
    notifyLocalFollowState(key, state);
  }
}

function writeLocalFollowState(key: string, state: LocalFollowState): boolean {
  if (!key) {
    return false;
  }

  if (isStaleLocalFollowVersion(key, state.version)) {
    return false;
  }

  try {
    window.localStorage.setItem(key, JSON.stringify({
      ...state,
      updatedAt: Date.now(),
    }));
    return true;
  } catch {
    return false;
  }
}

function notifyLocalFollowState(key: string, state: LocalFollowState) {
  if (!key) {
    return;
  }

  window.dispatchEvent(new CustomEvent(LOCAL_FOLLOW_EVENT, {
    detail: { key, ...state },
  }));
}

function retryDelayMs(response: Response | null, attempt: number): number {
  const retryAfter = Number(response?.headers.get("Retry-After"));

  if (Number.isFinite(retryAfter) && retryAfter > 0) {
    return Math.min(retryAfter * 1000, 60_000);
  }

  return Math.min(2_000 * attempt, 10_000);
}
