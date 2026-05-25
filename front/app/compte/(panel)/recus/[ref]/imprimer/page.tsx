import { notFound } from "next/navigation";

import { ReceiptPrintDocument } from "@/components/account/receipt-print/ReceiptPrintDocument";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";
import { getPlatformConfiguration } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function ReceiptPrintPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const [receipt, platform] = await Promise.all([
    getAccountReceipt(tenantSlug, token, ref),
    getPlatformConfiguration(),
  ]);

  if (!receipt) notFound();

  return <ReceiptPrintDocument platform={platform} receipt={receipt} tenantSlug={tenantSlug} />;
}
