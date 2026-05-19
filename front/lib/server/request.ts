import { type NextRequest, NextResponse } from "next/server";

export type JsonRecord = Record<string, unknown>;

export async function readJsonRecord(
  request: NextRequest,
  message = "Payload invalide.",
): Promise<{ data: JsonRecord } | { response: NextResponse }> {
  const data = await request.json().catch(() => null);

  if (!data || typeof data !== "object" || Array.isArray(data)) {
    return { response: NextResponse.json({ error: message }, { status: 400 }) };
  }

  return { data: data as JsonRecord };
}

export function stringField(data: JsonRecord, key: string, trim = true): string {
  const value = data[key];

  if (typeof value !== "string") {
    return "";
  }

  return trim ? value.trim() : value;
}
