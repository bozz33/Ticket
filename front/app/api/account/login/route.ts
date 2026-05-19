import { type NextRequest, NextResponse } from "next/server";

import { getAccountTenantSlug, setAuthCookies } from "@/lib/auth";
import { loginAccount } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { readJsonRecord, stringField } from "@/lib/server/request";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-login", 10);

  if (rateLimitError) {
    return rateLimitError;
  }

  const parsed = await readJsonRecord(request);

  if ("response" in parsed) {
    return parsed.response;
  }

  const email = stringField(parsed.data, "email");
  const password = stringField(parsed.data, "password", false);
  const tenant = stringField(parsed.data, "tenant");

  if (!email || !password) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await getAccountTenantSlug(tenant);
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Aucun espace acheteur actif n'est disponible pour le moment." },
      { status: 503 },
    );
  }

  if (!tenantSlug) {
    return NextResponse.json(
      { error: "Aucun espace acheteur actif n'est disponible pour le moment." },
      { status: 503 },
    );
  }

  const result = await loginAccount(tenantSlug, email, password);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 401 });
  }

  await setAuthCookies(result.token, tenantSlug);

  return NextResponse.json({ user: result.user });
}
