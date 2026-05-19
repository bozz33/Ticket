import { AccountHomeView } from "@/components/account/home/AccountHomeView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrders, getAccountPasses } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function AccountHomePage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  const [orders, passes] = token
    ? await Promise.all([
        getAccountOrders(tenantSlug, token),
        getAccountPasses(tenantSlug, token),
      ])
    : [[], []];

  return <AccountHomeView orders={orders} passes={passes} />;
}
