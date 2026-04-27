import Link from "next/link";

export default function NotFound() {
  return (
    <section className="section">
      <div className="shell empty-state">
        <h1>Contenu introuvable</h1>
        <p>La ressource demandee n'est pas disponible ou n'est plus publiee.</p>
        <Link className="button" href="/">
          Retour a l'accueil
        </Link>
      </div>
    </section>
  );
}
