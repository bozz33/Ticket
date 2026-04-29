import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountMe } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function ProfilPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const user = token ? await getAccountMe(tenantSlug, token) : null;

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Mon profil</h1>
        <p className="ac-page-sub">Informations de votre compte acheteur.</p>
      </div>

      <div className="ac-detail" style={{ gridTemplateColumns: "1fr 1fr" }}>
        <div className="ac-detail-panel">
          <div className="ac-detail-panel__head">
            <span className="ac-detail-panel__title">Informations personnelles</span>
          </div>
          <div className="ac-detail-panel__body">
            {user ? (
              <>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Nom complet</span>
                  <span className="ac-detail-row__value">{user.name}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Adresse e-mail</span>
                  <span className="ac-detail-row__value">{user.email}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">ID compte</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">#{user.id}</span>
                </div>
              </>
            ) : (
              <p style={{ color: "var(--text-soft)", fontSize: "0.875rem" }}>
                Impossible de charger le profil.
              </p>
            )}
          </div>
        </div>

        <div className="ac-detail-panel">
          <div className="ac-detail-panel__head">
            <span className="ac-detail-panel__title">Sécurité</span>
          </div>
          <div className="ac-detail-panel__body">
            <p style={{ fontSize: "0.875rem", color: "var(--text-soft)", lineHeight: "1.6" }}>
              Pour modifier votre mot de passe ou mettre à jour vos informations,
              contactez le support de la plateforme.
            </p>
          </div>
        </div>
      </div>
    </>
  );
}
