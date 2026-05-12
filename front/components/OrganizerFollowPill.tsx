"use client";

import { useEffect, useMemo, useState } from "react";

type FollowPayload = {
  authenticated: boolean;
  following: boolean;
  followers: number;
  message?: string;
  error?: string;
};

let accountAuthenticationProbe: Promise<boolean | null> | null = null;
const organizerFollowProbeCache = new Map<string, Promise<FollowPayload | null>>();

async function resolveAccountAuthenticationState(): Promise<boolean | null> {
  if (!accountAuthenticationProbe) {
    accountAuthenticationProbe = fetch("/api/account/me", {
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    })
      .then((response) => {
        if (response.ok) {
          return true;
        }

        if (response.status === 401) {
          return false;
        }

        return null;
      })
      .catch(() => null);
  }

  return accountAuthenticationProbe;
}

async function resolveOrganizerFollowState(slug: string): Promise<FollowPayload | null> {
  const cacheKey = slug.trim();

  if (!cacheKey) {
    return null;
  }

  if (!organizerFollowProbeCache.has(cacheKey)) {
    organizerFollowProbeCache.set(cacheKey, fetch(`/api/organizers/${encodeURIComponent(cacheKey)}/follow`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    })
      .then((response) => response.json().catch(() => null) as Promise<FollowPayload | null>)
      .catch(() => null));
  }

  return organizerFollowProbeCache.get(cacheKey) ?? null;
}

export function OrganizerFollowPill({
  slug,
  initialAuthenticated,
}: {
  slug: string;
  initialAuthenticated?: boolean;
}) {
  const [authenticated, setAuthenticated] = useState(Boolean(initialAuthenticated));
  const [following, setFollowing] = useState(false);
  const [loading, setLoading] = useState(false);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;

    return `/compte/connexion?tenant=${encodeURIComponent(slug)}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [slug]);

  useEffect(() => {
    setAuthenticated(Boolean(initialAuthenticated));
  }, [initialAuthenticated]);

  useEffect(() => {
    let active = true;

    async function loadStatus() {
      try {
        const accountAuthenticated = initialAuthenticated === undefined
          ? await resolveAccountAuthenticationState()
          : initialAuthenticated;

        if (!active) {
          return;
        }

        if (accountAuthenticated === false) {
          setAuthenticated(false);
          setFollowing(false);
          return;
        }

        const payload = await resolveOrganizerFollowState(slug);

        if (!active || !payload) {
          return;
        }

        setAuthenticated(Boolean(payload.authenticated));
        setFollowing(Boolean(payload.following));
      } catch {}
    }

    void loadStatus();

    return () => {
      active = false;
    };
  }, [initialAuthenticated, slug]);

  async function toggleFollow() {
    if (!authenticated) {
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

      organizerFollowProbeCache.set(slug, Promise.resolve(payload));
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
