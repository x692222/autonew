<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Branches;

use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateDealerBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createBranch', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
