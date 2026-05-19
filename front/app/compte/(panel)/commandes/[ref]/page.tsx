import { notFound } from "next/navigation";

import { OrderDetailView } from "@/components/account/order-detail/OrderDetailView";
import type { RefundRequestSummary } from "@/components/account/order-detail/helpers";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrder } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function CommandeDetailPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const order = await getAccountOrder(tenantSlug, token, ref);
  if (!order) notFound();

  const refundRequest = (order.meta?.refund_request ?? null) as RefundRequestSummary | null;
  const canRequestRefund = order.status === "confirmed" && !refundRequest;

  return (
    <OrderDetailView
      canRequestRefund={canRequestRefund}
      order={order}
      refundRequest={refundRequest}
    />
  );
}
