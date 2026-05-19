import { notFound } from "next/navigation";

import { ReceiptPrintDocument } from "@/components/account/receipt-print/ReceiptPrintDocument";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function ReceiptPrintPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const receipt = await getAccountReceipt(tenantSlug, token, ref);

  if (!receipt) notFound();

  return <ReceiptPrintDocument receipt={receipt} tenantSlug={tenantSlug} />;
}
