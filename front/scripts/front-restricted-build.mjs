import { spawnSync } from "node:child_process";
import { existsSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import path from "node:path";

const defaultDistDir = `.next-build-restricted-${Date.now()}`;
const env = {
  ...process.env,
  NEXT_DISABLE_BUILD_WORKERS: "1",
  NEXT_DIST_DIR: process.env.NEXT_DIST_DIR ?? defaultDistDir,
  NEXT_SKIP_BUILD_TYPECHECK: "1",
};

const root = process.cwd();
const distDir = path.resolve(root, env.NEXT_DIST_DIR);
const relativeDistDir = path.relative(root, distDir);

if (
  relativeDistDir.startsWith("..") ||
  path.isAbsolute(relativeDistDir) ||
  !path.basename(distDir).startsWith(".next-build")
) {
  process.stderr.write(`Refusing to clean unsafe build directory: ${distDir}\n`);
  process.exit(1);
}

try {
  rmSync(distDir, { force: true, recursive: true });
} catch (error) {
  process.stderr.write(`Could not clean restricted build directory ${distDir}: ${error instanceof Error ? error.message : String(error)}\n`);
  process.exit(1);
}

const tsconfigPath = path.join(root, "tsconfig.json");
const tsconfigBefore = existsSync(tsconfigPath) ? readFileSync(tsconfigPath, "utf8") : null;
const nextBin = "node_modules/next/dist/bin/next";
const result = spawnSync(process.execPath, [nextBin, "build", "--webpack"], {
  env,
  shell: false,
  stdio: "inherit",
});

if (tsconfigBefore !== null && existsSync(tsconfigPath) && readFileSync(tsconfigPath, "utf8") !== tsconfigBefore) {
  writeFileSync(tsconfigPath, tsconfigBefore);
}

if (result.error) {
  process.stderr.write(`Restricted build failed before Next.js completed: ${result.error.message}\n`);
  process.exit(1);
}

process.exit(result.status ?? 1);
