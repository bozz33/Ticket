import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { AboutPageView } from "@/components/about/AboutPageView";
import {
  getFrontPageData,
  getOrganizerHighlights,
  getPlatformConfiguration,
  getPublicContentSummary,
} from "@/lib/data/public";

export const revalidate = 120;
export const generateMetadata = () =>
  getManagedPageMetadata("/a-propos", {
    title: "A propos | Ticket",
    description:
      "Découvrez Ticket, une plateforme publique pensée pour publier, réserver, acheter, candidater et piloter des contenus multi-modules.",
  });

export default async function AboutPage() {
  const [platform, page, organizers, summary] = await Promise.all([
    getPlatformConfiguration(),
    getFrontPageData("/a-propos"),
    getOrganizerHighlights(),
    getPublicContentSummary(),
  ]);

  return (
    <AboutPageView
      organizers={organizers}
      page={page}
      platform={platform}
      summary={summary}
    />
  );
}
