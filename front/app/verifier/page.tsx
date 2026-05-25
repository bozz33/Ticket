import { VerificationLookupPage } from "@/components/public-verification/VerificationLookupPage";

export const dynamic = "force-dynamic";

export default async function VerificationPage({
  searchParams,
}: {
  searchParams: Promise<{ ref?: string; reference?: string; tenant?: string }>;
}) {
  const params = await searchParams;

  return (
    <VerificationLookupPage
      initialRef={(params.ref ?? params.reference ?? "").trim()}
      initialTenant={(params.tenant ?? "").trim()}
    />
  );
}
