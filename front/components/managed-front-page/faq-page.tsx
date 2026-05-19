import type { FrontPageData } from "@/lib/types";

import { getSectionsByType } from "./helpers";

export function renderFaqPage(page: FrontPageData) {
  const faqSections = getSectionsByType(page, "faq");

  return (
    <section>
      <div className="shell faq-layout">
        <aside>
          <nav aria-label="Categories FAQ" className="faq-categories">
            {faqSections.map((section) => (
              <a className="faq-category__btn" href={`#${section.key}`} key={section.key}>
                <span>{section.title}</span>
              </a>
            ))}
          </nav>
        </aside>

        <div>
          {faqSections.map((section) => (
            <section className="faq-section" id={section.key} key={section.key}>
              {section.title ? <h2>{section.title}</h2> : null}
              {section.body ? <p className="section-copy" style={{ marginBottom: "18px" }}>{section.body}</p> : null}
              <div className="faq-accordion">
                {section.items.map((item, index) => (
                  <details className="faq-accordion__item" key={`${section.key}-${index}`} open={index === 0}>
                    <summary className="faq-accordion__q">
                      <span>{item.title}</span>
                      <span aria-hidden="true" className="faq-accordion__q-icon">+</span>
                    </summary>
                    <div className="faq-accordion__a">
                      <p>{item.body}</p>
                    </div>
                  </details>
                ))}
              </div>
            </section>
          ))}
        </div>
      </div>
    </section>
  );
}
