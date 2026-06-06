"use client";

import { useState } from "react";
import QRCode from "qrcode";

export type ReceiptDownloadPayload = {
  brandName: string;
  brandTagline: string;
  buyerEmail: string;
  buyerName: string;
  buyerPhone: string;
  issuedAt: string;
  lineAmount: string;
  offerName: string;
  orderReference: string;
  paymentMethod: string;
  paymentReference: string;
  providerReference: string;
  quantity: number;
  receiptReference: string;
  serviceDescription: string;
  serviceFee: string;
  total: string;
  verificationUrl: string;
};

type ReceiptDownloadButtonProps = {
  payload: ReceiptDownloadPayload;
};

const encoder = new TextEncoder();
const pageWidth = 595.28;
const pageHeight = 841.89;

function asciiText(value: string) {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^\x20-\x7E]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function pdfEscape(value: string) {
  return asciiText(value).replace(/\\/g, "\\\\").replace(/\(/g, "\\(").replace(/\)/g, "\\)");
}

function text(value: string, x: number, y: number, size = 9, font: "F1" | "F2" = "F1") {
  return `BT /${font} ${size} Tf ${x.toFixed(2)} ${y.toFixed(2)} Td (${pdfEscape(value)}) Tj ET\n`;
}

function rect(x: number, y: number, width: number, height: number, mode: "S" | "f" = "S") {
  return `${x.toFixed(2)} ${y.toFixed(2)} ${width.toFixed(2)} ${height.toFixed(2)} re ${mode}\n`;
}

function line(x1: number, y1: number, x2: number, y2: number) {
  return `${x1.toFixed(2)} ${y1.toFixed(2)} m ${x2.toFixed(2)} ${y2.toFixed(2)} l S\n`;
}

function wrap(value: string, maxLength: number) {
  const words = asciiText(value).split(" ");
  const lines: string[] = [];
  let current = "";

  words.forEach((word) => {
    const next = current ? `${current} ${word}` : word;

    if (next.length > maxLength && current) {
      lines.push(current);
      current = word;
      return;
    }

    current = next;
  });

  if (current) {
    lines.push(current);
  }

  return lines.slice(0, 3);
}

function fromBase64(dataUrl: string) {
  const base64 = dataUrl.split(",", 2)[1] ?? "";
  const binary = window.atob(base64);
  const bytes = new Uint8Array(binary.length);

  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index);
  }

  return bytes;
}

function concat(chunks: Uint8Array[]) {
  const total = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
  const output = new Uint8Array(total);
  let offset = 0;

  chunks.forEach((chunk) => {
    output.set(chunk, offset);
    offset += chunk.length;
  });

  return output;
}

function chunk(value: string | Uint8Array) {
  return typeof value === "string" ? encoder.encode(value) : value;
}

function buildPdf(objects: Array<Array<string | Uint8Array>>) {
  const chunks: Uint8Array[] = [];
  const offsets: number[] = [];
  let offset = 0;

  function push(value: string | Uint8Array) {
    const bytes = chunk(value);
    chunks.push(bytes);
    offset += bytes.length;
  }

  push("%PDF-1.4\n%\xE2\xE3\xCF\xD3\n");

  objects.forEach((object, index) => {
    offsets[index + 1] = offset;
    push(`${index + 1} 0 obj\n`);
    object.forEach(push);
    push("\nendobj\n");
  });

  const xrefOffset = offset;
  const xrefRows = offsets
    .slice(1)
    .map((item) => `${String(item).padStart(10, "0")} 00000 n `)
    .join("\n");

  push(`xref\n0 ${objects.length + 1}\n0000000000 65535 f \n${xrefRows}\n`);
  push(`trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefOffset}\n%%EOF`);

  return concat(chunks);
}

async function createReceiptPdf(payload: ReceiptDownloadPayload) {
  const qrDataUrl = await QRCode.toDataURL(payload.verificationUrl, {
    type: "image/jpeg",
    width: 240,
    margin: 3,
    color: { dark: "#111820", light: "#ffffff" },
  });
  const qrBytes = fromBase64(qrDataUrl);
  const content: string[] = [];
  const left = 52;
  const right = pageWidth - 52;

  content.push("1 w\n");
  content.push("0.84 0.60 0.21 rg\n");
  content.push(rect(left, 765, 24, 24, "f"));
  content.push("0 0 0 RG\n1 1 1 rg\n");
  content.push(text("T", left + 8, 772, 12, "F2"));
  content.push("0.84 0.60 0.21 rg\n");
  content.push(text(payload.brandName, left + 32, 775, 15, "F2"));
  content.push("0 0 0 rg\n");
  content.push(text(payload.brandTagline, left + 32, 764, 6));
  content.push(text(`${payload.brandName} Services`, right - 88, 780, 7, "F2"));
  content.push(text("Plateforme de billetterie, reservations et paiements", right - 148, 768, 6));
  content.push(text("Support : support@ticket.africa - +225 27 22 40 11 00", right - 172, 758, 6));
  content.push(text(`RECU : ${payload.receiptReference}`, right - 170, 710, 9, "F2"));

  content.push(rect(left, 610, 230, 90));
  content.push(text("Details du paiement :", left + 12, 673, 10, "F2"));
  content.push(text(`Paye le : ${payload.issuedAt}`, left + 12, 654, 7));
  content.push(text(`Mode de reglement : ${payload.paymentMethod}`, left + 12, 641, 7));
  content.push(text(`Reference paiement : ${payload.paymentReference}`, left + 12, 628, 7));
  content.push(text(`N Reference : ${payload.providerReference}`, left + 12, 615, 7));

  content.push(rect(left + 245, 610, 245, 90));
  content.push(text("Client facture :", left + 257, 673, 10, "F2"));
  content.push(text(payload.buyerName, left + 257, 654, 7, "F2"));
  content.push(text(payload.buyerEmail, left + 257, 641, 7));
  content.push(text(payload.buyerPhone, left + 257, 628, 7));
  content.push(text(`Commande : ${payload.orderReference}`, left + 257, 615, 7));

  content.push(rect(left, 490, 490, 86));
  content.push("0.95 0.96 0.97 rg\n");
  content.push(rect(left, 546, 490, 30, "f"));
  content.push("0 0 0 rg\n");
  content.push(line(left + 320, 490, left + 320, 576));
  content.push(line(left + 380, 490, left + 380, 576));
  content.push(line(left, 546, left + 490, 546));
  content.push(text("Designation", left + 10, 558, 7, "F2"));
  content.push(text("Qte", left + 332, 558, 7, "F2"));
  content.push(text("Prix", left + 460, 558, 7, "F2"));
  content.push(text(payload.offerName, left + 10, 525, 7, "F2"));
  wrap(payload.serviceDescription, 64).forEach((lineValue, index) => {
    content.push(text(lineValue, left + 10, 512 - index * 9, 6));
  });
  content.push(text(String(payload.quantity), left + 334, 525, 7));
  content.push(text(payload.lineAmount, left + 435, 525, 7));

  content.push(rect(left, 360, 285, 92));
  content.push("0.98 0.98 0.98 rg\n");
  content.push(rect(left + 12, 373, 66, 66, "f"));
  content.push("0 0 0 rg\n");
  content.push("q\n60 0 0 60 67 376 cm\n/Im1 Do\nQ\n");
  content.push(text("QR de verification", left + 102, 414, 8, "F2"));
  content.push(text("Scannez pour confirmer l'authenticite du recu.", left + 102, 401, 6));
  content.push(text("Montant HT :", left + 305, 432, 7));
  content.push(text(payload.lineAmount, right - 85, 432, 7, "F2"));
  content.push(text("Frais de service :", left + 305, 412, 7));
  content.push(text(payload.serviceFee, right - 85, 412, 7, "F2"));
  content.push(text("NET A PAYER TTC :", left + 305, 389, 7));
  content.push(text(payload.total, right - 85, 389, 8, "F2"));

  content.push(rect(left, 220, 490, 86));
  content.push(text("CONDITIONS DE REGLEMENT", left + 12, 280, 9, "F2"));
  content.push(text("Ce recu confirme le paiement enregistre pour la commande indiquee et doit etre conserve comme justificatif de reglement.", left + 12, 260, 7));
  content.push(text("Le QR code permet de verifier publiquement le statut du recu et d'eviter toute reproduction frauduleuse.", left + 12, 245, 7));
  content.push("0.30 0.37 0.47 rg\n");
  content.push(text(`Document genere par ${payload.brandName}`, left, 76, 6));
  content.push(text(payload.verificationUrl, left + 155, 76, 6));
  content.push("0 0 0 rg\n");

  const contentBytes = encoder.encode(content.join(""));
  const pdfBytes = buildPdf([
    ["<< /Type /Catalog /Pages 2 0 R >>"],
    ["<< /Type /Pages /Kids [3 0 R] /Count 1 >>"],
    ["<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> /XObject << /Im1 6 0 R >> >> /Contents 7 0 R >>"],
    ["<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"],
    ["<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>"],
    [`<< /Type /XObject /Subtype /Image /Width 180 /Height 180 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${qrBytes.length} >>\nstream\n`, qrBytes, "\nendstream"],
    [`<< /Length ${contentBytes.length} >>\nstream\n`, contentBytes, "\nendstream"],
  ]);

  return new Blob([pdfBytes], { type: "application/pdf" });
}

function download(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export function ReceiptDownloadButton({ payload }: ReceiptDownloadButtonProps) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleDownload() {
    if (loading) return;
    setLoading(true);
    setError(null);
    try {
      const pdf = await createReceiptPdf(payload);
      download(pdf, `recu-${payload.receiptReference}.pdf`);
    } catch {
      setError("Impossible de générer le PDF. Réessayez ou utilisez l'impression navigateur.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <button
        className="button"
        disabled={loading}
        onClick={() => { void handleDownload(); }}
        type="button"
      >
        {loading ? "Génération en cours…" : "Télécharger PDF"}
      </button>
      {error ? <p style={{ color: "red", fontSize: "0.85em", marginTop: "0.5em" }}>{error}</p> : null}
    </div>
  );
}
