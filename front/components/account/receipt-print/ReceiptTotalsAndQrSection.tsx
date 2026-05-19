import { QrCode } from "@/components/QrCode";
import type { AccountReceipt } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

type ReceiptTotalsAndQrSectionProps = {
  receipt: AccountReceipt;
  verificationUrl: string;
};

export function ReceiptTotalsAndQrSection({ receipt, verificationUrl }: ReceiptTotalsAndQrSectionProps) {
  return (
    <div className="receipt-document__bottom">
      <section className="receipt-document__section">
        <div className="receipt-document__section-title">
          <span>Totaux</span>
        </div>

        <div className="receipt-document__totals">
          <div>
            <span>Montant dû</span>
            <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
          </div>
          <div>
            <span>Montant payé</span>
            <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
          </div>
          <div>
            <span>Montant restant</span>
            <strong>{formatMoney(0, receipt.currency_code)}</strong>
          </div>
        </div>
      </section>

      <aside className="receipt-document__qr-panel">
        <QrCode value={verificationUrl} size={148} />
        <strong>QR de vérification</strong>
        <p>Scannez pour suivre l’état de votre achat et vérifier publiquement l’authenticité du reçu.</p>
        <span className="receipt-document__qr-url">{verificationUrl}</span>
      </aside>
    </div>
  );
}
