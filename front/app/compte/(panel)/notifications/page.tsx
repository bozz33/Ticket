import { notFound } from "next/navigation";

import { NotificationsListView } from "@/components/account/notifications/NotificationsListView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountNotifications } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function NotificationsPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const payload = await getAccountNotifications(tenantSlug, token);

  return (
    <NotificationsListView
      initialNotifications={payload?.notifications ?? []}
      initialUnreadCount={payload?.unreadCount ?? 0}
    />
  );
}
