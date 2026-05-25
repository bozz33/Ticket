<?php

namespace Tests\Unit;

use App\Models\PublicCatalogItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Ticket\PublicCatalog\Application\PublicCatalogProjectionReader;
use Ticket\PublicCatalog\Domain\PublicCatalogModules;

class PublicCatalogProjectionReaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.central.driver', 'sqlite');
        config()->set('database.connections.central.database', ':memory:');

        DB::purge('central');

        $this->createProjectionTable();
        $this->seedProjection();
    }

    public function test_projection_reader_filters_and_paginates_public_catalog_items(): void
    {
        $result = $this->reader()->list([
            'module' => 'evenements',
            'city' => 'Abidjan',
            'price' => 'paid',
            'sort' => 'recent',
        ], 1, 10);

        $this->assertSame(1, $result['total']);
        $this->assertSame('Jazz Night', $result['items'][0]['title']);
        $this->assertSame('tenant-a', $result['items'][0]['organizerSlug']);
    }

    public function test_projection_reader_resolves_filters_search_and_detail_lookup(): void
    {
        $filters = $this->reader()->availableFilters('evenements');
        $suggestions = $this->reader()->searchSuggestions('jazz', 'evenements', 5);
        $item = $this->reader()->find('evenements', 'jazz-night', 'tenant-a');

        $this->assertSame(['Culture'], $filters['categories']);
        $this->assertSame(['Abidjan'], $filters['cities']);
        $this->assertSame('Jazz Night', $suggestions[0]['title']);
        $this->assertSame('Jazz Night', $item['title']);
    }

    public function test_projection_reader_excludes_ended_items_unless_past_items_are_requested(): void
    {
        PublicCatalogItem::query()->create($this->projectionRow([
            'tenant_id' => 1,
            'tenant_public_id' => 'tenant-public-a',
            'tenant_slug' => 'tenant-a',
            'tenant_name' => 'Tenant A',
            'module' => 'evenements',
            'item_public_id' => 'evt-ended',
            'item_slug' => 'past-concert',
            'title' => 'Past Concert',
            'summary' => 'Concert terminé',
            'category' => 'Culture',
            'city' => 'Abidjan',
            'price_from' => 5000,
            'is_free' => false,
            'published_at' => now()->subDays(10),
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
            'search_text' => 'tenant a past concert culture abidjan',
        ]));

        $current = $this->reader()->list(['module' => 'evenements'], 1, 10);
        $withPast = $this->reader()->list(['module' => 'evenements', 'include_past' => 'true'], 1, 10);

        $this->assertSame(['Jazz Night'], collect($current['items'])->pluck('title')->all());
        $this->assertContains('Past Concert', collect($withPast['items'])->pluck('title')->all());
    }

    public function test_projection_reader_sorts_weekly_likes_from_engagement_columns(): void
    {
        $result = $this->reader()->list(['sort' => 'weekly_likes'], 1, 10);

        $this->assertSame('Jazz Night', $result['items'][0]['title']);
    }

    private function reader(): PublicCatalogProjectionReader
    {
        return new PublicCatalogProjectionReader(new PublicCatalogModules);
    }

    private function createProjectionTable(): void
    {
        Schema::connection('central')->create('public_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('tenant_public_id');
            $table->string('tenant_slug');
            $table->string('tenant_name');
            $table->string('module');
            $table->string('item_public_id');
            $table->string('item_slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('category')->nullable();
            $table->string('city')->nullable();
            $table->string('country_code')->nullable();
            $table->string('currency_code', 8)->nullable();
            $table->unsignedBigInteger('price_from')->default(0);
            $table->boolean('is_free')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('weekly_likes_count')->default(0);
            $table->unsignedInteger('popularity_score')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->longText('search_text');
            $table->json('payload');
            $table->timestamps();
        });
    }

    private function seedProjection(): void
    {
        PublicCatalogItem::query()->create($this->projectionRow([
            'tenant_id' => 1,
            'tenant_public_id' => 'tenant-public-a',
            'tenant_slug' => 'tenant-a',
            'tenant_name' => 'Tenant A',
            'module' => 'evenements',
            'item_public_id' => 'evt-1',
            'item_slug' => 'jazz-night',
            'title' => 'Jazz Night',
            'summary' => 'Concert jazz',
            'category' => 'Culture',
            'city' => 'Abidjan',
            'price_from' => 10000,
            'is_free' => false,
            'likes_count' => 2,
            'weekly_likes_count' => 2,
            'published_at' => now(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'search_text' => 'tenant a jazz night concert jazz culture abidjan',
        ]));

        PublicCatalogItem::query()->create($this->projectionRow([
            'tenant_id' => 2,
            'tenant_public_id' => 'tenant-public-b',
            'tenant_slug' => 'tenant-b',
            'tenant_name' => 'Tenant B',
            'module' => 'formations',
            'item_public_id' => 'training-1',
            'item_slug' => 'laravel-pro',
            'title' => 'Laravel Pro',
            'summary' => 'Formation Laravel',
            'category' => 'Tech',
            'city' => 'Dakar',
            'price_from' => 0,
            'is_free' => true,
            'likes_count' => 5,
            'weekly_likes_count' => 0,
            'published_at' => now()->subDay(),
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(7),
            'search_text' => 'tenant b laravel pro formation tech dakar',
        ]));
    }

    private function projectionRow(array $overrides): array
    {
        $payload = [
            'id' => $overrides['item_public_id'],
            'module' => $overrides['module'],
            'slug' => $overrides['item_slug'],
            'title' => $overrides['title'],
            'summary' => $overrides['summary'],
            'category' => $overrides['category'],
            'city' => $overrides['city'],
            'country' => 'CI',
            'priceFrom' => $overrides['price_from'],
            'isFree' => $overrides['is_free'],
            'likesCount' => $overrides['likes_count'] ?? 0,
            'weeklyLikesCount' => $overrides['weekly_likes_count'] ?? 0,
            'publishedAt' => $overrides['published_at']->toIso8601String(),
            'startsAt' => isset($overrides['starts_at']) ? $overrides['starts_at']?->toIso8601String() : null,
            'endsAt' => isset($overrides['ends_at']) ? $overrides['ends_at']?->toIso8601String() : null,
            'organizerSlug' => $overrides['tenant_slug'],
            'speakers' => [],
            'tiers' => [],
        ];

        return array_merge([
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'is_featured' => false,
            'popularity_score' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'payload' => $payload,
        ], $overrides);
    }
}
