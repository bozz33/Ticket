CREATE TABLE IF NOT EXISTS checkin_passes (
    id BIGSERIAL PRIMARY KEY,
    tenant_id VARCHAR(160) NOT NULL,
    event_id VARCHAR(160) NOT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_starts_at TIMESTAMPTZ,
    event_location VARCHAR(255),
    pass_id VARCHAR(160) NOT NULL,
    pass_code VARCHAR(180) NOT NULL UNIQUE,
    receipt_reference VARCHAR(180),
    holder_name VARCHAR(255),
    holder_email VARCHAR(255),
    ticket_label VARCHAR(255),
    status VARCHAR(40) NOT NULL DEFAULT 'issued',
    used_at TIMESTAMPTZ,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS checkin_passes_tenant_event_idx ON checkin_passes(tenant_id, event_id);
CREATE INDEX IF NOT EXISTS checkin_passes_receipt_idx ON checkin_passes(receipt_reference);
CREATE INDEX IF NOT EXISTS checkin_passes_status_idx ON checkin_passes(status);

CREATE TABLE IF NOT EXISTS checkin_agents (
    id UUID PRIMARY KEY,
    tenant_id VARCHAR(160) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS checkin_scans (
    id BIGSERIAL PRIMARY KEY,
    tenant_id VARCHAR(160) NOT NULL,
    event_id VARCHAR(160),
    pass_code VARCHAR(180) NOT NULL,
    agent_id VARCHAR(160),
    entry_point VARCHAR(160),
    device_id VARCHAR(160),
    status VARCHAR(40) NOT NULL,
    reason VARCHAR(180),
    offline_batch_id VARCHAR(160),
    scanned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);

CREATE INDEX IF NOT EXISTS checkin_scans_tenant_event_idx ON checkin_scans(tenant_id, event_id, scanned_at);
CREATE INDEX IF NOT EXISTS checkin_scans_pass_idx ON checkin_scans(pass_code, scanned_at);
CREATE UNIQUE INDEX IF NOT EXISTS checkin_scans_one_accept_idx ON checkin_scans(pass_code) WHERE status = 'accepted';

CREATE TABLE IF NOT EXISTS checkin_offline_batches (
    id UUID PRIMARY KEY,
    tenant_id VARCHAR(160) NOT NULL,
    agent_id VARCHAR(160),
    device_id VARCHAR(160),
    status VARCHAR(40) NOT NULL DEFAULT 'queued',
    payload JSONB NOT NULL DEFAULT '[]'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    processed_at TIMESTAMPTZ
);
