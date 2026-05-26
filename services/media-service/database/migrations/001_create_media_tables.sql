CREATE TABLE IF NOT EXISTS media_assets (
    id UUID PRIMARY KEY,
    tenant_id VARCHAR(160),
    owner_type VARCHAR(120),
    owner_id VARCHAR(160),
    purpose VARCHAR(80) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    byte_size BIGINT NOT NULL DEFAULT 0,
    checksum_sha256 VARCHAR(64),
    storage_disk VARCHAR(80) NOT NULL DEFAULT 'local',
    storage_key VARCHAR(500) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS media_assets_tenant_idx ON media_assets(tenant_id);
CREATE INDEX IF NOT EXISTS media_assets_owner_idx ON media_assets(owner_type, owner_id);
CREATE INDEX IF NOT EXISTS media_assets_purpose_idx ON media_assets(purpose);
CREATE INDEX IF NOT EXISTS media_assets_status_idx ON media_assets(status);

CREATE TABLE IF NOT EXISTS media_document_jobs (
    id UUID PRIMARY KEY,
    tenant_id VARCHAR(160),
    template_key VARCHAR(160) NOT NULL,
    output_purpose VARCHAR(80) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'queued',
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    output_asset_id UUID REFERENCES media_assets(id),
    last_error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    completed_at TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS media_document_jobs_tenant_idx ON media_document_jobs(tenant_id);
CREATE INDEX IF NOT EXISTS media_document_jobs_status_idx ON media_document_jobs(status);

CREATE TABLE IF NOT EXISTS media_tenant_quota_usage (
    tenant_id VARCHAR(160) PRIMARY KEY,
    bytes_used BIGINT NOT NULL DEFAULT 0,
    monthly_bytes_limit BIGINT NOT NULL DEFAULT 1073741824,
    files_count BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
