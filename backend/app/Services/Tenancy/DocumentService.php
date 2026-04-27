<?php

namespace App\Services\Tenancy;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(protected TenantPublicProfileService $tenantPublicProfileService)
    {
    }

    public function list(?string $visibility = null, ?string $resourceTypeCode = null): Collection
    {
        return Document::query()
            ->with('organizationProfile')
            ->when($visibility !== null && $visibility !== '', fn ($query) => $query->where('visibility', $visibility))
            ->when($resourceTypeCode !== null && $resourceTypeCode !== '', fn ($query) => $query->where('resource_type_code', $resourceTypeCode))
            ->latest()
            ->get();
    }

    public function listPublic(?string $resourceTypeCode = null): Collection
    {
        return Document::query()
            ->with('organizationProfile')
            ->where('visibility', 'public')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->when($resourceTypeCode !== null && $resourceTypeCode !== '', fn ($query) => $query->where('resource_type_code', $resourceTypeCode))
            ->latest()
            ->get();
    }

    public function create(array $payload): Document
    {
        $organizationProfileId = $payload['organization_profile_id'] ?? $this->tenantPublicProfileService->getOrCreate()->id;

        return Document::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $organizationProfileId,
            'resource_type_code' => $payload['resource_type_code'] ?? null,
            'title' => $payload['title'],
            'slug' => $payload['slug'] ?? Str::slug($payload['title']),
            'description' => $payload['description'] ?? null,
            'disk' => $payload['disk'] ?? 'public',
            'path' => $payload['path'],
            'mime_type' => $payload['mime_type'] ?? null,
            'extension' => $payload['extension'] ?? null,
            'size_bytes' => $payload['size_bytes'] ?? 0,
            'visibility' => $payload['visibility'] ?? 'private',
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'published_at' => $payload['published_at'] ?? null,
            'meta' => $payload['meta'] ?? [],
        ])->load('organizationProfile');
    }

    public function findByIdentifier(string $identifier): ?Document
    {
        return Document::query()
            ->with('organizationProfile')
            ->where('slug', $identifier)
            ->orWhere('public_id', $identifier)
            ->first();
    }

    public function findPublicByIdentifier(string $identifier): ?Document
    {
        return Document::query()
            ->with('organizationProfile')
            ->where('visibility', 'public')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($query) use ($identifier) {
                $query->where('slug', $identifier)->orWhere('public_id', $identifier);
            })
            ->first();
    }
}
