<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Support\Validation\Dealers\DealerBranchValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerBranchesRequest extends FormRequest
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
        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerBranchValidationRules::class)->single(requireContactNumbers: false));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_numbers' => app(DealerBranchValidationRules::class)->normalizeContactNumbers($this->input('contact_numbers')),
        ]);
    }
}
