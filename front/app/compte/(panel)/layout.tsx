"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import type { ReactNode } from "react";
import { useCallback, useEffect, useRef, useState } from "react";

import type { AccountNotification, AccountUser } from "@/lib/types";

import "../account.css";

const ACCOUNT_IDLE_TIMEOUT_MS = 30 * 60 * 1000;

function initials(name: string): string {
  return name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
}

function AccountAvatar({ user, className }: { user: AccountUser | null; className: string }) {
  const avatarVersion = user?.avatar_url?.trim() ?? "";
  const avatarSrc = avatarVersion ? `/api/account/avatar-image?v=${encodeURIComponent(avatarVersion)}` : null;
  const [hasImageError, setHasImageError] = useState(false);

  useEffect(() => {
    setHasImageError(false);
  }, [avatarSrc]);

  if (avatarSrc && !hasImageError) {
    return (
      <img
        alt={user?.name ? `Photo de ${user.name}` : "Photo de profil"}
        className={className}
        src={avatarSrc}
        onError={() => setHasImageError(true)}
      />
    );
  }

  return <div className={className}>{user ? initials(user.name) : "?"}</div>;
}

function formatNotificationDate(value: string | null): string {
  if (!value) return "";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return "";

  return new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

function NotificationBell({ user }: { user: AccountUser | null }) {
  const router = useRouter();
  const containerRef = useRef<HTMLDivElement | null>(null);
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState<AccountNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState(user?.unread_notifications_count ?? 0);
  const [isLoading, setIsLoading] = useState(false);

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

      setNotifications(payload.notifications ?? []);
      setUnreadCount(Number(payload.unreadCount ?? 0));
    } finally {
      setIsLoading(false);
    }
  }, [user]);

  useEffect(() => {
    setUnreadCount(user?.unread_notifications_count ?? 0);
  }, [user?.unread_notifications_count]);

  useEffect(() => {
    if (user) {
      loadNotifications();
    }
  }, [loadNotifications, user]);

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

  async function markAllAsRead() {
    const response = await fetch("/api/account/notifications", { method: "PATCH" });

    if (!response.ok) return;

    const payload = (await response.json()) as { unreadCount?: number };
    setUnreadCount(Number(payload.unreadCount ?? 0));
    setNotifications((current) => current.map((item) => ({ ...item, read_at: item.read_at ?? new Date().toISOString() })));
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

    setUnreadCount(Number(payload.unreadCount ?? 0));
    setNotifications((current) =>
      current.map((item) => (item.id === notification.id ? payload.notification ?? { ...item, read_at: new Date().toISOString() } : item)),
    );
  }

  async function openNotification(notification: AccountNotification) {
    await markOneAsRead(notification);

    if (notification.action_url) {
      setIsOpen(false);
      router.push(notification.action_url);
    }
  }

  return (
    <div className="ac-notifications" ref={containerRef}>
      <button
        aria-label="Notifications"
        aria-expanded={isOpen}
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
        <div className="ac-notifications__popover">
          <div className="ac-notifications__head">
            <span>Notifications</span>
            {unreadCount > 0 ? (
              <button className="ac-notifications__read-all" type="button" onClick={markAllAsRead}>
                Tout lire
              </button>
            ) : null}
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
                    <span className="ac-notifications__body">
                      <span className="ac-notifications__title">{notification.title}</span>
                      {notification.body ? <span className="ac-notifications__text">{notification.body}</span> : null}
                      {notification.created_at ? <span className="ac-notifications__date">{formatNotificationDate(notification.created_at)}</span> : null}
                    </span>
                    <span className="ac-notifications__actions">
                      {!notification.read_at ? (
                        <button className="ac-notifications__mark-read" type="button" onClick={() => markOneAsRead(notification)}>
                          Lire
                        </button>
                      ) : null}
                      {notification.action_url ? (
                        <button className="ac-notifications__open" type="button" onClick={() => openNotification(notification)}>
                          Ouvrir
                        </button>
                      ) : null}
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

const NAV = [
  {
    href: "/compte/commandes",
    label: "Commandes",
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
        <line x1="3" x2="21" y1="6" y2="6" />
        <path d="M16 10a4 4 0 0 1-8 0" />
      </svg>
    ),
  },
  {
    href: "/compte/recus",
    label: "Reçus",
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="9" y1="13" x2="15" y2="13" />
        <line x1="9" y1="17" x2="15" y2="17" />
      </svg>
    ),
  },
  {
    href: "/compte/passes",
    label: "Mes passes",
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect width="5" height="5" x="3" y="3" rx="1" />
        <rect width="5" height="5" x="16" y="3" rx="1" />
        <rect width="5" height="5" x="3" y="16" rx="1" />
        <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
        <path d="M21 21v.01" />
        <path d="M12 7v3a2 2 0 0 1-2 2H7" />
        <path d="M3 12h.01" />
        <path d="M12 3h.01" />
        <path d="M12 16v.01" />
        <path d="M16 12h1" />
        <path d="M21 12v.01" />
        <path d="M12 21v-1" />
      </svg>
    ),
  },
  {
    href: "/compte/profil",
    label: "Mon profil",
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="8" r="5" />
        <path d="M20 21a8 8 0 1 0-16 0" />
      </svg>
    ),
  },
];

export default function AccountLayout({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<AccountUser | null>(null);
  const [sessionError, setSessionError] = useState<string | null>(null);
  const idleTimeoutRef = useRef<number | null>(null);
  const logoutStartedRef = useRef(false);

  useEffect(() => {
    fetch("/api/account/me")
      .then((r) => {
        if (r.status === 401) {
          router.push(`/compte/connexion?redirect=${encodeURIComponent(pathname)}`);
          return null;
        }

        if (!r.ok) {
          return r
            .json()
            .then((data) => ({ error: data?.error ?? "Impossible de charger votre session." }))
            .catch(() => ({ error: "Impossible de charger votre session." }));
        }

        return r.json();
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

  return (
    <div className="ac-layout shell">
      <aside className="ac-sidebar">
        <div className="ac-sidebar__user">
          <AccountAvatar user={user} className="ac-sidebar__avatar" />
          {user ? (
            <>
              <p className="ac-sidebar__name">{user.name}</p>
              <p className="ac-sidebar__email">{user.email}</p>
            </>
          ) : (
            <p className="ac-sidebar__name">Chargement…</p>
          )}
        </div>

        <nav className="ac-sidebar__nav">
          {NAV.map(({ href, label, icon }) => (
            <Link
              key={href}
              href={href}
              className={
                "ac-sidebar__nav-item" +
                (pathname.startsWith(href) ? " is-active" : "")
              }
            >
              {icon}
              {label}
            </Link>
          ))}
        </nav>

        <div className="ac-sidebar__divider" />

        <button className="ac-sidebar__logout" onClick={() => void handleLogout()}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          Déconnexion
        </button>
      </aside>

      <main className="ac-main">
        <div className="ac-main__topbar">
          <NotificationBell user={user} />
        </div>
        {sessionError ? <div className="ac-banner ac-banner--error">{sessionError}</div> : null}
        {children}
      </main>
    </div>
  );
}
