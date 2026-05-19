import Link from "next/link";

import { OrganizerRegistrationForm } from "@/components/onboarding/OrganizerRegistrationForm";
import type { FrontPageSection, PlatformConfiguration } from "@/lib/types";

import { getBooleanSetting } from "./helpers";

export function renderOnboardingForm(section: FrontPageSection | undefined, platform: PlatformConfiguration) {
  if (!section) {
    return null;
  }

  if (!getBooleanSetting(section, "mount_form")) {
    return (
      <section className="section section--light" id="inscription">
        <div className="shell" style={{ maxWidth: "760px", textAlign: "center" }}>
          <div style={{ marginBottom: "20px" }}>
            {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
            {section.title ? <h2 style={{ fontSize: "1.8rem", fontWeight: 700, margin: "8px 0 12px" }}>{section.title}</h2> : null}
            {section.body ? <p style={{ color: "var(--text-soft)" }}>{section.body}</p> : null}
          </div>
          {section.primary_cta?.url && section.primary_cta.label ? (
            <Link className="button" href={section.primary_cta.url}>
              {section.primary_cta.label}
            </Link>
          ) : null}
        </div>
      </section>
    );
  }

  return (
    <section className="section section--light" id="inscription">
      <div className="shell" style={{ maxWidth: "520px" }}>
        <div style={{ marginBottom: "32px" }}>
          {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
          {section.title ? <h2 style={{ fontSize: "1.8rem", fontWeight: 700, margin: "8px 0 12px" }}>{section.title}</h2> : null}
          {section.body ? <p style={{ color: "var(--text-soft)" }}>{section.body}</p> : null}
        </div>
        <OrganizerRegistrationForm brandName={platform.brandName} />
      </div>
    </section>
  );
}
