"use client";

import { useRouter } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";

import type { AccountUser } from "@/lib/types";

const ACCOUNT_IDLE_TIMEOUT_MS = 30 * 60 * 1000;
const ACCOUNT_SESSION_REFRESH_MS = 60 * 1000;

export function useAccountPanelSession(pathname: string) {
  const router = useRouter();
  const [user, setUser] = useState<AccountUser | null>(null);
  const [sessionError, setSessionError] = useState<string | null>(null);
  const idleTimeoutRef = useRef<number | null>(null);
  const lastSessionCheckRef = useRef(0);
  const logoutStartedRef = useRef(false);
  const userRef = useRef<AccountUser | null>(null);

  useEffect(() => {
    userRef.current = user;
  }, [user]);

  useEffect(() => {
    if (userRef.current && Date.now() - lastSessionCheckRef.current < ACCOUNT_SESSION_REFRESH_MS) {
      setSessionError(null);
      return;
    }

    const controller = new AbortController();

    async function loadSession() {
      try {
        const response = await fetch("/api/account/me", { signal: controller.signal });

        if (response.status === 401) {
          lastSessionCheckRef.current = 0;
          // Clear server-side cookies before redirecting so the middleware does not
          // repeatedly serve protected pages on the next navigation.
          await fetch("/api/account/logout", { method: "POST" }).catch(() => {});
          router.push(`/compte/connexion?redirect=${encodeURIComponent(pathname)}`);
          return;
        }

        if (!response.ok) {
          const data = (await response.json().catch(() => null)) as { error?: string } | null;

          setSessionError(data?.error ?? "Impossible de charger votre session.");
          return;
        }

        const data = (await response.json()) as { user?: AccountUser };

        if (data?.user) {
          setUser(data.user);
          lastSessionCheckRef.current = Date.now();
          setSessionError(null);
        }
      } catch (error) {
        if (error instanceof Error && error.name === "AbortError") {
          return;
        }

        setSessionError("Impossible de vérifier votre session pour le moment.");
      }
    }

    void loadSession();

    return () => {
      controller.abort();
    };
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
    lastSessionCheckRef.current = 0;
    userRef.current = null;
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
