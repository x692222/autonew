<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateDealersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Dealer::class)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
