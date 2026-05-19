import { type NextRequest, NextResponse } from "next/server";

import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { readJsonRecord, stringField } from "@/lib/server/request";

const OBSERVABILITY_ENDPOINT = process.env.OBSERVABILITY_ENDPOINT?.trim() ?? "";
const OBSERVABILITY_TOKEN = process.env.OBSERVABILITY_TOKEN?.trim() ?? "";
const allowedTypes = new Set(["web-vital", "client-error", "unhandled-rejection"]);

function numberField(value: unknown): number | undefined {
  const numberValue = typeof value === "number" ? value : Number(value);

  return Number.isFinite(numberValue) ? numberValue : undefined;
}

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "client-observability", 120);

  if (rateLimitError) {
    return rateLimitError;
  }

  const parsed = await readJsonRecord(request);

  if ("response" in parsed) {
    return parsed.response;
  }

  const type = stringField(parsed.data, "type");

  if (!allowedTypes.has(type)) {
    return NextResponse.json({ error: "Type d'événement invalide." }, { status: 422 });
  }

  const event = {
    type,
    name: stringField(parsed.data, "name").slice(0, 120),
    message: stringField(parsed.data, "message").slice(0, 500),
    path: stringField(parsed.data, "path").slice(0, 300),
    rating: stringField(parsed.data, "rating").slice(0, 40),
    value: numberField(parsed.data.value),
    timestamp: new Date().toISOString(),
    userAgent: request.headers.get("user-agent")?.slice(0, 300) ?? "",
  };

  if (OBSERVABILITY_ENDPOINT) {
    await fetch(OBSERVABILITY_ENDPOINT, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        ...(OBSERVABILITY_TOKEN ? { Authorization: `Bearer ${OBSERVABILITY_TOKEN}` } : {}),
      },
      body: JSON.stringify(event),
      cache: "no-store",
    }).catch(() => null);
  }

  return NextResponse.json({ accepted: true }, { status: 202 });
}
