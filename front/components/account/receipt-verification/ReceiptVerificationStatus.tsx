import Link from "next/link";

import type { PublicReceiptVerification } from "@/lib/types";

type ReceiptVerificationStatusProps = {
  receipt: PublicReceiptVerification;
};

export function ReceiptVerificationStatus({ receipt }: ReceiptVerificationStatusProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">État de vérification</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-qr-status">
          <div className="ac-qr-status__icon ac-qr-status__icon--ok">
            <svg
              fill="none"
              height="28"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              viewBox="0 0 24 24"
              width="28"
            >
              <path d="M20 7 9.5 17.5 4 12" />
            </svg>
          </div>
          <p className="ac-qr-status__title">Justificatif valide</p>
          <p className="ac-qr-status__sub">
            Le reçu est bien enregistré dans l&apos;espace public du tenant, avec {receipt.access_passes_count} pass
            lié(s).
          </p>
        </div>
        <div style={{ marginTop: "20px" }}>
          <Link href="/" className="button button--full">
            Retour à l&apos;accueil
          </Link>
        </div>
      </div>
    </div>
  );
}
