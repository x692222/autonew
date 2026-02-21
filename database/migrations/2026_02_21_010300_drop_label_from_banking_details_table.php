<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);

        return ! empty($rows);
    }

    public function up(): void
    {
        if (Schema::hasColumn('banking_details', 'label') && Schema::hasColumn('banking_details', 'institution')) {
            DB::table('banking_details')
                ->where(function ($query) {
                    $query->whereNull('institution')->orWhere('institution', '');
                })
                ->update([
                    'institution' => DB::raw('label'),
                ]);
        }

        Schema::table('banking_details', function (Blueprint $table) {
            if (Schema::hasColumn('banking_details', 'label')) {
                if ($this->indexExists('banking_details', 'banking_details_dealer_id_label_index')) {
                    $table->dropIndex('banking_details_dealer_id_label_index');
                }

                $table->dropColumn('label');
            }

            if (
                Schema::hasColumn('banking_details', 'institution')
                && ! $this->indexExists('banking_details', 'banking_details_dealer_id_institution_index')
            ) {
                $table->index(['dealer_id', 'institution'], 'banking_details_dealer_id_institution_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_details', function (Blueprint $table) {
            if (! Schema::hasColumn('banking_details', 'label')) {
                $table->string('label', 100)->nullable()->after('dealer_id');
            }
        });

        if (Schema::hasColumn('banking_details', 'institution') && Schema::hasColumn('banking_details', 'label')) {
            DB::table('banking_details')->update([
                'label' => DB::raw('institution'),
            ]);
        }

        Schema::table('banking_details', function (Blueprint $table) {
            if ($this->indexExists('banking_details', 'banking_details_dealer_id_institution_index')) {
                $table->dropIndex('banking_details_dealer_id_institution_index');
            }

            if (
                Schema::hasColumn('banking_details', 'label')
                && ! $this->indexExists('banking_details', 'banking_details_dealer_id_label_index')
            ) {
                $table->index(['dealer_id', 'label'], 'banking_details_dealer_id_label_index');
            }
        });
    }
};
