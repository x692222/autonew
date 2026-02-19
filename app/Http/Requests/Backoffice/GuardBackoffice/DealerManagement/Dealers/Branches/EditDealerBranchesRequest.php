<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerBranch $branch */
        $branch = $this->route('branch');

        return Gate::inspect('updateBranch', [$dealer, $branch])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
