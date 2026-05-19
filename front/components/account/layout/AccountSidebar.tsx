"use client";

import Link from "next/link";

import type { AccountUser } from "@/lib/types";

import { AccountAvatar } from "./AccountAvatar";
import { ACCOUNT_NAV } from "./navigation";

type AccountSidebarProps = {
  pathname: string;
  user: AccountUser | null;
  onLogout: () => void;
};

export function AccountSidebar({ pathname, user, onLogout }: AccountSidebarProps) {
  return (
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
        {ACCOUNT_NAV.map(({ href, label, icon }) => (
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

      <button className="ac-sidebar__logout" onClick={onLogout}>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
        Déconnexion
      </button>
    </aside>
  );
}
