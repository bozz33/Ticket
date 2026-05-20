<?php

use App\Models\Offer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('ticket_reservations')) {
            Schema::connection('tenant')->create('ticket_reservations', function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('event_ticket_id')->constrained('event_tickets')->cascadeOnDelete();
                $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('platform_transaction_reference')->nullable()->index();
                $table->string('buyer_email')->nullable()->index();
                $table->unsignedSmallInteger('quantity')->default(1);
                $table->string('status', 40)->default('pending')->index();
                $table->timestamp('reserved_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('released_at')->nullable()->index();
                $table->timestamp('confirmed_at')->nullable()->index();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['event_ticket_id', 'status', 'expires_at'], 'ticket_reservations_ticket_status_expiry_idx');
            });
        }

        if (Schema::connection('tenant')->hasTable('orders')) {
            Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('orders', 'orderable_type')) {
                    $table->string('orderable_type')->nullable()->after('offer_id');
                }

                if (! Schema::connection('tenant')->hasColumn('orders', 'orderable_id')) {
                    $table->unsignedBigInteger('orderable_id')->nullable()->after('orderable_type');
                }
            });

            $this->ensureIndex('orders', 'orders_orderable_type_orderable_id_index', ['orderable_type', 'orderable_id']);
            $this->backfillMorph('orders', 'orderable');
        }

        if (Schema::connection('tenant')->hasTable('access_passes')) {
            Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
                if (! Schema::connection('tenant')->hasColumn('access_passes', 'passable_type')) {
                    $table->string('passable_type')->nullable()->after('offer_id');
                }

                if (! Schema::connection('tenant')->hasColumn('access_passes', 'passable_id')) {
                    $table->unsignedBigInteger('passable_id')->nullable()->after('passable_type');
                }
            });

            $this->ensureIndex('access_passes', 'access_passes_passable_type_passable_id_index', ['passable_type', 'passable_id']);
            $this->backfillMorph('access_passes', 'passable');
        }

        if (Schema::connection('tenant')->hasTable('event_tickets')) {
            $this->ensureIndex('event_tickets', 'event_tickets_offer_lookup_idx', ['offer_id']);
            $this->ensureIndex('event_tickets', 'event_tickets_category_active_idx', ['ticket_category_id', 'is_active']);
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('event_tickets', 'event_tickets_category_active_idx');
        $this->dropIndexIfExists('event_tickets', 'event_tickets_offer_lookup_idx');

        if (Schema::connection('tenant')->hasTable('access_passes')) {
            $this->dropIndexIfExists('access_passes', 'access_passes_passable_type_passable_id_index');

            Schema::connection('tenant')->table('access_passes', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('access_passes', 'passable_type')) {
                    $table->dropColumn('passable_type');
                }

                if (Schema::connection('tenant')->hasColumn('access_passes', 'passable_id')) {
                    $table->dropColumn('passable_id');
                }
            });
        }

        if (Schema::connection('tenant')->hasTable('orders')) {
            $this->dropIndexIfExists('orders', 'orders_orderable_type_orderable_id_index');

            Schema::connection('tenant')->table('orders', function (Blueprint $table): void {
                if (Schema::connection('tenant')->hasColumn('orders', 'orderable_type')) {
                    $table->dropColumn('orderable_type');
                }

                if (Schema::connection('tenant')->hasColumn('orders', 'orderable_id')) {
                    $table->dropColumn('orderable_id');
                }
            });
        }

        Schema::connection('tenant')->dropIfExists('ticket_reservations');
    }

    private function backfillMorph(string $table, string $prefix): void
    {
        if (! Schema::connection('tenant')->hasColumn($table, 'offer_id')) {
            return;
        }

        DB::connection('tenant')
            ->table($table)
            ->whereNotNull('offer_id')
            ->whereNull("{$prefix}_type")
            ->update([
                "{$prefix}_type" => Offer::class,
                "{$prefix}_id" => DB::raw('offer_id'),
            ]);
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if (! Schema::connection('tenant')->hasTable($table)) {
            return;
        }

        try {
            $existing = collect(Schema::connection('tenant')->getIndexes($table))
                ->pluck('name')
                ->contains($indexName);
        } catch (Throwable) {
            $existing = false;
        }

        if ($existing) {
            return;
        }

        Schema::connection('tenant')->table($table, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::connection('tenant')->hasTable($table)) {
            return;
        }

        try {
            $existing = collect(Schema::connection('tenant')->getIndexes($table))
                ->pluck('name')
                ->contains($indexName);
        } catch (Throwable) {
            $existing = false;
        }

        if (! $existing) {
            return;
        }

        Schema::connection('tenant')->table($table, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }
};
