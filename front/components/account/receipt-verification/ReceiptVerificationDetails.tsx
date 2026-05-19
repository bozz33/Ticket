import type { PublicReceiptVerification } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import { formatVerificationDate } from "./helpers";

type ReceiptVerificationDetailsProps = {
  receipt: PublicReceiptVerification;
};

export function ReceiptVerificationDetails({ receipt }: ReceiptVerificationDetailsProps) {
  return (
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
          <span className="ac-detail-row__value">{formatVerificationDate(receipt.issued_at)}</span>
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
          <span className="ac-detail-row__label">Référence de suivi</span>
          <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.reference}</span>
        </div>
      </div>
    </div>
  );
}
