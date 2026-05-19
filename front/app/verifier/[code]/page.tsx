import { MissingPassVerificationView, MissingTenantVerificationView, PassVerificationView } from "@/components/account/pass-verification/PassVerificationView";
import { getTenantSlug } from "@/lib/auth";
import { getPublicPass } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function VerifyPassPage({
  params,
}: {
  params: Promise<{ code: string }>;
}) {
  const { code } = await params;
  const tenantSlug = await getTenantSlug();

  if (!tenantSlug) {
    return <MissingTenantVerificationView />;
  }

  const pass = await getPublicPass(tenantSlug, code);

  if (!pass) {
    return <MissingPassVerificationView />;
  }

  return <PassVerificationView pass={pass} />;
}
