"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import type { AccountNotification } from "@/lib/types";

import { formatNotificationDate } from "../layout/helpers";

export function NotificationsListView({
  initialNotifications,
  initialUnreadCount,
}: {
  initialNotifications: AccountNotification[];
  initialUnreadCount: number;
}) {
  const [notifications, setNotifications] = useState(initialNotifications);
  const [unreadCount, setUnreadCount] = useState(initialUnreadCount);
  const hasUnread = unreadCount > 0;

  const orderedNotifications = useMemo(
    () => [...notifications].sort((left, right) => String(right.created_at ?? "").localeCompare(String(left.created_at ?? ""))),
    [notifications],
  );

  async function markAllAsRead() {
    const response = await fetch("/api/account/notifications", { method: "PATCH" });

    if (!response.ok) {
      return;
    }

    const payload = (await response.json()) as { unreadCount?: number };
    setUnreadCount(Number(payload.unreadCount ?? 0));
    setNotifications((current) => current.map((notification) => ({
      ...notification,
      read_at: notification.read_at ?? new Date().toISOString(),
    })));
  }

  return (
    <>
      <div className="ac-page-header ac-page-header--split">
        <div>
          <h1 className="ac-page-title">Notifications</h1>
          <p className="ac-page-sub">Retrouvez les alertes de commande, remboursement, profil et sécurité de votre compte.</p>
        </div>
        <button className="button button--ghost" disabled={!hasUnread} type="button" onClick={markAllAsRead}>
          Tout marquer comme lu
        </button>
      </div>

      <section className="ac-notification-center">
        {orderedNotifications.length === 0 ? (
          <div className="ac-empty">
            <p className="ac-empty__title">Aucune notification</p>
            <p>Vos messages importants apparaîtront ici dès qu’une action sera disponible.</p>
          </div>
        ) : null}

        {orderedNotifications.map((notification) => (
          <Link
            className={`ac-notification-row${notification.read_at ? "" : " is-unread"}`}
            href={`/compte/notifications/${encodeURIComponent(notification.id)}`}
            key={notification.id}
          >
            <span className="ac-notification-row__dot" />
            <span className="ac-notification-row__content">
              <strong>{notification.title}</strong>
              {notification.body ? <span>{notification.body}</span> : null}
              <small>{formatNotificationDate(notification.created_at)}</small>
            </span>
            <span className="ac-notification-row__status">{notification.read_at ? "Lu" : "Nouveau"}</span>
          </Link>
        ))}
      </section>
    </>
  );
}
