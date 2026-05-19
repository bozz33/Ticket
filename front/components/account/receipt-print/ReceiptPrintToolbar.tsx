import Link from "next/link";

import { PrintButton } from "@/components/PrintButton";

type ReceiptPrintToolbarProps = {
  receiptReference: string;
  verificationUrl: string;
};

export function ReceiptPrintToolbar({ receiptReference, verificationUrl }: ReceiptPrintToolbarProps) {
  return (
    <div className="receipt-print-toolbar">
      <Link href={`/compte/recus/${receiptReference}`} className="ac-back">
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
        Fermer les détails du reçu
      </Link>

      <div className="receipt-print-toolbar__actions">
        <PrintButton className="button button--ghost">Imprimer</PrintButton>
        <a className="button" href={verificationUrl} target="_blank" rel="noreferrer">
          Vérifier le reçu
        </a>
      </div>
    </div>
  );
}
