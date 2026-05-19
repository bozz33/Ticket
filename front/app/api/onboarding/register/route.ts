import { type NextRequest, NextResponse } from "next/server";

import { registerOrganizer } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { readJsonRecord, stringField } from "@/lib/server/request";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "onboarding-register", 5);

  if (rateLimitError) {
    return rateLimitError;
  }

  const parsed = await readJsonRecord(request);

  if ("response" in parsed) {
    return parsed.response;
  }

  const org_name = stringField(parsed.data, "org_name");
  const email = stringField(parsed.data, "email");
  const password = stringField(parsed.data, "password", false);
  const country_code = stringField(parsed.data, "country_code");
  const currency_code = stringField(parsed.data, "currency_code");

  if (!org_name || !email || !password) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  const result = await registerOrganizer({ org_name, email, password, country_code, currency_code });

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json(result, { status: 201 });
}
