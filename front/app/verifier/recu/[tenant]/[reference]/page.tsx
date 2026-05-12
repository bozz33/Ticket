import Link from "next/link";

import { getPublicReceiptVerification } from "@/lib/data/account";
import { formatMoney } from "@/lib/utils";

export const dynamic = "force-dynamic";

function formatDate(iso: string | null) {
  if (!iso) return "—";

  return new Intl.DateTimeFormat("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(iso));
}

export default async function VerifyReceiptPage({
  params,
}: {
  params: Promise<{ tenant: string; reference: string }>;
}) {
  const { tenant, reference } = await params;
  const receipt = await getPublicReceiptVerification(tenant, reference);

  if (!receipt) {
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
              <h1 className="ac-verify__title">Reçu introuvable</h1>
              <p className="ac-verify__sub">La référence fournie ne correspond à aucun justificatif actif sur cette plateforme.</p>
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

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Vérification publique</p>
          <h1>Reçu authentifié</h1>
          <p>Ce justificatif existe bien dans la plateforme et ses métadonnées principales sont cohérentes.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell" style={{ maxWidth: "980px" }}>
          <div className="ac-detail">
            <div className="ac-detail-panel">
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Informations du reçu</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Référence</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.reference}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Montant</span>
                  <span className="ac-detail-row__value">{formatMoney(receipt.total_amount, receipt.currency_code)}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Date d&apos;émission</span>
                  <span className="ac-detail-row__value">{formatDate(receipt.issued_at)}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Titulaire</span>
                  <span className="ac-detail-row__value">{receipt.buyer_name ?? "—"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">E-mail</span>
                  <span className="ac-detail-row__value">{receipt.buyer_email_masked ?? "—"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Téléphone</span>
                  <span className="ac-detail-row__value">{receipt.buyer_phone_masked ?? "—"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Commande</span>
                  <span className="ac-detail-row__value">{receipt.order_reference ?? "—"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Référence paiement</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.transaction_reference ?? "—"}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">N° Référence</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">
                    {receipt.gateway_transaction_id != null
                      ? String(receipt.gateway_transaction_id)
                      : receipt.gateway_reference ?? "—"}
                  </span>
                </div>
              </div>
            </div>

            <div className="ac-detail-panel">
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">État de vérification</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-qr-status">
                  <div className="ac-qr-status__icon ac-qr-status__icon--ok">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M20 7 9.5 17.5 4 12" />
                    </svg>
                  </div>
                  <p className="ac-qr-status__title">Justificatif valide</p>
                  <p className="ac-qr-status__sub">
                    Le reçu est bien enregistré dans l&apos;espace public du tenant, avec {receipt.access_passes_count} pass lié(s).
                  </p>
                </div>
                <div style={{ marginTop: "20px" }}>
                  <Link href="/" className="button button--full">
                    Retour à l&apos;accueil
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
