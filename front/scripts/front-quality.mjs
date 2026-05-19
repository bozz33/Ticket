import { execFileSync } from "node:child_process";
import { existsSync, readFileSync, readdirSync, statSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const failures = [];

function read(relativePath) {
  return readFileSync(path.join(root, relativePath), "utf8");
}

function walk(directory, files = []) {
  for (const entry of readdirSync(directory)) {
    if (
      ["node_modules", ".runtime", ".git", ".chrome-shot", ".edge-desktop", ".edge-mobile", ".screenshots"].includes(entry) ||
      entry.startsWith(".next") ||
      entry.startsWith(".tmp-")
    ) {
      continue;
    }

    const fullPath = path.join(directory, entry);
    const stats = statSync(fullPath);

    if (stats.isDirectory()) {
      walk(fullPath, files);
    } else if (/\.(ts|tsx|js|mjs)$/.test(entry)) {
      files.push(fullPath);
    }
  }

  return files;
}

function check(condition, message) {
  if (!condition) {
    failures.push(message);
  }
}

const packageJson = JSON.parse(read("package.json"));
const allDependencies = {
  ...packageJson.dependencies,
  ...packageJson.devDependencies,
};

for (const [name, version] of Object.entries(allDependencies)) {
  check(version !== "latest", `Dependency ${name} must not use "latest".`);
  check(!/^[\^~]/.test(version), `Dependency ${name} must be pinned exactly.`);
}

for (const scriptName of [
  "lint",
  "a11y",
  "perf:budget",
  "contract:api",
  "typecheck",
  "test",
  "smoke:http",
  "build:ci",
  "build:restricted",
  "e2e:smoke",
  "quality",
]) {
  check(Boolean(packageJson.scripts?.[scriptName]), `Missing package script "${scriptName}".`);
}

const sourceFiles = walk(root);

for (const file of sourceFiles) {
  const relativePath = path.relative(root, file).replaceAll("\\", "/");
  const source = readFileSync(file, "utf8");

  if (source.startsWith('"use client"') || source.startsWith("'use client'")) {
    check(
      !source.includes("@/lib/data/"),
      `${relativePath} is a client component and must not import "@/lib/data/*".`,
    );
  }

  if (!relativePath.startsWith("scripts/") && !relativePath.startsWith("tests/")) {
    check(!source.includes("console."), `${relativePath} contains console.* output.`);
  }
}

const sw = read("public/sw.js");
check(sw.includes("PRIVATE_PATH_PREFIXES"), "Service worker must explicitly define private path exclusions.");
check(sw.includes("isPrivatePath(url.pathname)"), "Service worker must bypass private paths at runtime.");
check(!sw.includes("cache.put(request, clone)).catch") || sw.includes("isCacheableStaticRequest"), "Service worker cache writes must be limited to static requests.");

const nextConfig = read("next.config.ts");
check(!nextConfig.includes("\"connect-src 'self' http: https:\""), "CSP must not allow broad production connect-src.");
check(!nextConfig.includes("\"frame-src 'self' https://checkout.paystack.com https:\""), "CSP must not allow every HTTPS frame.");
check(nextConfig.includes("isProduction"), "CSP must be environment-aware.");

const checkoutClient = read("components/CheckoutClient.tsx");
check(!checkoutClient.includes("@/lib/data/public"), "Checkout client must use client API helpers, not server data helpers.");
check(existsSync(path.join(root, "lib/client/checkout.ts")), "Missing client checkout API helper.");
check(existsSync(path.join(root, "app/api/checkout/payment-options/route.ts")), "Missing checkout payment-options API facade.");

try {
  const trackedGenerated = execFileSync(
    "git",
    [
      "ls-files",
      "--",
      "front/.next-dev.log",
      "front/.next-dev.err.log",
      "front/tsconfig.tsbuildinfo",
    ],
    { cwd: path.resolve(root, ".."), encoding: "utf8" },
  ).trim();

  check(!trackedGenerated, `Generated front artifacts are tracked by Git:\n${trackedGenerated}`);
} catch {
  // Git may be unavailable in some deployment environments; source checks still run.
}

if (failures.length > 0) {
  process.stderr.write(`${failures.length} front quality check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}

process.stdout.write("Front quality checks passed.\n");
