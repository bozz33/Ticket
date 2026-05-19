"use client";

import { useEffect } from "react";
import { useReportWebVitals } from "next/web-vitals";

type ClientEvent = {
  type: "web-vital" | "client-error" | "unhandled-rejection";
  name: string;
  message?: string;
  path: string;
  rating?: string;
  value?: number;
};

const enabled = process.env.NEXT_PUBLIC_OBSERVABILITY_ENABLED === "true" || process.env.NODE_ENV === "production";
const endpoint = "/api/observability/client-event";

function sendEvent(event: ClientEvent) {
  if (!enabled || typeof window === "undefined") {
    return;
  }

  const payload = JSON.stringify({
    ...event,
    path: `${window.location.pathname}${window.location.search}`,
  });

  if (navigator.sendBeacon) {
    navigator.sendBeacon(endpoint, new Blob([payload], { type: "application/json" }));
    return;
  }

  void fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: payload,
    keepalive: true,
  }).catch(() => null);
}

export function ObservabilityReporter() {
  useReportWebVitals((metric) => {
    sendEvent({
      type: "web-vital",
      name: metric.name,
      value: metric.value,
      rating: "rating" in metric ? String(metric.rating) : "",
      path: "",
    });
  });

  useEffect(() => {
    if (!enabled) {
      return;
    }

    const onError = (event: ErrorEvent) => {
      sendEvent({
        type: "client-error",
        name: event.error?.name ?? "Error",
        message: event.message,
        path: "",
      });
    };

    const onUnhandledRejection = (event: PromiseRejectionEvent) => {
      sendEvent({
        type: "unhandled-rejection",
        name: "UnhandledRejection",
        message: event.reason instanceof Error ? event.reason.message : String(event.reason ?? ""),
        path: "",
      });
    };

    window.addEventListener("error", onError);
    window.addEventListener("unhandledrejection", onUnhandledRejection);

    return () => {
      window.removeEventListener("error", onError);
      window.removeEventListener("unhandledrejection", onUnhandledRejection);
    };
  }, []);

  return null;
}
