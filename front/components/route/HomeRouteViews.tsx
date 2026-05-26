import Link from "next/link";

import { buildContentCardLabels } from "@/components/content-card/labels";
import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { SectionHeader } from "@/components/route/SectionHeader";
import { resolveSupportedLocale, translate } from "@/lib/i18n/public-translations";
import type {
  FrontPageData,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
} from "@/lib/types";

import { HeroSearch } from "./home/HeroSearch";
import { HomeContentCardGrid } from "./home/HomeContentCardGrid";
import { getFrontSection, sectionStats, sectionText } from "./home/helpers";
import { StatIcon } from "./home/StatIcon";

export async function HomeView({
  page,
  platform,
  featured,
  popular,
  organizers,
  categories,
  stats,
  locale: requestedLocale,
}: {
  page?: FrontPageData | null;
  platform: PlatformConfiguration;
  featured: PublicContent[];
  popular: PublicContent[];
  organizers: Array<{ organizer: OrganizerProfile; items: PublicContent[] }>;
  categories: string[];
  stats: Array<{ label: string; value: string }>;
  locale?: string;
}) {
  const locale = resolveSupportedLocale(platform, requestedLocale);
  const t = (key: string, fallback: string) => translate(platform, locale, key, fallback);
  const contentCardLabels = buildContentCardLabels(t);
  const hero = getFrontSection(page, "hero", "home_hero") ?? getFrontSection(page, "hero");
  const metrics = getFrontSection(page, "metrics", "home_metrics") ?? getFrontSection(page, "metrics");
  const featuredSection = getFrontSection(page, "feature_grid", "home_featured");
  const popularSection = getFrontSection(page, "feature_grid", "home_popular");
  const organizersSection = getFrontSection(page, "organizer_highlights", "home_organizers") ?? getFrontSection(page, "organizer_highlights");
  const displayedStats = sectionStats(metrics, stats);
  const translatedStats = displayedStats.map((stat) => {
    const normalizedLabel = stat.label.toLowerCase();
    const key = normalizedLabel.includes("organisateur")
      ? "home.stats.organizers"
      : normalizedLabel.includes("utilisateur")
        ? "home.stats.users"
        : "home.stats.items";

    return { ...stat, label: t(key, stat.label) };
  });
  const { accountAuthenticated, accountSessionKey, followSummaries, likeSummaries } = await getLikeRenderingContext([...featured, ...popular]);
  const primaryCtaLabel = !hero?.primary_cta?.label || hero.primary_cta.label === "Explorer le catalogue"
    ? t("home.hero.primary_cta", "Vérifier un ticket")
    : hero.primary_cta.label;
  const primaryCtaUrl = primaryCtaLabel === t("home.hero.primary_cta", "Vérifier un ticket")
    ? "/verifier"
    : hero?.primary_cta?.url || "/evenements";

  return (
    <>
      <section className="hero">
        <div className="hero__image">
          <img
            alt={t("home.hero.image_alt", sectionText(hero?.title, "Scene premium et public pendant un evenement"))}
            decoding="async"
            fetchPriority="high"
            src={sectionText(hero?.image_url, "https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1800&q=80")}
          />
        </div>
        <div className="shell hero__content">
          <div className="hero__copy">
            <p className="eyebrow">{t("home.hero.eyebrow", sectionText(hero?.eyebrow, `Marketplace publique · ${platform.brandName}`))}</p>
            <h1>{t("home.hero.title", sectionText(hero?.title, "Des experiences a reserver, soutenir ou rejoindre."))}</h1>
            <p className="hero__lede">
              {t(
                "home.hero.body",
                sectionText(
                  hero?.body,
                  "Un catalogue premium pour billets, formations, stands, candidatures et campagnes, avec des parcours d'achat clairs et une mise en avant forte des organisateurs.",
                ),
              )}
            </p>
            <div className="hero__actions">
              <Link className="button" href={primaryCtaUrl}>
                {primaryCtaLabel}
              </Link>
              <Link className="button button--ghost-light" href={hero?.secondary_cta?.url || "/devenir-organisateur"}>
                {t("home.hero.secondary_cta", hero?.secondary_cta?.label || "Publier sur la plateforme")}
              </Link>
            </div>
          </div>

          <HeroSearch categories={categories} locale={locale} platform={platform} />
        </div>
      </section>

      <section className="stats-strip stats-strip--floating">
        <div className="shell stats-strip__grid">
          {translatedStats.map((stat, index) => (
            <article className="stat-tile" key={stat.label}>
              <span aria-hidden="true" className="stat-tile__icon">
                <StatIcon index={index} />
              </span>
              <span className="stat-tile__copy">
                <strong>{stat.value}</strong>
                <span>{stat.label}</span>
              </span>
            </article>
          ))}
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <SectionHeader
            action={
              <Link className="button button--ghost" href="/recherche">
                {t("common.view_all", "Tout voir")}
              </Link>
            }
            description={t("home.featured.description", featuredSection?.body ?? "Une vitrine riche et visuelle, avec des cartes denses et des CTA directs.")}
            eyebrow={t("home.featured.eyebrow", sectionText(featuredSection?.eyebrow, "Selection editee"))}
            title={t("home.featured.title", sectionText(featuredSection?.title, "A la une"))}
          />
          <HomeContentCardGrid
            accountAuthenticated={accountAuthenticated}
            accountSessionKey={accountSessionKey}
            followSummaries={followSummaries}
            items={featured}
            labels={contentCardLabels}
            likeSummaries={likeSummaries}
          />
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            description={t("home.popular.description", popularSection?.body ?? "Les contenus qui ont recu le plus de mentions j'aime cette semaine.")}
            eyebrow={t("home.popular.eyebrow", sectionText(popularSection?.eyebrow, "Tendances"))}
            title={t("home.popular.title", sectionText(popularSection?.title, "Populaires cette semaine"))}
          />
          <HomeContentCardGrid
            accountAuthenticated={accountAuthenticated}
            accountSessionKey={accountSessionKey}
            followSummaries={followSummaries}
            items={popular}
            labels={contentCardLabels}
            likeSummaries={likeSummaries}
          />
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <SectionHeader
            description={t("home.organizers.description", organizersSection?.body ?? "Chaque organisateur peut etre valorise comme une vraie page publique.")}
            eyebrow={t("home.organizers.eyebrow", sectionText(organizersSection?.eyebrow, "Organisateurs"))}
            title={t("home.organizers.title", sectionText(organizersSection?.title, "Profils publics mis en avant"))}
          />
          <div className="organizer-grid">
            {organizers.map(({ organizer, items }) => (
              <article className="organizer-card" key={organizer.slug}>
                <div className="organizer-card__banner">
                  <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.bannerUrl} />
                </div>
                <div className="organizer-card__body">
                  <div className="organizer-card__identity">
                    <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.logoUrl} />
                    <div>
                      <Link href={`/organisateurs/${organizer.slug}`}>{organizer.name}</Link>
                      <p>
                        {organizer.city}, {organizer.country}
                      </p>
                    </div>
                  </div>
                  <p className="organizer-card__copy">{organizer.tagline}</p>
                  <div className="organizer-card__mini-list">
                    {items.map((item) => (
                      <Link href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`} key={item.id}>
                        {item.title}
                      </Link>
                    ))}
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
