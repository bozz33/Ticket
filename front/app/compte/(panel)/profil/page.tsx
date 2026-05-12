import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountMe } from "@/lib/data/account";

import { ProfileEditor } from "./ProfileEditor";

export const dynamic = "force-dynamic";

export default async function ProfilPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const user = token ? await getAccountMe(tenantSlug, token) : null;

  return <ProfileEditor initialUser={user} />;
}
