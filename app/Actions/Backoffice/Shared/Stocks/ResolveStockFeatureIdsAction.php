<?php

namespace App\Actions\Backoffice\Shared\Stocks;

use App\Actions\Backoffice\Shared\SystemRequests\CreateSystemRequestAction;
use App\Models\Stock\StockFeatureTag;

class ResolveStockFeatureIdsAction
{
    public function __construct(private readonly CreateSystemRequestAction $createSystemRequestAction)
    {
    }

    public function execute(string $type, array $data, ?string $actorId = null, string $actorGuard = 'backoffice'): array
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

                    $this->createSystemRequestAction->execute(
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
