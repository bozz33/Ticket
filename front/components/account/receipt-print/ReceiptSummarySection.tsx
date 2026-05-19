import type { AccountReceipt } from "@/lib/types";

type ReceiptSummarySectionProps = {
  receipt: AccountReceipt;
};

export function ReceiptSummarySection({ receipt }: ReceiptSummarySectionProps) {
  return (
    <section className="receipt-document__section">
      <div className="receipt-document__section-title">
        <span>Récapitulatif</span>
      </div>

      <div className="receipt-document__summary receipt-document__summary--compact">
        <div>
          <span>À</span>
          <strong>{receipt.buyer_name ?? "Acheteur Ticket"}</strong>
        </div>
        <div>
          <span>De</span>
          <strong>Ticket Public Marketplace</strong>
        </div>
        <div>
          <span>Facture</span>
          <strong>{receipt.reference}</strong>
        </div>
      </div>
    </section>
  );
}
