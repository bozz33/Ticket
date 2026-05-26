import { randomUUID } from 'node:crypto';
import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { MEDIA_POOL } from '../../shared/database/database.constants';

type CreateDocumentJobInput = {
  tenantId: string | null;
  templateKey: string;
  outputPurpose: string;
  payload: Record<string, unknown>;
};

@Injectable()
export class DocumentJobsService {
  constructor(@Inject(MEDIA_POOL) private readonly pool: Pool) {}

  async create(input: CreateDocumentJobInput): Promise<Record<string, unknown>> {
    const id = randomUUID();
    const result = await this.pool.query(
      `
        INSERT INTO media_document_jobs(id, tenant_id, template_key, output_purpose, payload)
        VALUES ($1, $2, $3, $4, $5::jsonb)
        RETURNING id, tenant_id, template_key, output_purpose, status, created_at
      `,
      [id, input.tenantId, input.templateKey, input.outputPurpose, JSON.stringify(input.payload)],
    );

    return { job: result.rows[0] };
  }
}
