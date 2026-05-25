"use client";

import { useCallback, useEffect, useState } from "react";

import type { PublicPassVerification, PublicReceiptVerification } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

type VerificationResult =
  | { type: "receipt"; receipt: PublicReceiptVerification }
  | { type: "pass"; pass: PublicPassVerification };

type VerificationLookupPageProps = {
  initialRef?: string;
  initialTenant?: string;
};

type ExtractedReference = {
  ref: string;
  tenant?: string;
};

type BarcodeDetectorInstance = {
  detect(image: ImageBitmapSource): Promise<Array<{ rawValue: string }>>;
};

type BarcodeDetectorConstructor = new (options?: { formats?: string[] }) => BarcodeDetectorInstance;

type WindowWithBarcodeDetector = Window &
  typeof globalThis & {
    BarcodeDetector?: BarcodeDetectorConstructor;
  };

function extractReference(rawValue: string): ExtractedReference {
  const value = rawValue.trim();

  if (!value) {
    return { ref: "" };
  }

  try {
    const parsed = new URL(value);
    const queryRef = parsed.searchParams.get("ref") ?? parsed.searchParams.get("reference");
    const queryTenant = parsed.searchParams.get("tenant") ?? undefined;

    if (queryRef) {
      return { ref: queryRef.trim(), tenant: queryTenant };
    }

    const parts = parsed.pathname.split("/").filter(Boolean);
    const receiptIndex = parts.indexOf("recu");

    if (parts[0] === "verifier" && receiptIndex >= 0 && parts[receiptIndex + 2]) {
      return {
        ref: decodeURIComponent(parts[receiptIndex + 2]),
        tenant: parts[receiptIndex + 1] ? decodeURIComponent(parts[receiptIndex + 1]) : queryTenant,
      };
    }

    if (parts[0] === "verifier" && parts[1]) {
      return { ref: decodeURIComponent(parts[1]), tenant: queryTenant };
    }
  } catch {
    // The QR may contain a plain reference instead of a URL.
  }

  try {
    const json = JSON.parse(value) as Record<string, unknown>;
    const jsonRef = json.reference ?? json.code ?? json.public_id ?? json.receipt;

    if (typeof jsonRef === "string") {
      return {
        ref: jsonRef.trim(),
        tenant: typeof json.tenant === "string" ? json.tenant : undefined,
      };
    }
  } catch {
    // Plain references are handled below.
  }

  return { ref: value };
}

function formatVerificationDate(value: string | null) {
  if (!value) {
    return "—";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

function formatVerificationDateRange(startsAt?: string | null, endsAt?: string | null) {
  if (!startsAt) {
    return "—";
  }

  const startLabel = formatVerificationDate(startsAt);

  if (!endsAt) {
    return startLabel;
  }

  return `${startLabel} - ${formatVerificationDate(endsAt)}`;
}

export function VerificationLookupPage({ initialRef = "", initialTenant = "" }: VerificationLookupPageProps) {
  const [reference, setReference] = useState(initialRef);
  const [tenant, setTenant] = useState(initialTenant);
  const [result, setResult] = useState<VerificationResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [scanMessage, setScanMessage] = useState<string | null>(null);

  const performLookup = useCallback(async (nextReference: string, nextTenant: string, signal?: AbortSignal) => {
    const extracted = extractReference(nextReference);
    const ref = extracted.ref;
    const tenantSlug = extracted.tenant ?? nextTenant;

    if (!ref) {
      setMessage("Saisissez une référence de reçu, de paiement, de pass ou scannez un QR code.");
      setResult(null);
      return;
    }

    setLoading(true);
    setMessage(null);
    setResult(null);

    try {
      const params = new URLSearchParams({ ref });

      if (tenantSlug) {
        params.set("tenant", tenantSlug);
      }

      const response = await fetch(`/api/public/verify-reference?${params.toString()}`, {
        cache: "no-store",
        signal,
        headers: { Accept: "application/json" },
      });
      const payload = (await response.json().catch(() => null)) as { data?: VerificationResult; error?: string } | null;

      if (!response.ok || !payload?.data) {
        setMessage(payload?.error ?? "Aucun document authentique ne correspond à cette référence.");
        return;
      }

      setReference(ref);
      setTenant(tenantSlug);
      setResult(payload.data);
    } catch (error) {
      if (error instanceof Error && error.name === "AbortError") {
        return;
      }

      setMessage("La vérification est momentanément indisponible.");
    } finally {
      if (!signal?.aborted) {
        setLoading(false);
      }
    }
  }, []);

  async function handleQrImage(file: File | undefined) {
    if (!file) {
      return;
    }

    const barcodeDetector = (window as WindowWithBarcodeDetector).BarcodeDetector;

    if (!barcodeDetector) {
      setScanMessage("Le scan QR n'est pas disponible dans ce navigateur. Saisissez la référence manuellement.");
      return;
    }

    setScanMessage("Lecture du QR code...");

    try {
      const bitmap = await createImageBitmap(file);
      const detector = new barcodeDetector({ formats: ["qr_code"] });
      const codes = await detector.detect(bitmap);
      bitmap.close();

      const rawValue = codes[0]?.rawValue ?? "";
      const extracted = extractReference(rawValue);

      if (!extracted.ref) {
        setScanMessage("Aucune référence exploitable n'a été trouvée dans ce QR code.");
        return;
      }

      setReference(extracted.ref);

      if (extracted.tenant) {
        setTenant(extracted.tenant);
      }

      setScanMessage("QR code lu. Vérification en cours...");
      await performLookup(extracted.ref, extracted.tenant ?? tenant);
    } catch {
      setScanMessage("Impossible de lire cette image QR. Essayez une image plus nette.");
    }
  }

  useEffect(() => {
    if (!initialRef) {
      return undefined;
    }

    const controller = new AbortController();

    void performLookup(initialRef, initialTenant, controller.signal);

    return () => {
      controller.abort();
    };
  }, [initialRef, initialTenant, performLookup]);

  return (
    <main className="public-verification">
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Vérification publique</p>
          <h1>Vérifier un justificatif Ticket</h1>
          <p>Contrôlez l’authenticité d’un reçu, d’une référence de paiement, d’un numéro de référence ou d’un QR code.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell public-verification__grid">
          <form
            className="public-verification__form"
            onSubmit={(event) => {
              event.preventDefault();
              void performLookup(reference, tenant);
            }}
          >
            <label htmlFor="verification-reference">Référence à vérifier</label>
            <input
              id="verification-reference"
              onChange={(event) => setReference(event.target.value)}
              placeholder="RCP-260516-0826-XPOUMZ, PAY-..., N° référence ou code QR"
              value={reference}
            />

            <label htmlFor="verification-tenant">Espace organisateur</label>
            <input
              id="verification-tenant"
              onChange={(event) => setTenant(event.target.value)}
              placeholder="Laissez vide pour l'espace public par défaut"
              value={tenant}
            />

            <div className="public-verification__actions">
              <button className="button" disabled={loading} type="submit">
                {loading ? "Vérification..." : "Vérifier"}
              </button>
              <label className="button button--ghost public-verification__upload" htmlFor="verification-qr-image">
                Scanner un QR
                <input
                  accept="image/*"
                  aria-label="Scanner un QR code depuis une image"
                  id="verification-qr-image"
                  onChange={(event) => {
                    void handleQrImage(event.target.files?.[0]);
                    event.currentTarget.value = "";
                  }}
                  type="file"
                />
              </label>
            </div>

            {scanMessage ? <p className="public-verification__hint">{scanMessage}</p> : null}
            {message ? <p className="public-verification__error">{message}</p> : null}
          </form>

          <div className="public-verification__result">
            {!result ? (
              <div className="public-verification__empty">
                <strong>Authentification indépendante</strong>
                <p>Cette page sert à vérifier un document ou une référence publique. Elle ne remplace pas le scan d’accès à un événement.</p>
              </div>
            ) : null}

            {result?.type === "receipt" ? (
              <article className="public-verification__card">
                <span className="ac-badge ac-badge--issued">
                  <span className="ac-badge__dot" />
                  Reçu authentique
                </span>
                <h2>{result.receipt.reference}</h2>
                <dl>
                  <div>
                    <dt>Montant</dt>
                    <dd>{formatMoney(result.receipt.total_amount, result.receipt.currency_code)}</dd>
                  </div>
                  <div>
                    <dt>Émis le</dt>
                    <dd>{formatVerificationDate(result.receipt.issued_at)}</dd>
                  </div>
                  <div>
                    <dt>Commande</dt>
                    <dd>{result.receipt.order_reference ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Référence paiement</dt>
                    <dd>{result.receipt.transaction_reference ?? result.receipt.gateway_reference ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Client</dt>
                    <dd>{result.receipt.buyer_name ?? result.receipt.buyer_email_masked ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Statut</dt>
                    <dd>{result.receipt.status}</dd>
                  </div>
                  <div>
                    <dt>Événement</dt>
                    <dd>{result.receipt.event?.title ?? result.receipt.offer_name ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Date de l'événement</dt>
                    <dd>{formatVerificationDateRange(result.receipt.event?.starts_at, result.receipt.event?.ends_at)}</dd>
                  </div>
                  <div>
                    <dt>Localisation</dt>
                    <dd>{result.receipt.event?.location || result.receipt.event?.venue_address || "—"}</dd>
                  </div>
                </dl>
              </article>
            ) : null}

            {result?.type === "pass" ? (
              <article className="public-verification__card">
                <span className={`ac-badge ac-badge--${result.pass.status}`}>
                  <span className="ac-badge__dot" />
                  Pass {result.pass.status}
                </span>
                <h2>{result.pass.type_label}</h2>
                <dl>
                  <div>
                    <dt>Titulaire</dt>
                    <dd>{result.pass.holder_name ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Statut</dt>
                    <dd>{result.pass.status}</dd>
                  </div>
                  <div>
                    <dt>Utilisé le</dt>
                    <dd>{formatVerificationDate(result.pass.used_at)}</dd>
                  </div>
                  <div>
                    <dt>Expire le</dt>
                    <dd>{formatVerificationDate(result.pass.expires_at)}</dd>
                  </div>
                  <div>
                    <dt>Événement</dt>
                    <dd>{result.pass.event?.title ?? "—"}</dd>
                  </div>
                  <div>
                    <dt>Date de l'événement</dt>
                    <dd>{formatVerificationDateRange(result.pass.event?.starts_at, result.pass.event?.ends_at)}</dd>
                  </div>
                  <div>
                    <dt>Localisation</dt>
                    <dd>{result.pass.event?.location || result.pass.event?.venue_address || "—"}</dd>
                  </div>
                </dl>
              </article>
            ) : null}
          </div>
        </div>
      </section>
    </main>
  );
}
