CREATE TABLE IF NOT EXISTS catalog_items (
    id BIGSERIAL PRIMARY KEY,
    tenant_id VARCHAR(160) NOT NULL,
    tenant_public_id VARCHAR(160) NOT NULL,
    tenant_slug VARCHAR(160) NOT NULL,
    tenant_name VARCHAR(255) NOT NULL,
    module VARCHAR(80) NOT NULL,
    item_public_id VARCHAR(160) NOT NULL,
    item_slug VARCHAR(180) NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    category VARCHAR(160),
    city VARCHAR(160),
    country_code VARCHAR(12),
    currency_code VARCHAR(12),
    price_from BIGINT NOT NULL DEFAULT 0,
    is_free BOOLEAN NOT NULL DEFAULT TRUE,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    likes_count INTEGER NOT NULL DEFAULT 0,
    weekly_likes_count INTEGER NOT NULL DEFAULT 0,
    popularity_score INTEGER NOT NULL DEFAULT 0,
    published_at TIMESTAMPTZ,
    starts_at TIMESTAMPTZ,
    ends_at TIMESTAMPTZ,
    search_text TEXT NOT NULL DEFAULT '',
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (tenant_id, module, item_public_id)
);

CREATE INDEX IF NOT EXISTS catalog_items_module_published_idx ON catalog_items(module, published_at);
CREATE INDEX IF NOT EXISTS catalog_items_filter_idx ON catalog_items(module, category, city);
CREATE INDEX IF NOT EXISTS catalog_items_tenant_lookup_idx ON catalog_items(tenant_slug, module, item_slug);
CREATE INDEX IF NOT EXISTS catalog_items_weekly_likes_idx ON catalog_items(module, weekly_likes_count, likes_count, published_at);
CREATE INDEX IF NOT EXISTS catalog_items_search_idx ON catalog_items USING GIN (to_tsvector('simple', search_text));

CREATE TABLE IF NOT EXISTS catalog_front_pages (
    id BIGSERIAL PRIMARY KEY,
    path VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'published',
    locale VARCHAR(12) NOT NULL DEFAULT 'fr',
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    seo JSONB NOT NULL DEFAULT '{}'::jsonb,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS catalog_front_menus (
    id BIGSERIAL PRIMARY KEY,
    location VARCHAR(80) NOT NULL,
    locale VARCHAR(12) NOT NULL DEFAULT 'fr',
    items JSONB NOT NULL DEFAULT '[]'::jsonb,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (location, locale)
);
