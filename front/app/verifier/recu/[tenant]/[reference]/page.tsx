import { ReceiptNotFoundView } from "@/components/account/receipt-verification/ReceiptNotFoundView";
import { ReceiptVerificationView } from "@/components/account/receipt-verification/ReceiptVerificationView";
import { getPublicReceiptVerification } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function VerifyReceiptPage({
  params,
}: {
  params: Promise<{ tenant: string; reference: string }>;
}) {
  const { tenant, reference } = await params;
  const receipt = await getPublicReceiptVerification(tenant, reference);

  if (!receipt) {
    return <ReceiptNotFoundView />;
  }

  return <ReceiptVerificationView receipt={receipt} />;
}
