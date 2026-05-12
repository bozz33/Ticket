"use client";

import { useEffect, useMemo, useState } from "react";

type FollowPayload = {
  authenticated: boolean;
  following: boolean;
  followers: number;
  message?: string;
  error?: string;
};

export function OrganizerFollowCard({
  slug,
  organizerName,
  initialFollowers,
  initialAuthenticated,
  initialFollowing,
}: {
  slug: string;
  organizerName: string;
  initialFollowers: number;
  initialAuthenticated?: boolean;
  initialFollowing?: boolean;
}) {
  const [authenticated, setAuthenticated] = useState(Boolean(initialAuthenticated));
  const [following, setFollowing] = useState(Boolean(initialFollowing));
  const [followers, setFollowers] = useState(initialFollowers);
  const [loading, setLoading] = useState(false);

  const redirectHref = useMemo(() => {
    if (typeof window === "undefined") {
      return "/compte/connexion";
    }

    const redirectTarget = `${window.location.pathname}${window.location.search}${window.location.hash}`;

    return `/compte/connexion?tenant=${encodeURIComponent(slug)}&redirect=${encodeURIComponent(redirectTarget)}`;
  }, [slug]);

  useEffect(() => {
    if (typeof initialAuthenticated === "boolean" && typeof initialFollowing === "boolean") {
      setAuthenticated(initialAuthenticated);
      setFollowing(initialFollowing);
      setFollowers(initialFollowers);
      return;
    }

    let active = true;

    async function loadStatus() {
      try {
        const response = await fetch(`/api/organizers/${encodeURIComponent(slug)}/follow`, {
          cache: "no-store",
          headers: {
            Accept: "application/json",
          },
        });
        const payload = (await response.json().catch(() => null)) as FollowPayload | null;

        if (!active || !payload) {
          return;
        }

        setAuthenticated(Boolean(payload.authenticated));
        setFollowing(Boolean(payload.following));
        setFollowers(typeof payload.followers === "number" ? payload.followers : initialFollowers);
      } catch {}
    }

    void loadStatus();

    return () => {
      active = false;
    };
  }, [initialAuthenticated, initialFollowers, initialFollowing, slug]);

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

      setAuthenticated(Boolean(payload.authenticated));
      setFollowing(Boolean(payload.following));
      setFollowers(typeof payload.followers === "number" ? payload.followers : followers);
    } catch {
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="organizer-follow-card">
      <span className="organizer-follow-card__eyebrow">Communauté</span>
      <strong className="organizer-follow-card__count">{followers.toLocaleString("fr-FR")}</strong>
      <span className="organizer-follow-card__label">
        abonné{followers === 1 ? "" : "s"} à {organizerName}
      </span>
      <button
        className={`button button--full${following ? " button--ghost-light" : ""}`}
        disabled={loading}
        onClick={toggleFollow}
        type="button"
      >
        {loading
          ? "Mise à jour..."
          : following
            ? "Abonné"
            : "Suivre"}
      </button>
    </div>
  );
}
