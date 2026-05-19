import { readFileSync, statSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const failures = [];

const fileBudgets = [
  ["app/globals.css", 240_000],
  ["app/compte/account.css", 60_000],
  ["app/a-propos/page.tsx", 6_000],
  ["app/compte/(panel)/layout.tsx", 6_000],
  ["app/compte/(panel)/profil/ProfileEditor.tsx", 4_000],
  ["app/compte/(panel)/commandes/[ref]/page.tsx", 4_000],
  ["app/compte/(panel)/passes/[id]/page.tsx", 4_000],
  ["app/compte/(panel)/recus/[ref]/imprimer/page.tsx", 4_000],
  ["app/verifier/[code]/page.tsx", 4_000],
  ["components/CatalogFilters.tsx", 16_000],
  ["components/CheckoutClient.tsx", 9_000],
  ["components/CallForProjectApplicationForm.tsx", 14_000],
  ["components/about/AboutPageView.tsx", 16_000],
  ["components/account/layout/NotificationBell.tsx", 8_000],
  ["components/account/order-detail/OrderDetailView.tsx", 12_000],
  ["components/account/pass-detail/PassDetailView.tsx", 9_000],
  ["components/account/pass-verification/PassVerificationView.tsx", 9_000],
  ["components/account/profile/ProfileInfoForm.tsx", 6_000],
  ["components/account/profile/useProfileEditor.ts", 12_000],
  ["components/account/receipt-print/ReceiptPrintDocument.tsx", 12_000],
  ["components/route/CheckoutRouteViews.tsx", 6_000],
  ["components/route/DetailRouteViews.tsx", 6_000],
  ["components/route/HomeRouteViews.tsx", 10_000],
  ["components/route/OrganizerRouteViews.tsx", 16_000],
  ["components/route/PublicListingViews.tsx", 10_000],
  ["components/call-for-project-application/ApplicationFieldRenderer.tsx", 14_000],
  ["components/call-for-project-application/helpers.ts", 9_000],
  ["components/call-for-project-application/ApplicationProgress.tsx", 8_000],
  ["components/call-for-project-application/ApplicationStepPanel.tsx", 7_000],
  ["components/managed-front-page/section-renderers.tsx", 9_000],
  ["components/managed-front-page/contact-page.tsx", 7_000],
  ["components/route/checkout/PaymentSuccessView.tsx", 8_000],
  ["components/route/checkout/ReceiptView.tsx", 9_000],
  ["components/route/detail/default-content.ts", 12_000],
  ["components/route/detail/DetailBlocks.tsx", 8_000],
  ["lib/data/public/shared.ts", 16_000],
  ["lib/data/public/catalog.ts", 15_000],
  ["lib/data/account/profile.ts", 8_000],
  ["lib/data/account/auth.ts", 7_000],
];

const lineBudgets = [
  ["components/CatalogFilters.tsx", 340],
  ["components/CheckoutClient.tsx", 230],
  ["components/CallForProjectApplicationForm.tsx", 320],
  ["components/route/CheckoutRouteViews.tsx", 120],
  ["components/route/DetailRouteViews.tsx", 150],
  ["components/route/HomeRouteViews.tsx", 220],
  ["components/route/OrganizerRouteViews.tsx", 360],
  ["components/route/PublicListingViews.tsx", 260],
  ["components/call-for-project-application/ApplicationFieldRenderer.tsx", 360],
  ["components/call-for-project-application/helpers.ts", 280],
  ["components/call-for-project-application/ApplicationProgress.tsx", 220],
  ["components/call-for-project-application/ApplicationStepPanel.tsx", 180],
  ["components/managed-front-page/section-renderers.tsx", 220],
  ["components/managed-front-page/contact-page.tsx", 160],
  ["components/route/checkout/PaymentSuccessView.tsx", 180],
  ["components/route/checkout/ReceiptView.tsx", 220],
  ["components/route/detail/default-content.ts", 280],
  ["components/route/detail/DetailBlocks.tsx", 180],
  ["lib/data/public/shared.ts", 500],
  ["lib/data/public/catalog.ts", 430],
  ["lib/data/account/profile.ts", 270],
  ["lib/data/account/auth.ts", 230],
  ["app/globals.css", 9_300],
  ["app/a-propos/page.tsx", 80],
  ["app/compte/(panel)/layout.tsx", 140],
  ["app/compte/(panel)/profil/ProfileEditor.tsx", 80],
  ["app/compte/(panel)/commandes/[ref]/page.tsx", 70],
  ["app/compte/(panel)/passes/[id]/page.tsx", 60],
  ["app/compte/(panel)/recus/[ref]/imprimer/page.tsx", 60],
  ["app/verifier/[code]/page.tsx", 70],
  ["components/about/AboutPageView.tsx", 340],
  ["components/account/layout/NotificationBell.tsx", 190],
  ["components/account/order-detail/OrderDetailView.tsx", 240],
  ["components/account/pass-detail/PassDetailView.tsx", 220],
  ["components/account/pass-verification/PassVerificationView.tsx", 220],
  ["components/account/profile/ProfileInfoForm.tsx", 140],
  ["components/account/profile/useProfileEditor.ts", 280],
  ["components/account/receipt-print/ReceiptPrintDocument.tsx", 280],
];

for (const [relativePath, maxBytes] of fileBudgets) {
  const fullPath = path.join(root, relativePath);
  const bytes = statSync(fullPath).size;

  if (bytes > maxBytes) {
    failures.push(`${relativePath} is ${bytes} bytes, budget is ${maxBytes}.`);
  }
}

for (const [relativePath, maxLines] of lineBudgets) {
  const fullPath = path.join(root, relativePath);
  const lines = readFileSync(fullPath, "utf8").split(/\r?\n/).length;

  if (lines > maxLines) {
    failures.push(`${relativePath} is ${lines} lines, budget is ${maxLines}.`);
  }
}

if (failures.length > 0) {
  process.stderr.write(`${failures.length} performance budget check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}

process.stdout.write("Front performance budgets passed.\n");
