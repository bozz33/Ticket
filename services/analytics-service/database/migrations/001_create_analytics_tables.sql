CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGSERIAL PRIMARY KEY,
    event_id VARCHAR(160) NOT NULL UNIQUE,
    event_type VARCHAR(180) NOT NULL,
    event_version INTEGER NOT NULL DEFAULT 1,
    tenant_id VARCHAR(160),
    module VARCHAR(80),
    aggregate_type VARCHAR(120),
    aggregate_id VARCHAR(160),
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS analytics_events_tenant_date_idx ON analytics_events(tenant_id, occurred_at);
CREATE INDEX IF NOT EXISTS analytics_events_type_date_idx ON analytics_events(event_type, occurred_at);
CREATE INDEX IF NOT EXISTS analytics_events_module_date_idx ON analytics_events(module, occurred_at);

CREATE TABLE IF NOT EXISTS analytics_daily_kpis (
    day DATE NOT NULL,
    tenant_id VARCHAR(160) NOT NULL DEFAULT '__platform__',
    module VARCHAR(80) NOT NULL DEFAULT '__all__',
    metric_key VARCHAR(120) NOT NULL,
    metric_value NUMERIC(18, 2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (day, tenant_id, module, metric_key)
);

CREATE TABLE IF NOT EXISTS analytics_export_jobs (
    id UUID PRIMARY KEY,
    tenant_id VARCHAR(160),
    export_type VARCHAR(80) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'queued',
    filters JSONB NOT NULL DEFAULT '{}'::jsonb,
    output_asset_id VARCHAR(160),
    last_error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    completed_at TIMESTAMPTZ
);
