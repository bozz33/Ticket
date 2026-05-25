"use client";

import { useEffect, useMemo, useRef, useState } from "react";

type LikePayload = {
  authenticated: boolean;
  liked: boolean;
  likes: number;
  message?: string;
  error?: string;
};

type LocalLikeState = {
  liked: boolean;
  likes: number;
  version: number;
};

const LOCAL_LIKE_PREFIX = "ticket:content-like";
const LOCAL_LIKE_MAX_AGE_MS = 1000 * 60 * 60 * 24 * 30;
const LOCAL_LIKE_EVENT = "ticket:content-like-updated";

export function EventLikeButton({
  tenantSlug,
  module = "evenements",
  eventSlug,
  accountSessionKey,
  initialCount = 0,
  initialAuthenticated,
  initialLiked,
  variant = "card",
}: {
  tenantSlug: string;
  module?: string;
  eventSlug: string;
  accountSessionKey?: string;
  initialCount?: number;
  initialAuthenticated?: boolean;
  initialLiked?: boolean;
  variant?: "card" | "detail";
}) {
  const [authenticated, setAuthenticated] = useState<boolean | undefined>(initialAuthenticated);
  const [liked, setLiked] = useState(Boolean(initialLiked));
  const [likes, setLikes] = useState(initialCount);
  const [loading, setLoading] = useState(false);
  const [loginRedirecting, setLoginRedirecting] = useState(false);
  const mutationIdRef = useRef(0);
  const retryTimersRef = useRef<ReturnType<typeof setTimeout>[]>([]);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion?reason=like";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    const tenantQuery = tenantSlug?.trim() ? `&tenant=${encodeURIComponent(tenantSlug.trim())}` : "";

    return `/compte/connexion?reason=like${tenantQuery}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [tenantSlug]);

  const localStateKey = useMemo(
    () => accountSessionKey ? `${LOCAL_LIKE_PREFIX}:${accountSessionKey}:${tenantSlug}:${module}:${eventSlug}` : "",
    [accountSessionKey, eventSlug, module, tenantSlug],
  );

  useEffect(() => {
    setAuthenticated(initialAuthenticated);
  }, [initialAuthenticated]);

  useEffect(() => {
    if (typeof initialLiked === "boolean") {
      if (authenticated === true && localStateKey && typeof window !== "undefined" && readLocalLikeState(localStateKey)) {
        return;
      }

      setLiked(initialLiked);
    }
  }, [authenticated, initialLiked, localStateKey]);

  useEffect(() => {
    if (authenticated === true && localStateKey && typeof window !== "undefined" && readLocalLikeState(localStateKey)) {
      return;
    }

    setLikes(initialCount);
  }, [authenticated, initialCount, localStateKey]);

  useEffect(() => {
    if (authenticated !== true || !localStateKey || typeof window === "undefined") {
      return;
    }

    const localState = readLocalLikeState(localStateKey);

    if (!localState) {
      return;
    }

    setLiked(localState.liked);
    setLikes(localState.likes);
  }, [authenticated, localStateKey]);

  useEffect(() => {
    if (!localStateKey || typeof window === "undefined") {
      return;
    }

    function syncLocalState(event: Event) {
      const detail = (event as CustomEvent<{ key?: string; liked?: boolean; likes?: number; version?: number }>).detail;

      if (
        detail?.key !== localStateKey
        || typeof detail.liked !== "boolean"
        || typeof detail.likes !== "number"
      ) {
        return;
      }

      if (isStaleLocalLikeVersion(localStateKey, detail.version)) {
        return;
      }

      setLiked(detail.liked);
      setLikes(Math.max(0, detail.likes));
    }

    window.addEventListener(LOCAL_LIKE_EVENT, syncLocalState);

    return () => window.removeEventListener(LOCAL_LIKE_EVENT, syncLocalState);
  }, [localStateKey]);

  useEffect(() => () => {
    retryTimersRef.current.forEach((timer) => clearTimeout(timer));
    retryTimersRef.current = [];
  }, []);

  async function toggleLike(event: React.MouseEvent<HTMLButtonElement>) {
    event.preventDefault();
    event.stopPropagation();

    if (!tenantSlug?.trim()) {
      return;
    }

    if (authenticated === false) {
      setLoginRedirecting(true);
      setLoading(true);
      window.location.assign(redirectHref);
      return;
    }

    const previousLiked = liked;
    const previousLikes = likes;
    const nextLiked = !previousLiked;
    const nextLikes = Math.max(0, previousLikes + (nextLiked ? 1 : -1));
    const mutationId = mutationIdRef.current + 1;
    const mutationVersion = nextLocalLikeVersion(localStateKey);
    mutationIdRef.current = mutationId;

    setLiked(nextLiked);
    setLikes(nextLikes);
    setLoading(true);
    publishLocalLikeState(localStateKey, {
      liked: nextLiked,
      likes: nextLikes,
      version: mutationVersion,
    });

    try {
      const response = await fetch(`/api/content-likes/${encodeURIComponent(tenantSlug)}/${encodeURIComponent(module)}/${encodeURIComponent(eventSlug)}`, {
        method: previousLiked ? "DELETE" : "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      if (mutationId !== mutationIdRef.current) {
        return;
      }

      if (response.status === 401) {
        if (isStaleLocalLikeVersion(localStateKey, mutationVersion)) {
          return;
        }

        setLiked(previousLiked);
        setLikes(previousLikes);
        publishLocalLikeState(localStateKey, {
          liked: previousLiked,
          likes: previousLikes,
          version: mutationVersion,
        });
        setAuthenticated(false);
        setLoginRedirecting(true);
        window.location.assign(redirectHref);
        return;
      }

      const payload = (await response.json().catch(() => null)) as LikePayload | null;

      if (!response.ok || !payload) {
        if (response.status === 429 || response.status >= 500) {
          setAuthenticated(true);
          scheduleLikeRetry(response, mutationVersion, nextLiked);
          return;
        }

        if (isStaleLocalLikeVersion(localStateKey, mutationVersion)) {
          return;
        }

        setLiked(previousLiked);
        setLikes(previousLikes);
        publishLocalLikeState(localStateKey, {
          liked: previousLiked,
          likes: previousLikes,
          version: mutationVersion,
        });
        return;
      }

      if (isStaleLocalLikeVersion(localStateKey, mutationVersion)) {
        return;
      }

      setAuthenticated(Boolean(payload.authenticated));
      setLiked(Boolean(payload.liked));
      const resolvedLikes = typeof payload.likes === "number" ? payload.likes : likes;

      setLikes(resolvedLikes);
      publishLocalLikeState(localStateKey, {
        liked: Boolean(payload.liked),
        likes: resolvedLikes,
        version: mutationVersion,
      });
    } catch {
      if (mutationId === mutationIdRef.current) {
        setAuthenticated(true);
        scheduleLikeRetry(null, mutationVersion, nextLiked);
      }
    } finally {
      if (mutationId === mutationIdRef.current) {
        setLoading(false);
      }
    }
  }

  function scheduleLikeRetry(
    response: Response | null,
    version: number,
    desiredLiked: boolean,
    attempt = 1,
  ) {
    if (attempt > 3 || isStaleLocalLikeVersion(localStateKey, version)) {
      return;
    }

    const timer = setTimeout(() => {
      void retryLikeMutation(version, desiredLiked, attempt);
    }, retryDelayMs(response, attempt));

    retryTimersRef.current.push(timer);
  }

  async function retryLikeMutation(version: number, desiredLiked: boolean, attempt: number) {
    if (!tenantSlug?.trim() || isStaleLocalLikeVersion(localStateKey, version)) {
      return;
    }

    const localState = readLocalLikeState(localStateKey);

    if (!localState || localState.liked !== desiredLiked) {
      return;
    }

    try {
      const response = await fetch(`/api/content-likes/${encodeURIComponent(tenantSlug)}/${encodeURIComponent(module)}/${encodeURIComponent(eventSlug)}`, {
        method: desiredLiked ? "POST" : "DELETE",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      if (response.status === 401) {
        setAuthenticated(false);
        return;
      }

      const payload = (await response.json().catch(() => null)) as LikePayload | null;

      if (!response.ok || !payload) {
        if (response.status === 429 || response.status >= 500) {
          scheduleLikeRetry(response, version, desiredLiked, attempt + 1);
        }

        return;
      }

      if (isStaleLocalLikeVersion(localStateKey, version)) {
        return;
      }

      setAuthenticated(Boolean(payload.authenticated));
      setLiked(Boolean(payload.liked));
      const resolvedLikes = typeof payload.likes === "number" ? payload.likes : localState.likes;

      setLikes(resolvedLikes);
      publishLocalLikeState(localStateKey, {
        liked: Boolean(payload.liked),
        likes: resolvedLikes,
        version,
      });
    } catch {
      scheduleLikeRetry(null, version, desiredLiked, attempt + 1);
    }
  }

  return (
    <button
      aria-label={liked ? "Retirer des j'aime" : "Aimer ce contenu"}
      aria-pressed={liked}
      className={`event-like-button event-like-button--${variant}${liked ? " is-liked" : ""}`}
      disabled={loading || !tenantSlug?.trim()}
      onClick={toggleLike}
      title={authenticated === false ? "Connectez-vous pour aimer ce contenu" : liked ? "Retirer votre j'aime" : "Aimer ce contenu"}
      type="button"
    >
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 20.8 4.9 13.9a4.7 4.7 0 0 1 6.6-6.6L12 7.8l.5-.5a4.7 4.7 0 0 1 6.6 6.6Z" />
      </svg>
      <span>{loginRedirecting ? "Connexion…" : likes.toLocaleString("fr-FR")}</span>
    </button>
  );
}

function readLocalLikeState(key: string): LocalLikeState | null {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(key) || "null") as {
      liked?: unknown;
      likes?: unknown;
      version?: unknown;
      updatedAt?: unknown;
    } | null;

    if (
      !parsed
      || typeof parsed.liked !== "boolean"
      || typeof parsed.likes !== "number"
      || typeof parsed.updatedAt !== "number"
      || Date.now() - parsed.updatedAt > LOCAL_LIKE_MAX_AGE_MS
    ) {
      return null;
    }

    return {
      liked: parsed.liked,
      likes: Math.max(0, parsed.likes),
      version: typeof parsed.version === "number" ? parsed.version : 0,
    };
  } catch {
    return null;
  }
}

function nextLocalLikeVersion(key: string): number {
  const currentVersion = key ? readLocalLikeState(key)?.version ?? 0 : 0;

  return Math.max(currentVersion + 1, Date.now());
}

function isStaleLocalLikeVersion(key: string, version: number | undefined): boolean {
  if (!key || typeof version !== "number") {
    return false;
  }

  return (readLocalLikeState(key)?.version ?? 0) > version;
}

function publishLocalLikeState(key: string, state: LocalLikeState) {
  if (writeLocalLikeState(key, state)) {
    notifyLocalLikeState(key, state);
  }
}

function writeLocalLikeState(key: string, state: LocalLikeState): boolean {
  if (!key) {
    return false;
  }

  if (isStaleLocalLikeVersion(key, state.version)) {
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

function notifyLocalLikeState(key: string, state: LocalLikeState) {
  if (!key) {
    return;
  }

  window.dispatchEvent(new CustomEvent(LOCAL_LIKE_EVENT, {
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
