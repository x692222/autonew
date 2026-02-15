<?php

namespace App\Support\Stock;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Models\Stock\StockFeatureTag;
use App\Models\Stock\StockPublishLog;
use App\Support\StockHelper;
use App\Support\System\SystemRequestService;
use Illuminate\Support\Facades\DB;

class StockWriteService
{
    public function __construct(private readonly SystemRequestService $systemRequestService)
    {
    }

    public function create(Dealer $dealer, array $data, string $actorId, string $actorGuard = 'backoffice'): Stock
    {
        return DB::transaction(function () use ($dealer, $data, $actorId, $actorGuard) {
            $type = (string) $data['type'];
            $typed = (array) ($data['typed'] ?? []);
            $meta = StockHelper::stockRelationMeta();

            $stock = Stock::query()->create([
                'branch_id' => (string) $data['branch_id'],
                'type' => $type,
                'name' => (string) $data['name'],
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'price' => (int) $data['price'],
                'internal_reference' => $data['internal_reference'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'is_sold' => false,
            ]);

            $typedClass = $meta[$type]['class'];
            $typedClass::query()->create([
                ...$typed,
                'stock_id' => (string) $stock->id,
            ]);

            $featureIds = $this->resolveFeatureIds($type, $data, $actorId, $actorGuard);
            if ($featureIds !== []) {
                $stock->features()->sync($featureIds);
            }

            if (!empty($data['published_at'])) {
                StockPublishLog::query()->create([
                    'stock_id' => (string) $stock->id,
                    'action' => StockPublishLog::ACTION_PUBLISH,
                    'by_user_id' => $actorId,
                ]);
            }

            return $stock;
        });
    }

    public function update(Dealer $dealer, Stock $stock, array $data, string $actorId, string $actorGuard = 'backoffice'): Stock
    {
        return DB::transaction(function () use ($dealer, $stock, $data, $actorId, $actorGuard) {
            $type = (string) $stock->type;
            $typed = (array) ($data['typed'] ?? []);
            $meta = StockHelper::stockRelationMeta();

            $wasPublished = !empty($stock->published_at);
            $nowPublished = !empty($data['published_at']);

            $stock->update([
                'branch_id' => (string) $data['branch_id'],
                'name' => (string) $data['name'],
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'price' => (int) $data['price'],
                'internal_reference' => $data['internal_reference'] ?: $stock->internal_reference,
                'published_at' => $data['published_at'] ?? null,
            ]);

            $typedRelation = $meta[$type]['relation'];
            $typedClass = $meta[$type]['class'];

            $typedRow = $stock->{$typedRelation}()->first();
            if (! $typedRow) {
                $typedRow = new $typedClass();
                $typedRow->stock_id = (string) $stock->id;
            }

            foreach ($typed as $key => $value) {
                $typedRow->{$key} = $value;
            }
            $typedRow->save();

            $featureIds = $this->resolveFeatureIds($type, $data, $actorId, $actorGuard);
            $stock->features()->sync($featureIds);

            if ($wasPublished !== $nowPublished) {
                StockPublishLog::query()->create([
                    'stock_id' => (string) $stock->id,
                    'action' => $nowPublished ? StockPublishLog::ACTION_PUBLISH : StockPublishLog::ACTION_UNPUBLISH,
                    'by_user_id' => $actorId,
                ]);
            }

            return $stock->fresh();
        });
    }

    public function markSold(Stock $stock): void
    {
        $stock->update(['is_sold' => true]);
    }

    public function markUnsold(Stock $stock): void
    {
        $stock->update(['is_sold' => false]);
    }

    public function resolveFeatureIds(string $type, array $data, ?string $actorId = null, string $actorGuard = 'backoffice'): array
    {
        $featureIds = collect($data['feature_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $newNames = collect($data['new_feature_names'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        if ($newNames->isNotEmpty()) {
            $createdIds = $newNames->map(function (string $name) use ($type, $actorId, $actorGuard) {
                $tag = StockFeatureTag::query()->firstOrCreate(
                    ['stock_type' => $type, 'name' => $name],
                    [
                        'is_approved' => false,
                        'requested_by_user_id' => $actorGuard === 'backoffice' ? $actorId : null,
                        'requested_by_dealer_user_id' => $actorGuard === 'dealer' ? $actorId : null,
                        'reviewed_at' => null,
                    ]
                );

                if ($tag->wasRecentlyCreated) {
                    $subject = sprintf('Feature Tag Approval Request (%s)', $type);
                    $message = sprintf(
                        "A new feature tag was submitted.\n\nTag: %s\nType: %s\nRequested By Guard: %s\nRequested By ID: %s",
                        $name,
                        $type,
                        $actorGuard,
                        (string) ($actorId ?? '-')
                    );

                    $this->systemRequestService->create(
                        type: 'feature_tag',
                        subject: $subject,
                        message: $message,
                        userId: $actorGuard === 'backoffice' ? $actorId : null,
                        dealerUserId: $actorGuard === 'dealer' ? $actorId : null,
                        requestable: $tag,
                    );
                }

                return (string) $tag->id;
            });

            $featureIds = $featureIds->merge($createdIds)->unique()->values();
        }

        return $featureIds->all();
    }
}
