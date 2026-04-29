"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import type { ReactNode } from "react";
import { useEffect, useState } from "react";

import "../account.css";

interface User {
  id: number;
  name: string;
  email: string;
}

function initials(name: string): string {
  return name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
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
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    fetch("/api/account/me")
      .then((r) => {
        if (r.status === 401) {
          router.push("/compte/connexion");
          return null;
        }
        return r.ok ? r.json() : null;
      })
      .then((data) => {
        if (data?.user) setUser(data.user);
      })
      .catch(() => {});
  }, [router]);

  async function handleLogout() {
    await fetch("/api/account/logout", { method: "POST" });
    router.push("/compte/connexion");
  }

  return (
    <div className="ac-layout shell">
      <aside className="ac-sidebar">
        <div className="ac-sidebar__user">
          <div className="ac-sidebar__avatar">
            {user ? initials(user.name) : "?"}
          </div>
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

        <button className="ac-sidebar__logout" onClick={handleLogout}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          Déconnexion
        </button>
      </aside>

      <main className="ac-main">{children}</main>
    </div>
  );
}
