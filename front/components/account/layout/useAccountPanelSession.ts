"use client";

import { useRouter } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";

import type { AccountUser } from "@/lib/types";

const ACCOUNT_IDLE_TIMEOUT_MS = 30 * 60 * 1000;

export function useAccountPanelSession(pathname: string) {
  const router = useRouter();
  const [user, setUser] = useState<AccountUser | null>(null);
  const [sessionError, setSessionError] = useState<string | null>(null);
  const idleTimeoutRef = useRef<number | null>(null);
  const logoutStartedRef = useRef(false);

  useEffect(() => {
    fetch("/api/account/me")
      .then((response) => {
        if (response.status === 401) {
          router.push(`/compte/connexion?redirect=${encodeURIComponent(pathname)}`);
          return null;
        }

        if (!response.ok) {
          return response
            .json()
            .then((data) => ({ error: data?.error ?? "Impossible de charger votre session." }))
            .catch(() => ({ error: "Impossible de charger votre session." }));
        }

        return response.json();
      })
      .then((data) => {
        if (data?.user) {
          setUser(data.user);
          setSessionError(null);
          return;
        }

        if (data?.error) {
          setSessionError(data.error);
        }
      })
      .catch(() => {
        setSessionError("Impossible de vérifier votre session pour le moment.");
      });
  }, [pathname, router]);

  useEffect(() => {
    const handleProfileUpdate = (event: Event) => {
      const detail = (event as CustomEvent<AccountUser>).detail;

      if (detail) {
        setUser(detail);
      }
    };

    window.addEventListener("account-profile-updated", handleProfileUpdate);

    return () => {
      window.removeEventListener("account-profile-updated", handleProfileUpdate);
    };
  }, []);

  const handleLogout = useCallback(async (redirectHref = "/compte/connexion") => {
    if (logoutStartedRef.current) {
      return;
    }

    logoutStartedRef.current = true;

    await fetch("/api/account/logout", { method: "POST" }).catch(() => {});
    router.replace(redirectHref);
  }, [router]);

  useEffect(() => {
    logoutStartedRef.current = false;
  }, [pathname]);

  useEffect(() => {
    if (typeof window === "undefined" || !user) {
      return;
    }

    const resetIdleTimer = () => {
      if (idleTimeoutRef.current !== null) {
        window.clearTimeout(idleTimeoutRef.current);
      }

      idleTimeoutRef.current = window.setTimeout(() => {
        void handleLogout("/compte/connexion?reason=idle");
      }, ACCOUNT_IDLE_TIMEOUT_MS);
    };

    const handleVisibilityChange = () => {
      if (!document.hidden) {
        resetIdleTimer();
      }
    };

    const activityEvents: Array<keyof WindowEventMap> = ["mousemove", "keydown", "click", "scroll", "focus", "touchstart"];

    resetIdleTimer();

    for (const eventName of activityEvents) {
      window.addEventListener(eventName, resetIdleTimer, { passive: true });
    }

    document.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      if (idleTimeoutRef.current !== null) {
        window.clearTimeout(idleTimeoutRef.current);
      }

      for (const eventName of activityEvents) {
        window.removeEventListener(eventName, resetIdleTimer);
      }

      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [handleLogout, user]);

  return {
    handleLogout,
    sessionError,
    user,
  };
}
