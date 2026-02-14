<?php

namespace App\Traits;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

trait BelongsToDealerGuardControllerTrait
{
    /**
     * Determine if the given model belongs to the given dealer, either:
     * - via branch_id -> branch() -> dealer_id
     * - or via dealer_id directly on the model
     *
     * Minimal DB calls:
     * - 0 calls if needed relations are already loaded OR dealer_id exists on model
     * - 1 call max to fetch branch.dealer_id if branch not loaded
     */
    public function belongsToDealer(Dealer $dealer, Model $subject): bool
    {
        $dealerId = $dealer->getKey();

        if ($this->tableHasColumnCached($subject, 'branch_id') && method_exists($subject, 'branch')) {
            $branchId = $subject->getAttribute('branch_id');

            if ($branchId) {
                $relation = $subject->branch();

                if ($relation instanceof BelongsTo) {
                    // If already loaded, no DB call
                    if ($subject->relationLoaded('branch') && $subject->getRelation('branch')) {
                        $branch = $subject->getRelation('branch');

                        // If branch model doesn't even have dealer_id column, fail
                        if (!$this->tableHasColumnCached($branch, 'dealer_id')) {
                            return false;
                        }

                        return (string) $branch->getAttribute('dealer_id') === (string) $dealerId;
                    }

                    // Not loaded -> do ONE small query: fetch dealer_id from branch table
                    $related = $relation->getRelated();

                    if (!$this->tableHasColumnCached($related, 'dealer_id')) {
                        return false;
                    }

                    $ownerKeyName = $relation->getOwnerKeyName(); // usually 'id'

                    $branchDealerId = $related->newQuery()
                        ->where($ownerKeyName, $branchId)
                        ->value('dealer_id');

                    return $branchDealerId !== null && (string) $branchDealerId === (string) $dealerId;
                }
            }
        }

        if ($this->tableHasColumnCached($subject, 'dealer_id')) {
            $subjectDealerId = $subject->getAttribute('dealer_id');

            return $subjectDealerId !== null && (string) $subjectDealerId === (string) $dealerId;
        }

        return false;
    }

    /**
     * Cache forever whether a given model's table has a specific column.
     */
    private function tableHasColumnCached(Model $model, string $column): bool
    {
        $table = $model->getTable();
        $connection = $model->getConnectionName() ?: config('database.default');

        $cacheKey = "schema_has_column:{$connection}:{$table}:{$column}";

        return Cache::rememberForever($cacheKey, static function () use ($connection, $table, $column): bool {
            return Schema::connection($connection)->hasColumn($table, $column);
        });
    }
}
