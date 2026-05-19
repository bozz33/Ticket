const TENANT_SLUG_PATTERN = /^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/;

export function normalizeTenantSlug(value: string | null | undefined): string {
  const slug = value?.trim().toLowerCase() ?? "";

  return TENANT_SLUG_PATTERN.test(slug) ? slug : "";
}

export function isValidTenantSlug(value: string | null | undefined): boolean {
  return normalizeTenantSlug(value).length > 0;
}

export function requireValidTenantSlug(value: string | null | undefined): string {
  const slug = normalizeTenantSlug(value);

  if (!slug) {
    throw new Error("Tenant invalide.");
  }

  return slug;
}
