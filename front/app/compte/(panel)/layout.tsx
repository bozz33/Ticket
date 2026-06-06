"use client";

import { usePathname } from "next/navigation";
import type { ReactNode } from "react";

import { AccountSidebar } from "@/components/account/layout/AccountSidebar";
import { NotificationBell } from "@/components/account/layout/NotificationBell";
import { useAccountPanelSession } from "@/components/account/layout/useAccountPanelSession";

import "../account.css";

export default function AccountLayout({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const { handleLogout, sessionError, sessionExpiresSoon, user } = useAccountPanelSession(pathname);

  return (
    <div className="ac-layout shell">
      <AccountSidebar pathname={pathname} user={user} onLogout={() => void handleLogout()} />

      <main className="ac-main">
        <div className="ac-main__topbar">
          <NotificationBell user={user} />
        </div>
        {sessionExpiresSoon && !sessionError ? (
          <div className="ac-banner ac-banner--warn" role="alert">
            Votre session expirera dans 5 minutes en raison d&apos;inactivité.{" "}
            <button className="ac-banner__action" type="button" onClick={() => void handleLogout()}>
              Se déconnecter
            </button>
          </div>
        ) : null}
        {sessionError ? <div className="ac-banner ac-banner--error">{sessionError}</div> : null}
        {children}
      </main>
    </div>
  );
}
