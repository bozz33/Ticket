import type { AccountReceipt } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import type { ReceiptPrintModel } from "./model";

type ReceiptItemsSectionProps = {
  model: ReceiptPrintModel;
  receipt: AccountReceipt;
};

export function ReceiptItemsSection({ model, receipt }: ReceiptItemsSectionProps) {
  return (
    <section className="receipt-document__section">
      <div className="receipt-document__section-title">
        <span>Articles</span>
      </div>

      <div className="receipt-document__table">
        <div className="receipt-document__table-head">
          <span>Article</span>
          <span>Qté</span>
          <span>Prix unit.</span>
          <span>Montant</span>
        </div>

        <div className="receipt-document__table-row">
          <strong>{model.offerName}</strong>
          <span>{model.quantity}</span>
          <span>{formatMoney(model.unitAmount, receipt.currency_code)}</span>
          <span>{formatMoney(receipt.total_amount, receipt.currency_code)}</span>
        </div>
      </div>
    </section>
  );
}
