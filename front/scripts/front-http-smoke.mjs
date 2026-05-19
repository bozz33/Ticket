const baseUrl = process.env.FRONT_SMOKE_BASE_URL ?? "http://127.0.0.1:3000";

const routes = [
  { path: "/", label: "home" },
  { path: "/evenements", label: "events catalog" },
  { path: "/recherche?q=formation", label: "search" },
  { path: "/compte/connexion", label: "account login" },
  { path: "/devenir-organisateur/inscription", label: "organizer onboarding" },
];

const failures = [];

for (const route of routes) {
  const url = new URL(route.path, baseUrl);

  try {
    const response = await fetch(url, {
      headers: { Accept: "text/html" },
      redirect: "manual",
      signal: AbortSignal.timeout(15_000),
    });
    const contentType = response.headers.get("content-type") ?? "";

    if (response.status < 200 || response.status >= 400) {
      failures.push(`${route.label} (${url}) returned HTTP ${response.status}.`);
      continue;
    }

    if (!contentType.includes("text/html")) {
      failures.push(`${route.label} (${url}) returned ${contentType || "no content type"} instead of text/html.`);
    }
  } catch (error) {
    failures.push(`${route.label} (${url}) failed: ${error instanceof Error ? error.message : String(error)}.`);
  }
}

if (failures.length > 0) {
  process.stderr.write(`${failures.length} HTTP smoke check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}

process.stdout.write(`HTTP smoke checks passed against ${baseUrl}.\n`);
