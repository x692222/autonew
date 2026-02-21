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
        Schema::table('banking_details', function (Blueprint $table) {
            if (! Schema::hasColumn('banking_details', 'bank')) {
                $table->string('bank', 50)->default('')->after('dealer_id');
            }
            if (! Schema::hasColumn('banking_details', 'account_holder')) {
                $table->string('account_holder', 75)->default('')->after('bank');
            }
            if (! Schema::hasColumn('banking_details', 'account_number')) {
                $table->string('account_number', 5)->default('')->after('account_holder');
            }
            if (! Schema::hasColumn('banking_details', 'branch_name')) {
                $table->string('branch_name', 50)->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('banking_details', 'branch_code')) {
                $table->string('branch_code', 50)->nullable()->after('branch_name');
            }
            if (! Schema::hasColumn('banking_details', 'swift_code')) {
                $table->string('swift_code', 20)->nullable()->after('branch_code');
            }
            if (! Schema::hasColumn('banking_details', 'other_details')) {
                $table->text('other_details')->nullable()->after('swift_code');
            }
        });

        if (Schema::hasColumn('banking_details', 'institution') && Schema::hasColumn('banking_details', 'bank')) {
            DB::table('banking_details')
                ->where(function ($query) {
                    $query->whereNull('bank')->orWhere('bank', '');
                })
                ->update(['bank' => DB::raw('institution')]);
        }

        if (Schema::hasColumn('banking_details', 'details') && Schema::hasColumn('banking_details', 'other_details')) {
            DB::table('banking_details')
                ->where(function ($query) {
                    $query->whereNull('other_details')->orWhere('other_details', '');
                })
                ->update(['other_details' => DB::raw('details')]);
        }

        Schema::table('banking_details', function (Blueprint $table) {
            if ($this->indexExists('banking_details', 'banking_details_dealer_id_institution_index')) {
                $table->dropIndex('banking_details_dealer_id_institution_index');
            }

            if (Schema::hasColumn('banking_details', 'institution')) {
                $table->dropColumn('institution');
            }
            if (Schema::hasColumn('banking_details', 'details')) {
                $table->dropColumn('details');
            }

            if (! $this->indexExists('banking_details', 'banking_details_dealer_id_bank_index')) {
                $table->index(['dealer_id', 'bank'], 'banking_details_dealer_id_bank_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_details', function (Blueprint $table) {
            if (! Schema::hasColumn('banking_details', 'institution')) {
                $table->string('institution', 100)->default('')->after('dealer_id');
            }
            if (! Schema::hasColumn('banking_details', 'details')) {
                $table->text('details')->nullable()->after('institution');
            }
        });

        if (Schema::hasColumn('banking_details', 'bank') && Schema::hasColumn('banking_details', 'institution')) {
            DB::table('banking_details')
                ->where(function ($query) {
                    $query->whereNull('institution')->orWhere('institution', '');
                })
                ->update(['institution' => DB::raw('bank')]);
        }

        if (Schema::hasColumn('banking_details', 'other_details') && Schema::hasColumn('banking_details', 'details')) {
            DB::table('banking_details')
                ->where(function ($query) {
                    $query->whereNull('details')->orWhere('details', '');
                })
                ->update(['details' => DB::raw('other_details')]);
        }

        Schema::table('banking_details', function (Blueprint $table) {
            if ($this->indexExists('banking_details', 'banking_details_dealer_id_bank_index')) {
                $table->dropIndex('banking_details_dealer_id_bank_index');
            }

            if (Schema::hasColumn('banking_details', 'bank')) {
                $table->dropColumn('bank');
            }
            if (Schema::hasColumn('banking_details', 'account_holder')) {
                $table->dropColumn('account_holder');
            }
            if (Schema::hasColumn('banking_details', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('banking_details', 'branch_name')) {
                $table->dropColumn('branch_name');
            }
            if (Schema::hasColumn('banking_details', 'branch_code')) {
                $table->dropColumn('branch_code');
            }
            if (Schema::hasColumn('banking_details', 'swift_code')) {
                $table->dropColumn('swift_code');
            }
            if (Schema::hasColumn('banking_details', 'other_details')) {
                $table->dropColumn('other_details');
            }

            if (! $this->indexExists('banking_details', 'banking_details_dealer_id_institution_index')) {
                $table->index(['dealer_id', 'institution'], 'banking_details_dealer_id_institution_index');
            }
        });
    }
};

