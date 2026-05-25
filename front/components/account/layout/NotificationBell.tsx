"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useCallback, useEffect, useRef, useState } from "react";

import type { AccountNotification, AccountUser } from "@/lib/types";

import { formatNotificationDate } from "./helpers";

export function NotificationBell({ user }: { user: AccountUser | null }) {
  const router = useRouter();
  const containerRef = useRef<HTMLDivElement | null>(null);
  const mountedRef = useRef(false);
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState<AccountNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState(user?.unread_notifications_count ?? 0);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    mountedRef.current = true;

    return () => { mountedRef.current = false; };
  }, []);

  const loadNotifications = useCallback(async () => {
    if (!user) return;

    setIsLoading(true);

    try {
      const response = await fetch("/api/account/notifications", { cache: "no-store" });

      if (!response.ok) return;

      const payload = (await response.json()) as {
        notifications?: AccountNotification[];
        unreadCount?: number;
      };

      if (mountedRef.current) {
        setNotifications(payload.notifications ?? []);
        setUnreadCount(Number(payload.unreadCount ?? 0));
      }
    } finally {
      if (mountedRef.current) {
        setIsLoading(false);
      }
    }
  }, [user]);

  useEffect(() => {
    setUnreadCount(user?.unread_notifications_count ?? 0);
  }, [user?.unread_notifications_count]);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (!containerRef.current) {
        return;
      }

      if (!containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === "Escape") {
        setIsOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    document.addEventListener("keydown", handleEscape);

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
      document.removeEventListener("keydown", handleEscape);
    };
  }, []);

  async function handleToggle() {
    const nextOpen = !isOpen;
    setIsOpen(nextOpen);

    if (nextOpen) {
      await loadNotifications();
    }
  }

  async function markOneAsRead(notification: AccountNotification) {
    if (notification.read_at) {
      return;
    }

    const response = await fetch(`/api/account/notifications/${notification.id}/read`, { method: "PATCH" });

    if (!response.ok) {
      return;
    }

    const payload = (await response.json()) as {
      notification?: AccountNotification;
      unreadCount?: number;
    };

    if (mountedRef.current) {
      setUnreadCount(Number(payload.unreadCount ?? 0));
      setNotifications((current) =>
        current.map((item) => (item.id === notification.id ? payload.notification ?? { ...item, read_at: new Date().toISOString() } : item)),
      );
    }
  }

  async function openNotification(notification: AccountNotification) {
    await markOneAsRead(notification);

    setIsOpen(false);
    router.push(`/compte/notifications/${encodeURIComponent(notification.id)}`);
  }

  return (
    <div className="ac-notifications" ref={containerRef}>
      <button
        aria-label="Notifications"
        aria-expanded={isOpen}
        aria-controls="account-notifications-popover"
        className="ac-notifications__trigger"
        type="button"
        disabled={!user}
        onClick={handleToggle}
      >
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 8-3 8h18s-3-1-3-8" />
          <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        {unreadCount > 0 ? <span className="ac-notifications__count">{unreadCount > 99 ? "99+" : unreadCount}</span> : null}
      </button>

      {isOpen ? (
        <div className="ac-notifications__popover" id="account-notifications-popover" role="dialog" aria-label="Notifications">
          <div className="ac-notifications__head">
            <span>Notifications</span>
            <Link className="ac-notifications__read-all" href="/compte/notifications" onClick={() => setIsOpen(false)}>
              Tout lire
            </Link>
          </div>

          <div className="ac-notifications__list">
            {isLoading ? <p className="ac-notifications__empty">Chargement...</p> : null}
            {!isLoading && notifications.length === 0 ? <p className="ac-notifications__empty">Aucune notification</p> : null}
            {!isLoading
              ? notifications.map((notification) => (
                  <div
                    className={"ac-notifications__item" + (!notification.read_at ? " is-unread" : "")}
                    key={notification.id}
                  >
                    <span className="ac-notifications__dot" />
                    <button className="ac-notifications__body" type="button" onClick={() => openNotification(notification)}>
                      <span className="ac-notifications__title">{notification.title}</span>
                      {notification.body ? <span className="ac-notifications__text">{notification.body}</span> : null}
                      {notification.created_at ? <span className="ac-notifications__date">{formatNotificationDate(notification.created_at)}</span> : null}
                    </button>
                    <span className="ac-notifications__actions">
                      {!notification.read_at ? (
                        <button className="ac-notifications__mark-read" type="button" onClick={() => markOneAsRead(notification)}>
                          Lire
                        </button>
                      ) : null}
                      <button className="ac-notifications__open" type="button" onClick={() => openNotification(notification)}>
                        Ouvrir
                      </button>
                    </span>
                  </div>
                ))
              : null}
          </div>
        </div>
      ) : null}
    </div>
  );
}
