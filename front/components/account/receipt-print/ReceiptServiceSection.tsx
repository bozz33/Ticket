import type { AccountReceipt } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import type { ReceiptPrintModel } from "./model";

type ReceiptServiceSectionProps = {
  model: ReceiptPrintModel;
  receipt: AccountReceipt;
};

export function ReceiptServiceSection({ model, receipt }: ReceiptServiceSectionProps) {
  return (
    <section className="receipt-document__section">
      <div className="receipt-document__section-title">
        <span>Informations sur le service</span>
      </div>

      <div className="receipt-document__grid">
        <div>
          <span>Nom du service</span>
          <strong>{model.offerName}</strong>
        </div>
        <div>
          <span>Description</span>
          <strong>{model.serviceDescription}</strong>
        </div>
        <div>
          <span>Acheteur</span>
          <strong>{receipt.buyer_name ?? "Acheteur Ticket"}</strong>
        </div>
        <div>
          <span>E-mail</span>
          <strong>{receipt.buyer_email ?? "—"}</strong>
        </div>
        <div>
          <span>Téléphone</span>
          <strong>{model.buyerPhone}</strong>
        </div>
        <div>
          <span>Quantité</span>
          <strong>{model.quantity}</strong>
        </div>
        <div>
          <span>Prix unitaire</span>
          <strong>{formatMoney(model.unitAmount, receipt.currency_code)}</strong>
        </div>
        <div>
          <span>Devise</span>
          <strong>{receipt.currency_code}</strong>
        </div>
      </div>
    </section>
  );
}
