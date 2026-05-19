import type { FrontPageData, PlatformConfiguration } from "@/lib/types";

import { getSectionsByType } from "./managed-front-page/helpers";
export { fallbackPrimaryLinks, fallbackUtilityLinks } from "./managed-front-page/navigation";
import {
  renderContactComposite,
  renderFaqPage,
  renderFeatureGrid,
  renderHero,
  renderLegalPage,
  renderMetrics,
  renderOnboardingForm,
  renderOrganizerHighlights,
  renderSplitOverview,
} from "./managed-front-page/renderers";
import type { OrganizerHighlight } from "./managed-front-page/types";

export function ManagedFrontPage({
  page,
  platform,
  organizers = [],
  fallbackHeroImage,
}: {
  page: FrontPageData;
  platform: PlatformConfiguration;
  organizers?: OrganizerHighlight[];
  fallbackHeroImage?: string;
}) {
  const hero = getSectionsByType(page, "hero")[0];

  return (
    <>
      {renderHero(hero, fallbackHeroImage)}

      {page.template === "marketing_page" ? (
        <>
          {renderSplitOverview(getSectionsByType(page, "split_overview")[0])}
          {renderOrganizerHighlights(getSectionsByType(page, "organizer_highlights")[0], organizers)}
        </>
      ) : null}

      {page.template === "contact_page" ? (
        <>
          {renderContactComposite(
            getSectionsByType(page, "contact_channels")[0],
            getSectionsByType(page, "contact_form")[0],
          )}
          {renderFeatureGrid(getSectionsByType(page, "feature_grid")[0], true)}
        </>
      ) : null}

      {page.template === "faq_page" ? (
        <>
          {renderMetrics(getSectionsByType(page, "metrics")[0])}
          {renderFaqPage(page)}
        </>
      ) : null}

      {page.template === "legal_page" ? renderLegalPage(page) : null}

      {page.template === "onboarding_page" ? (
        <>
          {getSectionsByType(page, "feature_grid").map((section, index) => (
            <div key={section.key}>{renderFeatureGrid(section, index % 2 === 1)}</div>
          ))}
          {renderOnboardingForm(getSectionsByType(page, "onboarding_form")[0], platform)}
        </>
      ) : null}

      {page.template === "content_page"
        ? page.sections
            .filter((section) => section.type !== "hero")
            .map((section) => renderFeatureGrid(section))
        : null}
    </>
  );
}
