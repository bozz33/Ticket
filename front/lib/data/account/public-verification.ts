import type { PublicPassVerification, PublicReceiptVerification } from "@/lib/types";
import { apiBase } from "./client";

export async function getPublicPass(
  tenantSlug: string,
  code: string,
): Promise<PublicPassVerification | null> {
  if (!apiBase) return null;
  try {
    const res = await fetch(
      `${apiBase}/api/v1/public/tenants/${tenantSlug}/access-passes/${code}`,
      { cache: "no-store", headers: { Accept: "application/json" } },
    );
    if (!res.ok) return null;
    const body = await res.json();
    return (body?.data as PublicPassVerification) ?? null;
  } catch {
    return null;
  }
}

export async function getPublicReceiptVerification(
  tenantSlug: string,
  reference: string,
): Promise<PublicReceiptVerification | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(
      `${apiBase}/api/v1/public/tenants/${tenantSlug}/receipts/${encodeURIComponent(reference)}/verify`,
      { cache: "no-store", headers: { Accept: "application/json" } },
    );
    if (!res.ok) return null;
    const body = await res.json();
    return (body?.data as PublicReceiptVerification) ?? null;
  } catch {
    return null;
  }
}

