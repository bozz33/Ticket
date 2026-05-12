"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import type { AccessPassStatus, AccessPassType, AccountAccessPass } from "@/lib/types";

import { AccountTablePagination } from "./AccountTablePagination";

const PAGE_SIZE = 10;

const STATUS_LABELS: Record<AccessPassStatus, string> = {
  active: "Actif",
  used: "Utilise",
  revoked: "Revoque",
  expired: "Expire",
};

const TYPE_LABELS: Record<AccessPassType, string> = {
  event_ticket: "Billet evenement",
  training_enrollment: "Inscription formation",
  stand_reservation: "Reservation stand",
  purchase_pass: "Pass achat",
};

function formatDate(iso: string | null) {
  if (!iso) {
    return "—";
  }

  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

export function PassesTable({ passes }: { passes: AccountAccessPass[] }) {
  const [page, setPage] = useState(1);

  const rows = useMemo(() => {
    const start = (page - 1) * PAGE_SIZE;

    return passes.slice(start, start + PAGE_SIZE);
  }, [page, passes]);

  return (
    <div className="ac-table-block">
      <div className="ac-table-shell">
        <table className="ac-table">
          <thead>
            <tr>
              <th>Pass</th>
              <th>Offre</th>
              <th>Telephone</th>
              <th>Statut</th>
              <th>Expiration</th>
              <th>Commande</th>
              <th aria-label="Action" />
            </tr>
          </thead>
          <tbody>
            {rows.map((pass) => (
              <tr key={pass.id}>
                <td>
                  <div className="ac-table__primary">{TYPE_LABELS[pass.type] ?? pass.type}</div>
                  <div className="ac-table__secondary">{pass.public_id}</div>
                </td>
                <td>
                  <div className="ac-table__primary">{pass.offer?.name ?? "Pass"}</div>
                  <div className="ac-table__secondary">{pass.holder_name ?? "Porteur"}</div>
                </td>
                <td>{pass.order?.buyer_phone ?? "—"}</td>
                <td>
                  <span className={`ac-badge ac-badge--${pass.status}`}>
                    <span className="ac-badge__dot" />
                    {STATUS_LABELS[pass.status] ?? pass.status}
                  </span>
                </td>
                <td>{formatDate(pass.expires_at)}</td>
                <td>{pass.order?.reference ?? "—"}</td>
                <td className="ac-table__action-cell">
                  <Link className="ac-table__action" href={`/compte/passes/${pass.public_id}`}>
                    Voir
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="ac-table-mobile">
        {rows.map((pass) => (
          <Link className="ac-table-card" href={`/compte/passes/${pass.public_id}`} key={pass.id}>
            <div className="ac-table-card__top">
              <div>
                <p className="ac-table-card__title">{pass.offer?.name ?? TYPE_LABELS[pass.type]}</p>
                <p className="ac-table-card__sub">{TYPE_LABELS[pass.type] ?? pass.type}</p>
              </div>
              <span className={`ac-badge ac-badge--${pass.status}`}>
                <span className="ac-badge__dot" />
                {STATUS_LABELS[pass.status] ?? pass.status}
              </span>
            </div>
            <dl className="ac-table-card__grid">
              <div>
                <dt>Telephone</dt>
                <dd>{pass.order?.buyer_phone ?? "—"}</dd>
              </div>
              <div>
                <dt>Expiration</dt>
                <dd>{formatDate(pass.expires_at)}</dd>
              </div>
              <div>
                <dt>Commande</dt>
                <dd>{pass.order?.reference ?? "—"}</dd>
              </div>
              <div>
                <dt>ID</dt>
                <dd>{pass.public_id}</dd>
              </div>
            </dl>
          </Link>
        ))}
      </div>

      <AccountTablePagination
        onPageChange={setPage}
        page={page}
        pageSize={PAGE_SIZE}
        total={passes.length}
      />
    </div>
  );
}
