import { notFound } from "next/navigation";

import { CheckoutClient } from "@/features/checkout";
import type {
  AccountUser,
  CheckoutPaymentOptions,
  PublicContent,
} from "@/lib/types";

export { PaymentSuccessView } from "./checkout/PaymentSuccessView";
export { ReceiptView } from "./checkout/ReceiptView";

export function CheckoutView({
  item,
  selectedOffer,
  dateLabel,
  paymentOptions,
  accountUser,
  loginUrl,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  dateLabel: string;
  paymentOptions: CheckoutPaymentOptions | null;
  accountUser: AccountUser | null;
  loginUrl: string;
}) {
  if (!item) {
    notFound();
  }

  return (
    <CheckoutClient
      accountUser={accountUser}
      dateLabel={dateLabel}
      initialPaymentOptions={paymentOptions}
      item={item}
      loginUrl={loginUrl}
      selectedOffer={selectedOffer}
    />
  );
}
