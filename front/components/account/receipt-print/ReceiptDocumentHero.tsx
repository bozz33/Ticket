import type { AccountReceipt } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import { formatReceiptIssueDate } from "./helpers";

type ReceiptDocumentHeroProps = {
  receipt: AccountReceipt;
};

export function ReceiptDocumentHero({ receipt }: ReceiptDocumentHeroProps) {
  return (
    <>
      <div className="receipt-document__topline">
        <div>
          <p className="receipt-document__eyebrow">Reçu de paiement</p>
          <p className="receipt-document__origin">Ticket Public Marketplace</p>
        </div>
        <span className="receipt-document__status-pill">Payé</span>
      </div>

      <div className="receipt-document__hero">
        <div>
          <h1>Payée le {formatReceiptIssueDate(receipt.issued_at ?? receipt.created_at)}</h1>
          <p className="receipt-document__lede">
            Reçu émis pour votre achat. Ce document peut être imprimé, partagé ou vérifié à partir du QR code.
          </p>
        </div>

        <div className="receipt-document__hero-amount">
          <span>Montant payé</span>
          <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
        </div>
      </div>
    </>
  );
}
