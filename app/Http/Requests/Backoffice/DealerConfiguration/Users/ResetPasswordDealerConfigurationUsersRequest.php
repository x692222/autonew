<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Users;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordDealerConfigurationUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        return [];
    }
}
