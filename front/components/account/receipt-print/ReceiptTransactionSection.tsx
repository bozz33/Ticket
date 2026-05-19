import type { AccountReceipt } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import { formatLongReceiptDate } from "./helpers";
import type { ReceiptPrintModel } from "./model";

type ReceiptTransactionSectionProps = {
  model: ReceiptPrintModel;
  receipt: AccountReceipt;
};

export function ReceiptTransactionSection({ model, receipt }: ReceiptTransactionSectionProps) {
  return (
    <section className="receipt-document__section">
      <div className="receipt-document__section-title">
        <span>Informations sur la transaction</span>
      </div>

      <div className="receipt-document__grid">
        <div>
          <span>Montant</span>
          <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
        </div>
        <div>
          <span>ID Transaction</span>
          <strong>{model.gatewayTransactionId}</strong>
        </div>
        <div>
          <span>N° Référence</span>
          <strong>{model.providerReference}</strong>
        </div>
        <div>
          <span>Référence paiement</span>
          <strong>{model.paymentReference}</strong>
        </div>
        <div>
          <span>Moyen de paiement</span>
          <strong>{model.paymentMethod}</strong>
        </div>
        <div>
          <span>Commande liée</span>
          <strong>{model.orderReference}</strong>
        </div>
        <div>
          <span>Statut</span>
          <strong>Payé</strong>
        </div>
        <div>
          <span>Généré le</span>
          <strong>{formatLongReceiptDate(receipt.issued_at ?? receipt.created_at)}</strong>
        </div>
      </div>
    </section>
  );
}
