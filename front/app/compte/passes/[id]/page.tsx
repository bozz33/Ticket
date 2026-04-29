import Link from "next/link";
import { notFound } from "next/navigation";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountPass } from "@/lib/data/account";
import type { AccessPassStatus, AccessPassType } from "@/lib/types";
import { QrCode } from "./QrCode";

export const dynamic = "force-dynamic";

function formatDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const STATUS_LABELS: Record<AccessPassStatus, string> = {
  active: "Actif",
  used: "Utilisé",
  revoked: "Révoqué",
  expired: "Expiré",
};

const TYPE_LABELS: Record<AccessPassType, string> = {
  event_ticket: "Billet événement",
  training_enrollment: "Inscription formation",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

export default async function PassDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const pass = await getAccountPass(tenantSlug, token, id);
  if (!pass) notFound();

  const qrPayload = JSON.stringify({
    code: pass.access_code,
    type: pass.type,
    public_id: pass.public_id,
  });

  const isConsumable = pass.status === "active";
  const isUsed = pass.status === "used";
  const isRevoked = pass.status === "revoked";

  return (
    <>
      <Link href="/compte/passes" className="ac-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="m15 18-6-6 6-6" />
        </svg>
        Retour aux passes
      </Link>

      <div className="ac-page-header">
        <h1 className="ac-page-title">{pass.offer?.name ?? TYPE_LABELS[pass.type]}</h1>
        <p className="ac-page-sub">
          <span className={`ac-badge ac-badge--${pass.status}`}>
            <span className="ac-badge__dot" />
            {STATUS_LABELS[pass.status]}
          </span>
        </p>
      </div>

      <div className="ac-detail">
        <div>
          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Informations du pass</span>
            </div>
            <div className="ac-detail-panel__body">
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Type</span>
                <span className="ac-detail-row__value">{TYPE_LABELS[pass.type]}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Porteur</span>
                <span className="ac-detail-row__value">{pass.holder_name ?? "—"}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">E-mail porteur</span>
                <span className="ac-detail-row__value">{pass.holder_email ?? "—"}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Créé le</span>
                <span className="ac-detail-row__value">{formatDate(pass.created_at)}</span>
              </div>
              {pass.expires_at && (
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Expire le</span>
                  <span className="ac-detail-row__value">{formatDate(pass.expires_at)}</span>
                </div>
              )}
              {isUsed && (
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Utilisé le</span>
                  <span className="ac-detail-row__value">{formatDate(pass.used_at)}</span>
                </div>
              )}
              {isRevoked && (
                <>
                  <div className="ac-detail-row">
                    <span className="ac-detail-row__label">Révoqué le</span>
                    <span className="ac-detail-row__value">{formatDate(pass.revoked_at)}</span>
                  </div>
                  {pass.revocation_reason && (
                    <div className="ac-detail-row">
                      <span className="ac-detail-row__label">Motif</span>
                      <span className="ac-detail-row__value">{pass.revocation_reason}</span>
                    </div>
                  )}
                </>
              )}
              {pass.order && (
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Commande</span>
                  <Link
                    href={`/compte/commandes/${pass.order.reference}`}
                    className="ac-detail-row__value"
                    style={{ textDecoration: "underline" }}
                  >
                    {pass.order.reference}
                  </Link>
                </div>
              )}
            </div>
          </div>
        </div>

        <div>
          <div className="ac-qr-panel">
            {isConsumable ? (
              <>
                <p className="ac-qr-panel__label">Code QR d&apos;accès</p>
                <div className="ac-qr-wrap">
                  <QrCode value={qrPayload} />
                </div>
                <p className="ac-qr-code-text">{pass.access_code.slice(0, 16)}…</p>
              </>
            ) : isUsed ? (
              <>
                <p className="ac-qr-panel__label">Pass utilisé</p>
                <div className="ac-qr-status">
                  <div className="ac-qr-status__icon ac-qr-status__icon--warn">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M20 12V22H4V12" />
                      <path d="M22 7H2v5h20V7z" />
                      <path d="M12 22V7" />
                      <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                      <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                    </svg>
                  </div>
                  <p className="ac-qr-status__title">Déjà scanné</p>
                  <p className="ac-qr-status__sub">Ce pass a été utilisé le {formatDate(pass.used_at)}.</p>
                </div>
              </>
            ) : (
              <>
                <p className="ac-qr-panel__label">Pass non disponible</p>
                <div className="ac-qr-status">
                  <div className="ac-qr-status__icon ac-qr-status__icon--bad">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="15" y1="9" x2="9" y2="15" />
                      <line x1="9" y1="9" x2="15" y2="15" />
                    </svg>
                  </div>
                  <p className="ac-qr-status__title">{STATUS_LABELS[pass.status]}</p>
                  {isRevoked && pass.revocation_reason && (
                    <p className="ac-qr-status__sub">{pass.revocation_reason}</p>
                  )}
                </div>
              </>
            )}
          </div>

          <div style={{ marginTop: "16px", textAlign: "center" }}>
            <Link
              href={`/verifier/${pass.access_code}`}
              className="ac-back"
              style={{ justifyContent: "center" }}
              target="_blank"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
              Vérifier ce pass publiquement
            </Link>
          </div>
        </div>
      </div>
    </>
  );
}
