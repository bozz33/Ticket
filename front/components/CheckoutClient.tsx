"use client";

import { CheckoutProforma, type CheckoutClientProps } from "@/components/checkout-client/CheckoutProforma";

export function CheckoutClient(props: CheckoutClientProps) {
  return <CheckoutProforma {...props} />;
}
