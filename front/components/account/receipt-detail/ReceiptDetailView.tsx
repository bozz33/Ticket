import Link from "next/link";

import type { AccountReceipt } from "@/lib/types";

import {
  RECEIPT_STATUS_LABELS,
  formatReceiptAmount,
  formatReceiptDate,
  receiptGatewayReference,
  receiptPaymentReference,
} from "./helpers";

type ReceiptDetailViewProps = {
  receipt: AccountReceipt;
};

export function ReceiptDetailView({ receipt }: ReceiptDetailViewProps) {
  return (
    <>
      <Link href="/compte/recus" className="ac-back">
        <svg
          fill="none"
          height="16"
          stroke="currentColor"
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="2"
          viewBox="0 0 24 24"
          width="16"
        >
          <path d="m15 18-6-6 6-6" />
        </svg>
        Retour aux reçus
      </Link>

      <div className="ac-page-header">
        <h1 className="ac-page-title">Reçu {receipt.reference}</h1>
        <p className="ac-page-sub">Justificatif centralisé, prêt à afficher ou à imprimer.</p>
      </div>

      <section className="receipt-panel receipt-panel--account">
        <div className="receipt-panel__icon" aria-hidden="true">
          <svg
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="1.8"
            viewBox="0 0 24 24"
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="15" y2="17" />
          </svg>
        </div>

        <div className="receipt-panel__status-wrap">
          <span className={`ac-badge ac-badge--${receipt.status}`}>
            <span className="ac-badge__dot" />
            {RECEIPT_STATUS_LABELS[receipt.status]}
          </span>
        </div>

        <strong className="receipt-panel__amount">
          {formatReceiptAmount(receipt.total_amount, receipt.currency_code)}
        </strong>

        <p className="receipt-panel__caption">Reçu émis pour votre achat et prêt à être partagé ou imprimé.</p>

        <div className="receipt-panel__meta">
          <div>
            <span>N° reçu</span>
            <strong>{receipt.receipt_number ?? receipt.reference}</strong>
          </div>
          <div>
            <span>Référence</span>
            <strong>{receipt.reference}</strong>
          </div>
          <div>
            <span>Date d&apos;émission</span>
            <strong>{formatReceiptDate(receipt.issued_at)}</strong>
          </div>
          <div>
            <span>Nom du destinataire</span>
            <strong>{receipt.buyer_name ?? "—"}</strong>
          </div>
          <div>
            <span>E-mail</span>
            <strong>{receipt.buyer_email ?? "—"}</strong>
          </div>
          <div>
            <span>Téléphone</span>
            <strong>{receipt.order?.buyer_phone ?? receipt.buyer_phone ?? "—"}</strong>
          </div>
          <div>
            <span>Référence paiement</span>
            <strong>{receiptPaymentReference(receipt)}</strong>
          </div>
          <div>
            <span>N° Référence</span>
            <strong>{receiptGatewayReference(receipt)}</strong>
          </div>
          <div>
            <span>Créé le</span>
            <strong>{formatReceiptDate(receipt.created_at)}</strong>
          </div>
          <div>
            <span>Commande liée</span>
            <strong>{receipt.order?.reference ?? "—"}</strong>
          </div>
        </div>

        <div className="receipt-panel__actions">
          <Link
            className="button button--ghost"
            href={`/compte/recus/${receipt.reference}/imprimer`}
            rel="noreferrer"
            target="_blank"
          >
            Imprimer ou télécharger le reçu
          </Link>
          {receipt.order ? (
            <Link className="button" href={`/compte/commandes/${receipt.order.reference}`}>
              Voir la commande
            </Link>
          ) : null}
        </div>
      </section>
    </>
  );
}
