<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait SluggableTrait
{
    /**
     * Resolve a slug source string.
     * Prefer `name`, else fallback to `title`.
     */
    protected function resolveSlugSource(): ?string
    {
        // Using getAttribute avoids PHP notices when the property doesn't exist on a model
        $name  = $this->getAttribute('name');
        $title = $this->getAttribute('title');

        $source = $name ?: $title;

        return is_string($source) && trim($source) !== '' ? $source : null;
    }

    /**
     * Generate a unique slug for this model using `name` or `title`.
     * If the slug exists in the model's table, append a random 3-char suffix.
     *
     * @param string|null $source Optionally pass a custom source string; defaults to `name` else `title`
     * @param string $column The column to store the slug in (default: 'slug')
     * @return string
     */
    public function generateUniqueSlug(?string $source = null, string $column = 'slug'): string
    {
        $source = $source ?? $this->resolveSlugSource() ?? '';
        $base = Str::slug($source, '-');

        if ($base === '') {
            // Fallback if name/title is empty/non-sluggable
            $base = strtolower(Str::random(6));
        }

        $table   = $this->getTable();
        $keyName = $this->getKeyName();
        $key     = $this->getKey();

        // Check if base slug already exists on this table (exclude current row if updating)
        $exists = DB::table($table)
            ->where($column, $base)
            ->when($this->exists && $key !== null, function ($q) use ($keyName, $key) {
                $q->where($keyName, '!=', $key);
            })
            ->exists();

        if (!$exists) {
            return $base;
        }

        // If it exists, keep trying with a 3-char lowercase suffix until unique
        do {
            $slug   = $base . '-' . strtolower(Str::random(3));
            $exists = DB::table($table)
                ->where($column, $slug)
                ->when($this->exists && $key !== null, function ($q) use ($keyName, $key) {
                    $q->where($keyName, '!=', $key);
                })
                ->exists();
        } while ($exists);

        return $slug;
    }

    /**
     * Auto-set the slug when saving if it's empty.
     * - On create: if slug empty, create from name/title
     * - On update: if name/title changed, regenerate slug
     */
    public static function bootSluggableTrait(): void
    {
        static::saving(function ($model) {
            $source = $model->resolveSlugSource();

            // If neither name nor title exists, do nothing
            if (empty($source)) {
                return;
            }

            // On create: if slug empty, generate one
            if (!$model->exists) {
                if (empty($model->slug)) {
                    $model->slug = $model->generateUniqueSlug($source, 'slug');
                }
                return;
            }

            // On update: if either name OR title changed, regenerate
            if ($model->isDirty('name') || $model->isDirty('title')) {
                $model->slug = $model->generateUniqueSlug($source, 'slug');
                return;
            }

            // If slug empty, generate
            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug($source, 'slug');
            }
        });
    }
}
