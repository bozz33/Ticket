import type { FrontPageData } from "@/lib/types";

import { getSectionsByType } from "./helpers";

export function renderLegalPage(page: FrontPageData) {
  const legalSections = getSectionsByType(page, "legal_article");

  return (
    <section>
      <div className="shell legal-layout">
        <aside>
          <nav aria-label="Sommaire" className="legal-toc">
            <h3>Sommaire</h3>
            <ol className="legal-toc__list">
              {legalSections.map((section) => (
                <li className="legal-toc__item" key={section.key}>
                  <a href={`#${section.key}`}>{section.title}</a>
                </li>
              ))}
            </ol>
          </nav>
        </aside>

        <article className="legal-content">
          {legalSections.map((section, index) => {
            const labelValueItems = section.items.filter((item) => item.label && item.value);
            const narrativeItems = section.items.filter((item) => item.title || item.body);

            return (
              <section className="legal-section" id={section.key} key={section.key}>
                <div className="legal-section__number">{index + 1}</div>
                {section.title ? <h2>{section.title}</h2> : null}
                {section.body ? <p>{section.body}</p> : null}
                {labelValueItems.length > 0 ? (
                  <table className="legal-table">
                    <tbody>
                      {labelValueItems.map((item, itemIndex) => (
                        <tr key={`${section.key}-table-${itemIndex}`}>
                          <td><strong>{item.label}</strong></td>
                          <td>{item.value}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                ) : null}
                {narrativeItems.length > 0 ? (
                  <div className="support-grid" style={{ marginTop: "18px" }}>
                    {narrativeItems.map((item, itemIndex) => (
                      <article className="explore-tile" key={`${section.key}-item-${itemIndex}`}>
                        {item.title ? <h3 style={{ margin: 0 }}>{item.title}</h3> : null}
                        {item.body ? <p style={{ margin: 0 }}>{item.body}</p> : null}
                      </article>
                    ))}
                  </div>
                ) : null}
              </section>
            );
          })}
        </article>
      </div>
    </section>
  );
}
