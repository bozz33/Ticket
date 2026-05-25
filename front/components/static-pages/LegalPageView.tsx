import Link from "next/link";

import type { LegalPageContent } from "./legal-pages";

export function LegalPageView({ content }: { content: LegalPageContent }) {
  return (
    <>
      <section className="page-hero legal-page-hero">
        <div className="shell page-hero__content">
          <p className="eyebrow">{content.eyebrow}</p>
          <h1>{content.title}</h1>
          <p>{content.description}</p>
          <p className="inner-hero__meta">{content.updatedAt}</p>
        </div>
      </section>

      <section className="section">
        <div className="shell legal-layout">
          <aside>
            <nav aria-label="Sommaire" className="legal-toc">
              <h3>Sommaire</h3>
              <ol className="legal-toc__list">
                {content.sections.map((section) => (
                  <li className="legal-toc__item" key={section.id}>
                    <a href={`#${section.id}`}>{section.title}</a>
                  </li>
                ))}
              </ol>
              {content.relatedLinks && content.relatedLinks.length > 0 ? (
                <div className="legal-toc__related" aria-label="Documents liés">
                  {content.relatedLinks.map((link) => (
                    <Link className="legal-toc__related-link" href={link.href} key={link.href}>
                      <strong>{link.title}</strong>
                      <span>{link.body}</span>
                    </Link>
                  ))}
                </div>
              ) : null}
            </nav>
          </aside>

          <article className="legal-content">
            {content.sections.map((section, index) => {
              const tableItems = section.items?.filter((item) => item.label && item.value) ?? [];
              const cardItems = section.items?.filter((item) => item.title || item.body) ?? [];

              return (
                <section className="legal-section" id={section.id} key={section.id}>
                  <div className="legal-section__number">{index + 1}</div>
                  <h2>{section.title}</h2>
                  {section.body ? <p>{section.body}</p> : null}
                  {tableItems.length > 0 ? (
                    <table className="legal-table">
                      <tbody>
                        {tableItems.map((item) => (
                          <tr key={`${section.id}-${item.label}`}>
                            <td><strong>{item.label}</strong></td>
                            <td>{item.value}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  ) : null}
                  {cardItems.length > 0 ? (
                    <div className="support-grid legal-card-grid">
                      {cardItems.map((item) => (
                        <article className="explore-tile" key={`${section.id}-${item.title}`}>
                          {item.title ? <h3>{item.title}</h3> : null}
                          {item.body ? <p>{item.body}</p> : null}
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
    </>
  );
}

export function LegalNoticePageView({ content }: { content: LegalPageContent }) {
  return <LegalPageView content={content} />;
}
