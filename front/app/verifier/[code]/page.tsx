import Link from "next/link";

import { getTenantSlug } from "@/lib/auth";
import { getPublicPass } from "@/lib/data/account";
import type { AccessPassStatus } from "@/lib/types";

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
  active: "Valide",
  used: "Déjà utilisé",
  revoked: "Révoqué",
  expired: "Expiré",
};

const STATUS_COPY: Record<AccessPassStatus, string> = {
  active: "Ce pass est actif et peut être accepté à l'entrée si l'identité correspond.",
  used: "Ce pass a déjà été consommé. Il ne doit plus être accepté une seconde fois.",
  revoked: "Ce pass a été révoqué et ne doit pas être accepté.",
  expired: "Ce pass est expiré et ne doit plus être accepté.",
};

export default async function VerifyPassPage({
  params,
}: {
  params: Promise<{ code: string }>;
}) {
  const { code } = await params;
  const tenantSlug = await getTenantSlug();

  if (!tenantSlug) {
    return (
      <section className="section">
        <div className="shell ac-verify-wrap">
          <div className="ac-verify">
            <div className="ac-verify__result ac-verify__result--denied">
              <div className="ac-verify__icon ac-verify__icon--denied">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="15" y1="9" x2="9" y2="15" />
                  <line x1="9" y1="9" x2="15" y2="15" />
                </svg>
              </div>
              <h1 className="ac-verify__title">Vérification indisponible</h1>
              <p className="ac-verify__sub">Le tenant public n&apos;est pas configuré pour cette vérification.</p>
              <div style={{ marginTop: "20px" }}>
                <Link href="/contact" className="button button--full">
                  Contacter le support
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    );
  }

  const pass = await getPublicPass(tenantSlug, code);

  if (!pass) {
    return (
      <section className="section">
        <div className="shell ac-verify-wrap">
          <div className="ac-verify">
            <div className="ac-verify__result ac-verify__result--denied">
              <div className="ac-verify__icon ac-verify__icon--denied">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="15" y1="9" x2="9" y2="15" />
                  <line x1="9" y1="9" x2="15" y2="15" />
                </svg>
              </div>
              <h1 className="ac-verify__title">Pass introuvable</h1>
              <p className="ac-verify__sub">Le code fourni ne correspond à aucun pass actif sur cet espace public.</p>
              <div style={{ marginTop: "20px" }}>
                <Link href="/" className="button button--full">
                  Retour à l&apos;accueil
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    );
  }

  const statusTone = pass.status === "active" ? "active" : pass.status;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Vérification publique</p>
          <h1>{STATUS_LABELS[pass.status]}</h1>
          <p>{STATUS_COPY[pass.status]}</p>
          <div className="page-hero__pills">
            <span>{pass.type_label}</span>
            <span className={`ac-badge ac-badge--${statusTone}`}>
              <span className="ac-badge__dot" />
              {STATUS_LABELS[pass.status]}
            </span>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell" style={{ maxWidth: "960px" }}>
          <div className="ac-detail" style={{ gridTemplateColumns: "1.2fr 0.8fr" }}>
            <div className="ac-detail-panel">
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Informations du pass</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Type</span>
                  <span className="ac-detail-row__value">{pass.type_label}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Statut</span>
                  <span className={`ac-badge ac-badge--${statusTone}`}>
                    <span className="ac-badge__dot" />
                    {STATUS_LABELS[pass.status]}
                  </span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Titulaire</span>
                  <span className="ac-detail-row__value">{pass.holder_name ?? "Non renseigné"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Identifiant public</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{pass.public_id}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Code</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{pass.qr_payload.code}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Expire le</span>
                  <span className="ac-detail-row__value">{formatDate(pass.expires_at)}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Utilisé le</span>
                  <span className="ac-detail-row__value">{formatDate(pass.used_at)}</span>
                </div>
              </div>
            </div>

            <div className="ac-detail-panel">
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Décision</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-qr-status">
                  <div className={`ac-qr-status__icon ${pass.status === "active" ? "ac-qr-status__icon--ok" : pass.status === "used" ? "ac-qr-status__icon--warn" : "ac-qr-status__icon--bad"}`}>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      {pass.status === "active" ? (
                        <path d="M20 7 9.5 17.5 4 12" />
                      ) : pass.status === "used" ? (
                        <>
                          <circle cx="12" cy="12" r="10" />
                          <path d="M12 8v4" />
                          <path d="M12 16h.01" />
                        </>
                      ) : (
                        <>
                          <circle cx="12" cy="12" r="10" />
                          <line x1="15" y1="9" x2="9" y2="15" />
                          <line x1="9" y1="9" x2="15" y2="15" />
                        </>
                      )}
                    </svg>
                  </div>
                  <p className="ac-qr-status__title">{STATUS_LABELS[pass.status]}</p>
                  <p className="ac-qr-status__sub">{STATUS_COPY[pass.status]}</p>
                </div>

                <div style={{ marginTop: "20px" }}>
                  <Link href="/" className="button button--full">
                    Retour à l'accueil
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
