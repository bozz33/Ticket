import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { createAccountRefundRequest } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { readJsonRecord, stringField } from "@/lib/server/request";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-refund-request", 8);

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await requireTenantSlug();
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Tenant non configuré." }, { status: 500 });
  }

  const parsed = await readJsonRecord(request);

  if ("response" in parsed) {
    return parsed.response;
  }

  const payload = {
    order_reference: stringField(parsed.data, "order_reference"),
    reason_code: stringField(parsed.data, "reason_code"),
    reason: stringField(parsed.data, "reason"),
  };

  if (!payload.order_reference || !payload.reason_code) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  const result = await createAccountRefundRequest(tenantSlug, token, payload);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json(result, { status: 201 });
}
