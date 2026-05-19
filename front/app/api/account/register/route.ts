import { type NextRequest, NextResponse } from "next/server";

import { getAccountTenantSlug, setAuthCookies } from "@/lib/auth";
import { registerAccount } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { readJsonRecord, stringField } from "@/lib/server/request";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-register", 10);

  if (rateLimitError) {
    return rateLimitError;
  }

  const parsed = await readJsonRecord(request);

  if ("response" in parsed) {
    return parsed.response;
  }

  const name = stringField(parsed.data, "name");
  const email = stringField(parsed.data, "email");
  const password = stringField(parsed.data, "password", false);
  const tenant = stringField(parsed.data, "tenant");

  if (!name || !email || !password) {
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

  const result = await registerAccount(tenantSlug, name, email, password);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  await setAuthCookies(result.token, tenantSlug);

  return NextResponse.json({ user: result.user }, { status: 201 });
}
