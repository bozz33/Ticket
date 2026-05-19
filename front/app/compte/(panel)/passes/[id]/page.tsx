import { notFound } from "next/navigation";

import { PassDetailView } from "@/components/account/pass-detail/PassDetailView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountPass } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function PassDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const pass = await getAccountPass(tenantSlug, token, id);
  if (!pass) notFound();

  return <PassDetailView pass={pass} />;
}
