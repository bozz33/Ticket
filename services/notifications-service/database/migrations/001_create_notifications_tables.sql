CREATE TABLE IF NOT EXISTS notification_event_deliveries (
    id BIGSERIAL PRIMARY KEY,
    event_id UUID NOT NULL UNIQUE,
    event_type VARCHAR(160) NOT NULL,
    tenant_id VARCHAR(160),
    status VARCHAR(40) NOT NULL DEFAULT 'processing',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT,
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    processed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS notification_messages (
    id BIGSERIAL PRIMARY KEY,
    event_id UUID NOT NULL,
    tenant_id VARCHAR(160),
    recipient VARCHAR(255) NOT NULL,
    channel VARCHAR(40) NOT NULL,
    template_key VARCHAR(160) NOT NULL,
    subject VARCHAR(255),
    body TEXT NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'queued',
    provider_reference VARCHAR(255),
    last_error TEXT,
    sent_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS notification_messages_event_id_idx ON notification_messages(event_id);
CREATE INDEX IF NOT EXISTS notification_messages_recipient_idx ON notification_messages(recipient);
CREATE INDEX IF NOT EXISTS notification_event_deliveries_tenant_idx ON notification_event_deliveries(tenant_id);
