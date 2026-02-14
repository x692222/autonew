<?php

namespace App\Traits;

use App\Models\Audit\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasActivityTrait
{
    /**
     * Models can override by defining: protected array $activityIgnore = [...]
     */
    protected array $defaultActivityIgnore = [
        'post_content',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationship
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    /**
     * Manual logging available.
     */
    public function addActivity(string $description, ?string $event = null, array $properties = []): ActivityLog
    {
        $attrs = $this->baseActivityAttributes();
        $attrs['description'] = $description;
        $attrs['event'] = $event;
        $attrs['properties'] = $properties ?: null;

        /** @var Model $this */
        return $this->activityLogs()->create($attrs);
    }

    /**
     * Trait boot
     */
    public static function bootHasActivityTrait(): void
    {
        // CREATED
        static::created(function (Model $model) {
            if (!$model->shouldLogActivity()) {
                return;
            }

            $event = $model->activityEventName('created');

            $props = [
                'action' => 'created',
                'model' => class_basename($model),
                'model_id' => $model->getKey(),
                'attributes' => $model->activitySafeAttributes($model->getAttributes()),
            ];

            $model->writeActivity(
                description: $model->activityDescription('created', $props),
                event: $event,
                properties: $props,
            );
        });

        // UPDATED
        static::updated(function (Model $model) {
            if (!$model->shouldLogActivity()) {
                return;
            }

            // Only log when there are real changes
            $changes = $model->getChanges();
            if (empty($changes)) {
                return;
            }

            $event = $model->activityEventName('updated');

            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $model->getOriginal($field);
            }

            $props = [
                'action' => 'updated',
                'model' => class_basename($model),
                'model_id' => $model->getKey(),
                'changes' => [
                    'before' => $model->activitySafeAttributes($before),
                    'after' => $model->activitySafeAttributes($changes),
                ],
                'changed_fields' => array_values(array_keys($changes)),
            ];

            $model->writeActivity(
                description: $model->activityDescription('updated', $props),
                event: $event,
                properties: $props,
            );
        });

        // SAVED
        static::saved(function (Model $model) {
            if (!$model->shouldLogActivity()) {
                return;
            }

            // avoid double-logging
            if ($model->wasRecentlyCreated) {
                return;
            }

            if (method_exists($model, 'wasChanged') && $model->wasChanged()) {
                // updated already logged
                return;
            }

            $event = $model->activityEventName('saved');

            $props = [
                'action' => 'saved',
                'model' => class_basename($model),
                'model_id' => $model->getKey(),
            ];

            $model->writeActivity(
                description: $model->activityDescription('saved', $props),
                event: $event,
                properties: $props,
            );
        });

        // DELETED (soft delete or force delete)
        static::deleted(function (Model $model) {
            if (!$model->shouldLogActivity()) {
                return;
            }

            $usingSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
            $isForceDeleting = $usingSoftDeletes ? $model->isForceDeleting() : true;

            $event = $model->activityEventName($isForceDeleting ? 'force_deleted' : 'deleted');

            $props = [
                'action' => $isForceDeleting ? 'force_deleted' : 'deleted',
                'model' => class_basename($model),
                'model_id' => $model->getKey(),
                'attributes' => $model->activitySafeAttributes($model->getAttributes()),
            ];

            $model->writeActivity(
                description: $model->activityDescription($props['action'], $props),
                event: $event,
                properties: $props,
            );

            if ($isForceDeleting) {
                $model->activityLogs()->delete();
            }
        });
    }

    protected function writeActivity(string $description, ?string $event, array $properties = []): void
    {
        try {
            /** @var Model $this */
            $attrs = $this->baseActivityAttributes();

            $attrs['description'] = Str::limit($description ?: 'Activity', 255, '…');
            $attrs['event'] = $event;
            $attrs['properties'] = $properties ?: null;

            $this->activityLogs()->create($attrs);
        } catch (\Throwable $e) {
            // no exception
        }
    }

    /**
     * Build base attributes safely.
     */
    protected function baseActivityAttributes(): array
    {
        return [
            'user_id' => auth()->guard('backoffice')?->user()?->id,
            'dealer_user_id' => auth()->guard('dealer')?->user()?->id,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];
    }

    /**
     * Models can override with: public function shouldLogActivity(): bool { ... }
     */
    public function shouldLogActivity(): bool
    {
        // prevent recursion
        if ($this instanceof ActivityLog) {
            return false;
        }

        // allow models to disable by defining: protected bool $logActivity = false;
        if (property_exists($this, 'logActivity') && $this->logActivity === false) {
            return false;
        }

        return true;
    }

    /**
     * Event name like: product_created / orderitem_updated / articlepost_deleted
     */
    protected function activityEventName(string $action): string
    {
        $model = Str::snake(class_basename($this));
        return "{$model}_{$action}";
    }

    /**
     * Human-ish description. Models can override by adding:
     * public function activityDescription(string $action, array $props): string { ... }
     */
    public function activityDescription(string $action, array $props = []): string
    {
        $label = $this->activityLabel();

        return match ($action) {
            'created' => "{$label} created",
            'updated' => "{$label} updated".(!empty($props['changed_fields']) ? ': '.implode(', ', $props['changed_fields']) : ''),
            'saved' => "{$label} saved",
            'deleted' => "{$label} deleted",
            'force_deleted' => "{$label} permanently deleted",
            default => "{$label} {$action}",
        };
    }

    /**
     * A nice label like: Product "ABC123" (or name/title/slug/id)
     */
    protected function activityLabel(): string
    {
        $identifier = $this->getAttribute('name')
            ??$this->getAttribute('title')
            ??$this->getAttribute('slug')
            ??$this->getKey();

        return class_basename($this).' "'.Str::limit((string)$identifier, 60, '…').'"';
    }

    /**
     * Remove noisy/sensitive fields from attribute arrays.
     */
    protected function activitySafeAttributes(array $attributes): array
    {
        $ignore = $this->defaultActivityIgnore;

        if (property_exists($this, 'activityIgnore') && is_array($this->activityIgnore)) {
            $ignore = array_unique(array_merge($ignore, $this->activityIgnore));
        }

        foreach ($ignore as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }
}
