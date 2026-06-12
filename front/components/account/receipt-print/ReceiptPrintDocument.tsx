import type { AccountReceipt, PlatformConfiguration } from "@/lib/types";
import { QrCode } from "@/components/QrCode";
import { formatMoney } from "@/lib/utils";

import { buildReceiptPrintModel } from "./model";
import { ReceiptPrintToolbar } from "./ReceiptPrintToolbar";
import { formatLongReceiptDate } from "./helpers";

type ReceiptPrintDocumentProps = {
  platform: PlatformConfiguration;
  receipt: AccountReceipt;
  tenantSlug: string;
};

export function ReceiptPrintDocument({ platform, receipt, tenantSlug }: ReceiptPrintDocumentProps) {
  const model = buildReceiptPrintModel(receipt, tenantSlug);
  const issuedAt = formatLongReceiptDate(receipt.issued_at ?? receipt.created_at);
  const buyerName = receipt.buyer_name ?? "Acheteur Ticket";
  const buyerEmail = receipt.buyer_email ?? "—";
  const buyerPhone = model.buyerPhone;
  const lineAmount = model.unitAmount * model.quantity;
  const serviceFee = Math.max(0, receipt.total_amount - lineAmount);
  const serviceFeeLabel = serviceFee > 0 ? formatMoney(serviceFee, receipt.currency_code) : "—";

  return (
    <div className="receipt-print-shell">
      <ReceiptPrintToolbar
        downloadPayload={{
          brandName: platform.brandName || "Ticket",
          brandTagline: "Public marketplace",
          buyerEmail,
          buyerName,
          buyerPhone,
          issuedAt,
          lineAmount: formatMoney(lineAmount, receipt.currency_code),
          offerName: model.offerName,
          orderReference: model.orderReference,
          paymentMethod: model.paymentMethod,
          paymentReference: model.paymentReference,
          providerReference: model.providerReference,
          quantity: model.quantity,
          receiptReference: receipt.reference,
          serviceDescription: model.serviceDescription,
          serviceFee: serviceFeeLabel,
          total: formatMoney(receipt.total_amount, receipt.currency_code),
          verificationUrl: model.verificationUrl,
        }}
      />

      <article className="receipt-document receipt-invoice">
        <header className="receipt-invoice__header">
          <div className="receipt-invoice__brand">
            {platform.logoUrl ? (
              <img alt={platform.brandName} className="receipt-invoice__logo" src={platform.logoUrl} />
            ) : (
              <span className="receipt-invoice__mark">T</span>
            )}
            <div>
              <strong>{platform.brandName || "Ticket"}</strong>
              <span>Public marketplace</span>
            </div>
          </div>

          <div className="receipt-invoice__issuer">
            <strong>{platform.brandName || "Ticket"} Services</strong>
            <span>Plateforme de billetterie, réservations et paiements</span>
            <span>Support : support@ticket.africa · +225 27 22 40 11 00</span>
          </div>
        </header>

        <div className="receipt-invoice__number">
          <strong>REÇU N° {model.receiptNumber}</strong>
          <span>Référence : {receipt.reference}</span>
        </div>

        <section className="receipt-invoice__parties">
          <div className="receipt-invoice__box">
            <h2>Détails du paiement :</h2>
            <p>Payé le : <strong>{issuedAt}</strong></p>
            <p>Mode de règlement : <strong>{model.paymentMethod}</strong></p>
            <p>Référence paiement : <strong>{model.paymentReference}</strong></p>
            <p>N° Référence : <strong>{model.providerReference}</strong></p>
          </div>

          <div className="receipt-invoice__box">
            <h2>Client facturé :</h2>
            <p><strong>{buyerName}</strong></p>
            <p>{buyerEmail}</p>
            <p>{buyerPhone}</p>
            <p>Commande : <strong>{model.orderReference}</strong></p>
          </div>
        </section>

        <section className="receipt-invoice__table">
          <div className="receipt-invoice__table-head">
            <span>Désignation</span>
            <span>Qté</span>
            <span>Prix</span>
          </div>

          <div className="receipt-invoice__table-row">
            <div>
              <strong>{model.offerName}</strong>
              <span>{model.serviceDescription}</span>
            </div>
            <span>{model.quantity}</span>
            <span>{formatMoney(lineAmount, receipt.currency_code)}</span>
          </div>
        </section>

        <section className="receipt-invoice__settlement">
          <div className="receipt-invoice__qr-box">
            <QrCode value={model.verificationUrl} size={108} />
            <div>
              <strong>QR de vérification</strong>
              <span>Scannez pour confirmer l’authenticité du reçu.</span>
            </div>
          </div>

          <div className="receipt-invoice__totals">
            <div>
              <span>Montant HT :</span>
              <strong>{formatMoney(lineAmount, receipt.currency_code)}</strong>
            </div>
            <div>
              <span>Frais de service :</span>
              <strong>{serviceFeeLabel}</strong>
            </div>
            <div>
              <span>NET À PAYER TTC :</span>
              <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
            </div>
          </div>
        </section>

        <section className="receipt-invoice__conditions">
          <h2>CONDITIONS DE RÈGLEMENT</h2>
          <p>Ce reçu confirme le paiement enregistré pour la commande indiquée et doit être conservé comme justificatif de règlement.</p>
          <p>Le QR code permet de vérifier publiquement le statut du reçu et d’éviter toute reproduction frauduleuse.</p>
        </section>

        <footer className="receipt-invoice__footer">
          <span>Document généré par {platform.brandName || "Ticket"}</span>
          <span>{model.verificationUrl}</span>
        </footer>
      </article>
    </div>
  );
}
