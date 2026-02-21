<?php

namespace App\Actions\Backoffice\Shared\PendingFeatureTags;

use App\Models\Stock\StockFeatureTag;

class ReviewPendingFeatureTagAction
{
    public function execute(StockFeatureTag $stockFeatureTag, bool $isApproved, ?string $reviewedByUserId): void
    {
        $stockFeatureTag->update([
            'is_approved' => $isApproved,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewedByUserId,
        ]);
    }
}
