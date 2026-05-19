import { getStaticPageHeroImage } from "@/lib/utils";

import { AboutCapabilitiesSection } from "./AboutCapabilitiesSection";
import { AboutCtaSection } from "./AboutCtaSection";
import { AboutFlowSection } from "./AboutFlowSection";
import { AboutHeroSection } from "./AboutHeroSection";
import { AboutModulesSection } from "./AboutModulesSection";
import { AboutOrganizerTrustSection } from "./AboutOrganizerTrustSection";
import { AboutStorySection } from "./AboutStorySection";
import type { AboutPageViewProps } from "./types";

export function AboutPageView({ organizers, page, platform, summary }: AboutPageViewProps) {
  const hero = page?.sections.find((section) => section.type === "hero");
  const heroImage = hero?.image_url || getStaticPageHeroImage("a-propos");
  const galleryImages = summary.galleryImages;
  const highlightedOrganizers = organizers.slice(0, 6);
  const moduleCards = summary.moduleCards;

  return (
    <>
      <AboutHeroSection
        hero={hero}
        heroImage={heroImage}
        highlightedOrganizerCount={highlightedOrganizers.length}
        platform={platform}
        summary={summary}
      />
      <AboutStorySection galleryImages={galleryImages} heroImage={heroImage} summary={summary} />
      <AboutCapabilitiesSection />
      <AboutOrganizerTrustSection organizers={highlightedOrganizers} platform={platform} summary={summary} />
      <AboutModulesSection moduleCards={moduleCards} />
      <AboutFlowSection />
      <AboutCtaSection />
    </>
  );
}
