import { readFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const openApiPath = path.resolve(root, "..", "backend", "docs", "api", "openapi.json");
const requiredPaths = [
  "/api/v1/public/platform/configuration",
  "/api/v1/public/front/pages",
  "/api/v1/public/content",
  "/api/v1/public/content/{module}/{slug}",
  "/api/v1/public/content/search/suggestions",
  "/api/v1/public/tenants/{tenant}/payment-options",
  "/api/v1/public/tenants/{tenant}/payments/initialize",
  "/api/v1/public/tenants/{tenant}/payments/verify/{reference}",
  "/api/v1/public/onboarding/register",
  "/api/v1/tenants/{tenant}/auth/login",
  "/api/v1/tenants/{tenant}/refund-requests",
];

const spec = JSON.parse(readFileSync(openApiPath, "utf8"));
const paths = spec.paths ?? {};
const missing = requiredPaths.filter((apiPath) => !paths[apiPath]);

if (missing.length > 0) {
  process.stderr.write(`OpenAPI contract is missing ${missing.length} path(s):\n`);
  for (const apiPath of missing) {
    process.stderr.write(`- ${apiPath}\n`);
  }
  process.exit(1);
}

process.stdout.write(`OpenAPI contract check passed (${requiredPaths.length} front-critical paths).\n`);
