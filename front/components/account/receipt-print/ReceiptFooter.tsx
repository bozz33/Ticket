import type { AccountReceipt } from "@/lib/types";

import { formatLongReceiptDate } from "./helpers";

type ReceiptFooterProps = {
  receipt: AccountReceipt;
};

export function ReceiptFooter({ receipt }: ReceiptFooterProps) {
  return (
    <div className="receipt-document__footer">
      <div>
        <span>Support</span>
        <strong>support@ticket.africa</strong>
      </div>
      <div>
        <span>Contact</span>
        <strong>+225 27 22 40 11 00</strong>
      </div>
      <div>
        <span>Émis le</span>
        <strong>{formatLongReceiptDate(receipt.issued_at ?? receipt.created_at)}</strong>
      </div>
    </div>
  );
}
