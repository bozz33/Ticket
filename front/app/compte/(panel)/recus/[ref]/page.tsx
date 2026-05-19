import { notFound } from "next/navigation";

import { ReceiptDetailView } from "@/components/account/receipt-detail/ReceiptDetailView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function RecuDetailPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const receipt = await getAccountReceipt(tenantSlug, token, ref);
  if (!receipt) notFound();

  return <ReceiptDetailView receipt={receipt} />;
}
