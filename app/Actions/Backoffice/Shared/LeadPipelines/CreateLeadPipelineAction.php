<?php

namespace App\Actions\Backoffice\Shared\LeadPipelines;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;

class CreateLeadPipelineAction
{
    public function execute(Dealer $dealer, array $data): LeadPipeline
    {
        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        return $dealer->pipelines()->create($data);
    }
}
