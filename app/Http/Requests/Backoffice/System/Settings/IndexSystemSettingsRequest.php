<?php

namespace App\Http\Requests\Backoffice\System\Settings;

use App\Models\System\Configuration\SystemConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', SystemConfiguration::class)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
