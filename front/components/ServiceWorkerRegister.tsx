"use client";

import { useEffect } from "react";

export function ServiceWorkerRegister() {
  useEffect(() => {
    if (typeof window === "undefined" || !("serviceWorker" in navigator)) {
      return;
    }

    const unregister = async () => {
      const registrations = await navigator.serviceWorker.getRegistrations();

      await Promise.all(registrations.map((registration) => registration.unregister()));

      if ("caches" in window) {
        const cacheKeys = await caches.keys();

        await Promise.all(cacheKeys.map((cacheKey) => caches.delete(cacheKey)));
      }
    };

    const register = async () => {
      try {
        const registration = await navigator.serviceWorker.register("/sw.js", {
          scope: "/",
          updateViaCache: "none",
        });

        await registration.update();
      } catch {
        // no-op in local dev if registration fails temporarily
      }
    };

    if (process.env.NODE_ENV !== "production") {
      void unregister();
      return;
    }

    void register();
  }, []);

  return null;
}
