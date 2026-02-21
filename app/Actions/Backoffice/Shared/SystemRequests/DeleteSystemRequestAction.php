<?php

namespace App\Actions\Backoffice\Shared\SystemRequests;

use App\Models\System\SystemRequest;

class DeleteSystemRequestAction
{
    public function execute(SystemRequest $systemRequest): void
    {
        $systemRequest->delete();
    }
}
