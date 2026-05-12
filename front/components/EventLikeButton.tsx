"use client";

import { useEffect, useMemo, useState } from "react";

type LikePayload = {
  authenticated: boolean;
  liked: boolean;
  likes: number;
  message?: string;
  error?: string;
};

export function EventLikeButton({
  tenantSlug,
  eventSlug,
  initialCount = 0,
  initialAuthenticated,
  initialLiked,
  variant = "card",
}: {
  tenantSlug: string;
  eventSlug: string;
  initialCount?: number;
  initialAuthenticated?: boolean;
  initialLiked?: boolean;
  variant?: "card" | "detail";
}) {
  const [authenticated, setAuthenticated] = useState(Boolean(initialAuthenticated));
  const [liked, setLiked] = useState(Boolean(initialLiked));
  const [likes, setLikes] = useState(initialCount);
  const [loading, setLoading] = useState(false);
  const [loginRedirecting, setLoginRedirecting] = useState(false);

  const shouldHydrateStatus = initialAuthenticated === undefined
    || (initialAuthenticated === true && initialLiked === undefined);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion?reason=like";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    const tenantQuery = tenantSlug?.trim() ? `&tenant=${encodeURIComponent(tenantSlug.trim())}` : "";

    return `/compte/connexion?reason=like${tenantQuery}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [tenantSlug]);

  useEffect(() => {
    setAuthenticated(Boolean(initialAuthenticated));
  }, [initialAuthenticated]);

  useEffect(() => {
    setLiked(Boolean(initialLiked));
  }, [initialLiked]);

  useEffect(() => {
    setLikes(initialCount);
  }, [initialCount]);

  useEffect(() => {
    if (!shouldHydrateStatus) {
      return;
    }

    let active = true;

    async function loadStatus() {
      try {
        const response = await fetch(`/api/events/${encodeURIComponent(tenantSlug)}/${encodeURIComponent(eventSlug)}/like`, {
          cache: "no-store",
          headers: {
            Accept: "application/json",
          },
        });
        const payload = (await response.json().catch(() => null)) as LikePayload | null;

        if (!active || !payload) {
          return;
        }

        setAuthenticated(Boolean(payload.authenticated));
        setLiked(Boolean(payload.liked));
        setLikes(typeof payload.likes === "number" ? payload.likes : initialCount);
      } catch {}
    }

    void loadStatus();

    return () => {
      active = false;
    };
  }, [eventSlug, initialCount, shouldHydrateStatus, tenantSlug]);

  async function toggleLike(event: React.MouseEvent<HTMLButtonElement>) {
    event.preventDefault();
    event.stopPropagation();

    if (!tenantSlug?.trim()) {
      return;
    }

    if (!authenticated) {
      setLoginRedirecting(true);
      setLoading(true);
      window.location.assign(redirectHref);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch(`/api/events/${encodeURIComponent(tenantSlug)}/${encodeURIComponent(eventSlug)}/like`, {
        method: liked ? "DELETE" : "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
      });

      if (response.status === 401) {
        setAuthenticated(false);
        setLoginRedirecting(true);
        window.location.assign(redirectHref);
        return;
      }

      const payload = (await response.json().catch(() => null)) as LikePayload | null;

      if (!response.ok || !payload) {
        setLoading(false);
        return;
      }

      setAuthenticated(Boolean(payload.authenticated));
      setLiked(Boolean(payload.liked));
      setLikes(typeof payload.likes === "number" ? payload.likes : likes);
    } catch {
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      aria-label={liked ? "Retirer des j'aime" : "Aimer cet événement"}
      aria-pressed={liked}
      className={`event-like-button event-like-button--${variant}${liked ? " is-liked" : ""}`}
      disabled={loading || !tenantSlug?.trim()}
      onClick={toggleLike}
      title={!authenticated ? "Connectez-vous pour aimer cet événement" : liked ? "Retirer votre j'aime" : "Aimer cet événement"}
      type="button"
    >
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 20.8 4.9 13.9a4.7 4.7 0 0 1 6.6-6.6L12 7.8l.5-.5a4.7 4.7 0 0 1 6.6 6.6Z" />
      </svg>
      <span>{loginRedirecting ? "Connexion…" : likes.toLocaleString("fr-FR")}</span>
    </button>
  );
}
