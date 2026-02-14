<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait InternalReferenceTrait
{
    /**
     * Generate a unique, uppercase alphanumeric internal reference.
     *
     * Example: X7K9Q2M8
     *
     * @param int $length Length of the reference (default: 8)
     */
    public function generateUniqueInternalReference(int $length = 10): string
    {
        $table   = $this->getTable();
        $keyName = $this->getKeyName();
        $key     = $this->getKey();

        do {
            // Uppercase alphanumeric only
            $candidate = strtoupper(Str::random($length));

            // Ensure A–Z and 0–9 only (Str::random is already safe, this is defensive)
            $candidate = preg_replace('/[^A-Z0-9]/', '', $candidate);

            $exists = DB::table($table)
                ->where('internal_reference', $candidate)
                ->when($this->exists && $key !== null, function ($q) use ($keyName, $key) {
                    $q->where($keyName, '!=', $key);
                })
                ->exists();

        } while ($exists || strlen($candidate) < $length);

        return $candidate;
    }

    /**
     * Auto-set internal_reference when saving if empty.
     * - On create: generate if empty
     * - On update: generate ONLY if still empty
     */
    public static function bootInternalReferenceTrait(): void
    {
        static::saving(function ($model) {
            if (! empty($model->internal_reference)) {
                return;
            }

            $model->internal_reference = $model->generateUniqueInternalReference();
        });
    }
}
