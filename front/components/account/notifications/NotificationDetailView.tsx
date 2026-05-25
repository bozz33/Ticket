"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import type { AccountNotification } from "@/lib/types";

import { formatNotificationDate } from "../layout/helpers";

export function NotificationDetailView({ notification }: { notification: AccountNotification }) {
  const [readAt, setReadAt] = useState(notification.read_at);

  useEffect(() => {
    if (readAt) {
      return;
    }

    let isMounted = true;

    fetch(`/api/account/notifications/${encodeURIComponent(notification.id)}/read`, { method: "PATCH" })
      .then(async (response) => {
        if (!response.ok) {
          return;
        }

        const payload = (await response.json()) as { notification?: AccountNotification };

        if (isMounted) {
          setReadAt(payload.notification?.read_at ?? new Date().toISOString());
        }
      })
      .catch(() => undefined);

    return () => {
      isMounted = false;
    };
  }, [notification.id, readAt]);

  return (
    <>
      <Link href="/compte/notifications" className="ac-back">
        <svg
          fill="none"
          height="16"
          stroke="currentColor"
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="2"
          viewBox="0 0 24 24"
          width="16"
        >
          <path d="m15 18-6-6 6-6" />
        </svg>
        Retour aux notifications
      </Link>

      <article className="ac-notification-detail">
        <div className="ac-notification-detail__head">
          <div>
            <p className="eyebrow">Notification</p>
            <h1>{notification.title}</h1>
          </div>
          <span className={`ac-badge ${readAt ? "ac-badge--issued" : "ac-badge--refund_pending"}`}>
            <span className="ac-badge__dot" />
            {readAt ? "Lu" : "Nouveau"}
          </span>
        </div>

        <p className="ac-notification-detail__body">
          {notification.body || "Cette notification ne contient pas de message complémentaire."}
        </p>

        <dl className="ac-notification-detail__meta">
          <div>
            <dt>Reçue le</dt>
            <dd>{formatNotificationDate(notification.created_at) || "—"}</dd>
          </div>
          <div>
            <dt>Lecture</dt>
            <dd>{readAt ? formatNotificationDate(readAt) : "Non lue"}</dd>
          </div>
        </dl>

        {notification.action_url ? (
          <div className="ac-notification-detail__actions">
            <Link className="button" href={notification.action_url}>
              Ouvrir l’action liée
            </Link>
          </div>
        ) : null}
      </article>
    </>
  );
}
