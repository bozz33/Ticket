import Link from "next/link";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountPasses } from "@/lib/data/account";
import type { AccessPassStatus, AccessPassType } from "@/lib/types";

export const dynamic = "force-dynamic";

function formatDate(iso: string | null) {
  if (!iso) return null;
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

const STATUS_LABELS: Record<AccessPassStatus, string> = {
  active: "Actif",
  used: "Utilisé",
  revoked: "Révoqué",
  expired: "Expiré",
};

const TYPE_LABELS: Record<AccessPassType, string> = {
  event_ticket: "Billet",
  training_enrollment: "Inscription",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

const TYPE_ICON_COLOR: Record<AccessPassType, string> = {
  event_ticket: "ac-card__icon--teal",
  training_enrollment: "ac-card__icon--gold",
  stand_reservation: "ac-card__icon--dark",
  purchase_pass: "ac-card__icon--teal",
};

export default async function PassesPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const passes = token ? await getAccountPasses(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Mes passes</h1>
        <p className="ac-page-sub">Vos billets et passes d&apos;accès avec leurs QR codes.</p>
      </div>

      {passes.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <rect width="5" height="5" x="3" y="3" rx="1" />
            <rect width="5" height="5" x="16" y="3" rx="1" />
            <rect width="5" height="5" x="3" y="16" rx="1" />
            <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
            <path d="M21 21v.01" />
            <path d="M12 7v3a2 2 0 0 1-2 2H7" />
            <path d="M3 12h.01" />
          </svg>
          <p className="ac-empty__title">Aucun pass</p>
          <p className="ac-empty__text">Vos passes d&apos;accès apparaîtront ici après une commande confirmée.</p>
        </div>
      ) : (
        <div className="ac-list">
          {passes.map((pass) => {
            const expiresAt = formatDate(pass.expires_at);
            return (
              <Link key={pass.id} href={`/compte/passes/${pass.public_id}`} className="ac-card">
                <div className={`ac-card__icon ${TYPE_ICON_COLOR[pass.type]}`}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                    <path d="M13 5v2" />
                    <path d="M13 17v2" />
                    <path d="M13 11v2" />
                  </svg>
                </div>
                <div className="ac-card__body">
                  <p className="ac-card__title">{pass.offer?.name ?? TYPE_LABELS[pass.type]}</p>
                  <p className="ac-card__meta">
                    <span>{pass.holder_name ?? "Porteur anonyme"}</span>
                    {expiresAt && (
                      <>
                        <span className="ac-card__meta-sep" />
                        <span>Expire le {expiresAt}</span>
                      </>
                    )}
                  </p>
                </div>
                <div className="ac-card__right">
                  <span className={`ac-badge ac-badge--${pass.status}`}>
                    <span className="ac-badge__dot" />
                    {STATUS_LABELS[pass.status]}
                  </span>
                </div>
                <svg className="ac-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </Link>
            );
          })}
        </div>
      )}
    </>
  );
}
