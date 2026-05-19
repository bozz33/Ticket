import { notFound } from "next/navigation";

import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { OrganizerBodySection } from "./organizer/OrganizerBodySection";
import { OrganizerCatalogSection } from "./organizer/OrganizerCatalogSection";
import { OrganizerHero } from "./organizer/OrganizerHero";
import type { OrganizerViewProps } from "./organizer/types";

export async function OrganizerView({
  organizer,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  stats,
}: OrganizerViewProps) {
  if (!organizer) {
    notFound();
  }

  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);

  return (
    <>
      <OrganizerHero organizer={organizer} stats={stats} />
      <OrganizerBodySection organizer={organizer} />
      <OrganizerCatalogSection
        accountAuthenticated={accountAuthenticated}
        currentPage={currentPage}
        filters={filters}
        items={items}
        likeSummaries={likeSummaries}
        organizer={organizer}
        totalItems={totalItems}
        totalPages={totalPages}
      />
    </>
  );
}
