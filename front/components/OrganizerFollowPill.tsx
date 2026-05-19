"use client";

import { useEffect, useMemo, useState } from "react";

type FollowPayload = {
  authenticated: boolean;
  following: boolean;
  followers: number;
  message?: string;
  error?: string;
};

export function OrganizerFollowPill({
  slug,
  initialAuthenticated,
  initialFollowing = false,
}: {
  slug: string;
  initialAuthenticated?: boolean;
  initialFollowing?: boolean;
}) {
  const [authenticated, setAuthenticated] = useState<boolean | undefined>(initialAuthenticated);
  const [following, setFollowing] = useState(Boolean(initialFollowing));
  const [loading, setLoading] = useState(false);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;

    return `/compte/connexion?tenant=${encodeURIComponent(slug)}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [slug]);

  useEffect(() => {
    setAuthenticated(initialAuthenticated);
  }, [initialAuthenticated]);

  useEffect(() => {
    setFollowing(Boolean(initialFollowing));
  }, [initialFollowing]);

  async function toggleFollow() {
    if (authenticated === false) {
      setLoading(true);
      window.location.assign(redirectHref);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch(`/api/organizers/${encodeURIComponent(slug)}/follow`, {
        method: following ? "DELETE" : "POST",
        headers: {
          Accept: "application/json",
        },
      });
      const payload = (await response.json().catch(() => null)) as FollowPayload | null;

      if (response.status === 401) {
        setAuthenticated(false);
        window.location.assign(redirectHref);
        return;
      }

      if (!response.ok || !payload) {
        setLoading(false);
        return;
      }

      setFollowing(Boolean(payload.following));
      setAuthenticated(Boolean(payload.authenticated));
    } catch {
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      className={`content-card__publisher-action${following ? " is-active" : ""}`}
      disabled={loading}
      onClick={toggleFollow}
      type="button"
    >
      {loading
        ? "..."
        : following
          ? "Suivi"
          : "Suivre"}
    </button>
  );
}
