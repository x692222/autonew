<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SYSTEM_SCOPE_KEY = '__system__';

    public function up(): void
    {
        Schema::create('stored_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->string('scope_key', 36);
            $table->string('section', 50)->nullable();
            $table->string('sku', 64);
            $table->string('description')->default('');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['scope_key', 'sku']);
            $table->index(['dealer_id', 'sku']);
            $table->index(['dealer_id', 'section', 'sku']);
            $table->index(['dealer_id', 'section', 'created_at']);
        });

        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->foreignUuid('stored_line_item_id')
                ->nullable()
                ->after('stock_id')
                ->constrained('stored_line_items')
                ->nullOnDelete();
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->foreignUuid('stored_line_item_id')
                ->nullable()
                ->after('stock_id')
                ->constrained('stored_line_items')
                ->nullOnDelete();
        });

        $this->backfillStoredLineItems();
        $this->backfillStoredLineItemLinks('quotation_line_items');
        $this->backfillStoredLineItemLinks('invoice_line_items');
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stored_line_item_id');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stored_line_item_id');
        });

        Schema::dropIfExists('stored_line_items');
    }

    private function backfillStoredLineItems(): void
    {
        $this->syncStoredItemsFromTable('quotation_line_items');
        $this->syncStoredItemsFromTable('invoice_line_items');
    }

    private function syncStoredItemsFromTable(string $tableName): void
    {
        DB::table($tableName)
            ->select(['id', 'dealer_id', 'section', 'sku', 'description', 'amount', 'created_at'])
            ->whereNotNull('sku')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    $sku = trim((string) $row->sku);
                    if ($sku === '') {
                        continue;
                    }

                    $dealerId = $row->dealer_id ?: null;
                    $scopeKey = $this->scopeKeyFor($dealerId);

                    $existingId = DB::table('stored_line_items')
                        ->where('scope_key', $scopeKey)
                        ->where('sku', $sku)
                        ->value('id');

                    $payload = [
                        'dealer_id' => $dealerId,
                        'scope_key' => $scopeKey,
                        'section' => (string) $row->section,
                        'sku' => $sku,
                        'description' => (string) ($row->description ?? ''),
                        'amount' => round((float) ($row->amount ?? 0), 2),
                        'updated_at' => $row->created_at ?? now(),
                    ];

                    if ($existingId) {
                        DB::table('stored_line_items')
                            ->where('id', $existingId)
                            ->update($payload);

                        continue;
                    }

                    DB::table('stored_line_items')->insert([
                        'id' => (string) Str::uuid(),
                        ...$payload,
                        'created_at' => $row->created_at ?? now(),
                    ]);
                }
            });
    }

    private function backfillStoredLineItemLinks(string $tableName): void
    {
        DB::table($tableName)
            ->select(['id', 'dealer_id', 'sku'])
            ->whereNotNull('sku')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($tableName): void {
                foreach ($rows as $row) {
                    $sku = trim((string) $row->sku);
                    if ($sku === '') {
                        continue;
                    }

                    $storedLineItemId = DB::table('stored_line_items')
                        ->where('scope_key', $this->scopeKeyFor($row->dealer_id ?: null))
                        ->where('sku', $sku)
                        ->value('id');

                    if (! $storedLineItemId) {
                        continue;
                    }

                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update(['stored_line_item_id' => $storedLineItemId]);
                }
            });
    }

    private function scopeKeyFor(?string $dealerId): string
    {
        return $dealerId ?: self::SYSTEM_SCOPE_KEY;
    }
};
