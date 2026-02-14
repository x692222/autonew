<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Branches;

use App\Models\Dealer\Dealer;
use App\Models\Location\LocationSuburb;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealerBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createBranch', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'suburb_id' => ['required', 'string', Rule::exists(LocationSuburb::class, 'id')],
            'contact_numbers' => ['nullable', 'string', 'max:255'],
            'display_address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
