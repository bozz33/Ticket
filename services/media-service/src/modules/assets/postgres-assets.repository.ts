import { randomUUID } from 'node:crypto';
import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { MEDIA_POOL } from '../../shared/database/database.constants';
import { CreateMediaAssetInput, MediaAsset } from './asset.types';
import { AssetsRepository } from './assets.repository';

type MediaAssetRow = {
  id: string;
  tenant_id: string | null;
  owner_type: string | null;
  owner_id: string | null;
  purpose: string;
  original_name: string;
  mime_type: string;
  byte_size: string;
  checksum_sha256: string | null;
  storage_disk: string;
  storage_key: string;
  status: string;
  metadata: Record<string, unknown>;
};

@Injectable()
export class PostgresAssetsRepository implements AssetsRepository {
  constructor(@Inject(MEDIA_POOL) private readonly pool: Pool) {}

  async create(input: CreateMediaAssetInput): Promise<MediaAsset> {
    const id = randomUUID();
    const result = await this.pool.query<MediaAssetRow>(
      `
        INSERT INTO media_assets(
          id, tenant_id, owner_type, owner_id, purpose, original_name, mime_type,
          byte_size, checksum_sha256, storage_disk, storage_key, metadata
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12::jsonb)
        RETURNING *
      `,
      [
        id,
        input.tenantId,
        input.ownerType ?? null,
        input.ownerId ?? null,
        input.purpose,
        input.originalName,
        input.mimeType,
        input.byteSize,
        input.checksumSha256 ?? null,
        input.storageDisk,
        input.storageKey,
        JSON.stringify(input.metadata ?? {}),
      ],
    );

    return mapRow(result.rows[0]);
  }

  async findById(id: string): Promise<MediaAsset | null> {
    const result = await this.pool.query<MediaAssetRow>('SELECT * FROM media_assets WHERE id = $1', [id]);
    return result.rows[0] ? mapRow(result.rows[0]) : null;
  }

  async markUploaded(id: string, checksumSha256?: string | null): Promise<void> {
    await this.pool.query(
      `
        UPDATE media_assets
        SET status = 'uploaded',
            checksum_sha256 = COALESCE($2, checksum_sha256),
            updated_at = NOW()
        WHERE id = $1
      `,
      [id, checksumSha256 ?? null],
    );
  }
}

function mapRow(row: MediaAssetRow): MediaAsset {
  return {
    id: row.id,
    tenantId: row.tenant_id,
    ownerType: row.owner_type,
    ownerId: row.owner_id,
    purpose: row.purpose,
    originalName: row.original_name,
    mimeType: row.mime_type,
    byteSize: Number(row.byte_size),
    checksumSha256: row.checksum_sha256,
    storageDisk: row.storage_disk,
    storageKey: row.storage_key,
    status: row.status,
    metadata: row.metadata ?? {},
  };
}
