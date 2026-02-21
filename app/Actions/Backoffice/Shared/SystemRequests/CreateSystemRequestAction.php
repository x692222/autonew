<?php

namespace App\Actions\Backoffice\Shared\SystemRequests;

use App\Enums\SystemRequestStatusEnum;
use App\Models\System\SystemRequest;
use Illuminate\Database\Eloquent\Model;

class CreateSystemRequestAction
{
    public function execute(
        string $type,
        string $subject,
        string $message,
        ?string $userId = null,
        ?string $dealerUserId = null,
        ?Model $requestable = null
    ): SystemRequest {
        return SystemRequest::query()->create([
            'user_id' => $userId,
            'dealer_user_id' => $dealerUserId,
            'requestable_type' => $requestable?->getMorphClass(),
            'requestable_id' => $requestable?->getKey(),
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'status' => SystemRequestStatusEnum::SUBMITTED->value,
        ]);
    }
}
