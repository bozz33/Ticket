import Link from "next/link";

import { PrintButton } from "@/components/PrintButton";
import { ReceiptDownloadButton, type ReceiptDownloadPayload } from "./ReceiptDownloadButton";

type ReceiptPrintToolbarProps = {
  downloadPayload: ReceiptDownloadPayload;
};

export function ReceiptPrintToolbar({ downloadPayload }: ReceiptPrintToolbarProps) {
  return (
    <div className="receipt-print-toolbar">
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

      <div className="receipt-print-toolbar__actions">
        <PrintButton className="button button--ghost">Imprimer</PrintButton>
        <ReceiptDownloadButton payload={downloadPayload} />
      </div>
    </div>
  );
}
