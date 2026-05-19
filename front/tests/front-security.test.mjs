import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { describe, it } from "node:test";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

function read(relativePath) {
  return readFileSync(path.join(root, relativePath), "utf8");
}

describe("front security guardrails", () => {
  it("does not let the service worker cache account or API responses", () => {
    const source = read("public/sw.js");

    assert.match(source, /PRIVATE_PATH_PREFIXES/);
    assert.match(source, /"\/api\/"/);
    assert.match(source, /"\/compte"/);
    assert.match(source, /isPrivatePath\(url\.pathname\)/);
    assert.doesNotMatch(source, /cache\.put\(request,\s*clone\)[\s\S]*request\.mode === "navigate"/);
  });

  it("keeps CSP scoped instead of allowing every connection or frame", () => {
    const source = read("next.config.ts");

    assert.doesNotMatch(source, /connect-src 'self' http: https:/);
    assert.doesNotMatch(source, /frame-src 'self' https:\/\/checkout\.paystack\.com https:/);
    assert.match(source, /isProduction/);
  });

  it("keeps checkout browser code behind internal API facades", () => {
    const source = read("components/CheckoutClient.tsx");

    assert.doesNotMatch(source, /@\/lib\/data\/public/);
    assert.match(read("lib/client/checkout.ts"), /\/api\/checkout\/initialize/);
    assert.match(read("app/api/checkout/payment-options/route.ts"), /getCheckoutPaymentOptions/);
  });
});
