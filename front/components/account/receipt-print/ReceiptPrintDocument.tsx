import type { AccountReceipt } from "@/lib/types";

import { buildReceiptPrintModel } from "./model";
import { ReceiptDocumentHero } from "./ReceiptDocumentHero";
import { ReceiptFooter } from "./ReceiptFooter";
import { ReceiptItemsSection } from "./ReceiptItemsSection";
import { ReceiptPrintToolbar } from "./ReceiptPrintToolbar";
import { ReceiptServiceSection } from "./ReceiptServiceSection";
import { ReceiptSummarySection } from "./ReceiptSummarySection";
import { ReceiptTotalsAndQrSection } from "./ReceiptTotalsAndQrSection";
import { ReceiptTransactionSection } from "./ReceiptTransactionSection";

type ReceiptPrintDocumentProps = {
  receipt: AccountReceipt;
  tenantSlug: string;
};

export function ReceiptPrintDocument({ receipt, tenantSlug }: ReceiptPrintDocumentProps) {
  const model = buildReceiptPrintModel(receipt, tenantSlug);

  return (
    <div className="receipt-print-shell">
      <ReceiptPrintToolbar receiptReference={receipt.reference} verificationUrl={model.verificationUrl} />

      <article className="receipt-document">
        <ReceiptDocumentHero receipt={receipt} />
        <ReceiptSummarySection receipt={receipt} />
        <ReceiptTransactionSection model={model} receipt={receipt} />
        <ReceiptServiceSection model={model} receipt={receipt} />
        <ReceiptItemsSection model={model} receipt={receipt} />
        <ReceiptTotalsAndQrSection receipt={receipt} verificationUrl={model.verificationUrl} />
        <ReceiptFooter receipt={receipt} />
      </article>
    </div>
  );
}
