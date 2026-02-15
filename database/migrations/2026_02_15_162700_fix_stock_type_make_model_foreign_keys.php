<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeMakeAndModelReferences();

        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_make_id_foreign')) {
                $table->dropForeign('stock_type_vehicles_make_id_foreign');
            }
            if ($this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_model_id_foreign')) {
                $table->dropForeign('stock_type_vehicles_model_id_foreign');
            }
        });
        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock_makes')->restrictOnDelete();
            }
            if (! $this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_model_id_foreign')) {
                $table->foreign('model_id')->references('id')->on('stock_models')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_commercial', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_commercial', 'stock_type_commercial_make_id_foreign')) {
                $table->dropForeign('stock_type_commercial_make_id_foreign');
            }
        });
        Schema::table('stock_type_commercial', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_commercial', 'stock_type_commercial_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock_makes')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_motorbikes', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_motorbikes', 'stock_type_motorbikes_make_id_foreign')) {
                $table->dropForeign('stock_type_motorbikes_make_id_foreign');
            }
        });
        Schema::table('stock_type_motorbikes', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_motorbikes', 'stock_type_motorbikes_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock_makes')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_leisure', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_leisure', 'stock_type_leisure_make_id_foreign')) {
                $table->dropForeign('stock_type_leisure_make_id_foreign');
            }
        });
        Schema::table('stock_type_leisure', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_leisure', 'stock_type_leisure_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock_makes')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_make_id_foreign')) {
                $table->dropForeign('stock_type_vehicles_make_id_foreign');
            }
            if ($this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_model_id_foreign')) {
                $table->dropForeign('stock_type_vehicles_model_id_foreign');
            }
        });
        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock')->restrictOnDelete();
            }
            if (! $this->foreignExists('stock_type_vehicles', 'stock_type_vehicles_model_id_foreign')) {
                $table->foreign('model_id')->references('id')->on('stock')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_commercial', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_commercial', 'stock_type_commercial_make_id_foreign')) {
                $table->dropForeign('stock_type_commercial_make_id_foreign');
            }
        });
        Schema::table('stock_type_commercial', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_commercial', 'stock_type_commercial_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_motorbikes', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_motorbikes', 'stock_type_motorbikes_make_id_foreign')) {
                $table->dropForeign('stock_type_motorbikes_make_id_foreign');
            }
        });
        Schema::table('stock_type_motorbikes', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_motorbikes', 'stock_type_motorbikes_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock')->restrictOnDelete();
            }
        });

        Schema::table('stock_type_leisure', function (Blueprint $table) {
            if ($this->foreignExists('stock_type_leisure', 'stock_type_leisure_make_id_foreign')) {
                $table->dropForeign('stock_type_leisure_make_id_foreign');
            }
        });
        Schema::table('stock_type_leisure', function (Blueprint $table) {
            if (! $this->foreignExists('stock_type_leisure', 'stock_type_leisure_make_id_foreign')) {
                $table->foreign('make_id')->references('id')->on('stock')->restrictOnDelete();
            }
        });
    }

    private function foreignExists(string $table, string $constraintName): bool
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = "FOREIGN KEY"
               AND CONSTRAINT_NAME = ?
             LIMIT 1',
            [$table, $constraintName]
        );

        return $row !== null;
    }

    private function normalizeMakeAndModelReferences(): void
    {
        $fallbackMakeByType = [
            'vehicle' => DB::table('stock_makes')->where('stock_type', 'vehicle')->orderBy('name')->value('id'),
            'commercial' => DB::table('stock_makes')->where('stock_type', 'commercial')->orderBy('name')->value('id'),
            'motorbike' => DB::table('stock_makes')->where('stock_type', 'motorbike')->orderBy('name')->value('id'),
            'leisure' => DB::table('stock_makes')->where('stock_type', 'leisure')->orderBy('name')->value('id'),
        ];

        foreach ($fallbackMakeByType as $type => $fallbackMakeId) {
            if (empty($fallbackMakeId)) {
                throw new RuntimeException("Missing stock make fallback for type [{$type}]");
            }
        }

        $tablesByType = [
            'stock_type_commercial' => 'commercial',
            'stock_type_motorbikes' => 'motorbike',
            'stock_type_leisure' => 'leisure',
        ];

        foreach ($tablesByType as $table => $type) {
            DB::table($table)
                ->whereNotExists(function ($q) use ($table) {
                    $q->select(DB::raw(1))
                        ->from('stock_makes')
                        ->whereColumn("{$table}.make_id", 'stock_makes.id');
                })
                ->update(['make_id' => (string) $fallbackMakeByType[$type]]);
        }

        $vehicleFallbackMakeId = (string) $fallbackMakeByType['vehicle'];
        $globalFallbackModelId = DB::table('stock_models')->orderBy('name')->value('id');
        if (empty($globalFallbackModelId)) {
            throw new RuntimeException('Missing stock model fallback for vehicles');
        }

        DB::table('stock_type_vehicles')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('stock_makes')
                    ->whereColumn('stock_type_vehicles.make_id', 'stock_makes.id');
            })
            ->update(['make_id' => $vehicleFallbackMakeId]);

        $vehicles = DB::table('stock_type_vehicles')->select(['id', 'make_id', 'model_id'])->get();
        foreach ($vehicles as $vehicle) {
            $modelValid = DB::table('stock_models')
                ->where('id', (string) $vehicle->model_id)
                ->where('make_id', (string) $vehicle->make_id)
                ->exists();

            if ($modelValid) {
                continue;
            }

            $modelIdForMake = DB::table('stock_models')
                ->where('make_id', (string) $vehicle->make_id)
                ->orderBy('name')
                ->value('id');

            DB::table('stock_type_vehicles')
                ->where('id', (string) $vehicle->id)
                ->update(['model_id' => (string) ($modelIdForMake ?: $globalFallbackModelId)]);
        }
    }
};
