import Link from "next/link";
import { notFound } from "next/navigation";

import { getTenantSlug } from "@/lib/auth";
import { getPublicPass } from "@/lib/data/account";
import type { AccessPassStatus } from "@/lib/types";

import "../../compte/account.css";

export const dynamic = "force-dynamic";

function formatDate(iso: string | null) {
  if (!iso) return null;
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const STATUS_CONFIG: Record<
  AccessPassStatus,
  { variant: "granted" | "denied" | "warn"; title: string; sub: string }
> = {
  active: {
    variant: "granted",
    title: "Accès autorisé",
    sub: "Ce pass est valide et prêt à être utilisé.",
  },
  used: {
    variant: "warn",
    title: "Déjà utilisé",
    sub: "Ce pass a déjà été scanné.",
  },
  revoked: {
    variant: "denied",
    title: "Pass révoqué",
    sub: "Ce pass a été révoqué et n'est plus valide.",
  },
  expired: {
    variant: "denied",
    title: "Pass expiré",
    sub: "Ce pass a dépassé sa date de validité.",
  },
};

const ICON_GRANTED = (
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M20 6 9 17l-5-5" />
  </svg>
);

const ICON_DENIED = (
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

const ICON_WARN = (
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
    <path d="M12 9v4" />
    <path d="M12 17h.01" />
  </svg>
);

const ICONS = { granted: ICON_GRANTED, denied: ICON_DENIED, warn: ICON_WARN };

const TYPE_LABELS: Record<string, string> = {
  event_ticket: "Billet événement",
  training_enrollment: "Inscription formation",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

export default async function VerifierPage({
  params,
}: {
  params: Promise<{ code: string }>;
}) {
  const { code } = await params;
  const tenantSlug = await getTenantSlug();

  const pass = await getPublicPass(tenantSlug, code);

  if (!pass) notFound();

  const config = STATUS_CONFIG[pass.status];
  const usedAt = formatDate(pass.used_at);
  const expiresAt = formatDate(pass.expires_at);

  return (
    <div className="ac-verify-wrap">
      <div className="ac-verify">
        <div className={`ac-verify__result ac-verify__result--${config.variant}`}>
          <div className={`ac-verify__icon ac-verify__icon--${config.variant}`}>
            {ICONS[config.variant]}
          </div>
          <h1 className="ac-verify__title">{config.title}</h1>
          <p className="ac-verify__sub">{config.sub}</p>

          <div className="ac-verify__details">
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">Type</span>
              <span className="ac-detail-row__value">{TYPE_LABELS[pass.type] ?? pass.type_label}</span>
            </div>
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">Porteur</span>
              <span className="ac-detail-row__value">{pass.holder_name ?? "Anonyme"}</span>
            </div>
            {usedAt && (
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Utilisé le</span>
                <span className="ac-detail-row__value">{usedAt}</span>
              </div>
            )}
            {expiresAt && (
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Expire le</span>
                <span className="ac-detail-row__value">{expiresAt}</span>
              </div>
            )}
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">ID public</span>
              <span className="ac-detail-row__value ac-detail-row__value--mono" style={{ fontSize: "0.78rem" }}>
                {pass.public_id}
              </span>
            </div>
          </div>
        </div>

        <div style={{ textAlign: "center", marginTop: "20px" }}>
          <Link href="/" className="ac-back" style={{ justifyContent: "center" }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            Retour à l&apos;accueil
          </Link>
        </div>
      </div>
    </div>
  );
}
