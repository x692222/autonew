<?php

namespace App\Actions\Backoffice\Shared\SystemRequests;

use App\Models\System\SystemRequest;

class UpdateSystemRequestAction
{
    public function execute(SystemRequest $systemRequest, array $data): void
    {
        $systemRequest->update($data);
    }
}
