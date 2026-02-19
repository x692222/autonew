<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock;
use App\Enums\PoliceClearanceStatusEnum;
use App\Models\Dealer\Configuration\DealerConfiguration;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Stock\Stock;
use App\Models\Stock\StockMake;
use App\Models\Stock\StockModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealerStockRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var Dealer|null $dealer */
        $dealer = $this->route('dealer');

        if (! $dealer) {
            return;
        }

        $merge = [];

        if (! $this->exists('active_status')) {
            $merge['active_status'] = 'active';
        }

        if (! $this->exists('sold_status')) {
            $merge['sold_status'] = 'unsold';
        }

        if (! $this->exists('type')) {
            $defaultType = DealerConfiguration::query()
                ->where('dealer_id', $dealer->id)
                ->where('key', 'default_stock_type_filter')
                ->value('value');

            if (is_string($defaultType) && in_array($defaultType, Stock::STOCK_TYPE_OPTIONS, true)) {
                $merge['type'] = $defaultType;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('showStock', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'string', Rule::exists(DealerBranch::class, 'id')],
            'active_status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sold_status' => ['nullable', Rule::in(['sold', 'unsold'])],
            'police_clearance_ready' => ['nullable', Rule::in(PoliceClearanceStatusEnum::values())],
            'type' => ['nullable', Rule::in(Stock::STOCK_TYPE_OPTIONS)],
            'is_import' => ['nullable', Rule::in(['yes', 'no'])],
            'gearbox_type' => ['nullable', 'string', 'max:50'],
            'drive_type' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'millage_range' => ['nullable', 'string', 'max:40'],
            'make_id' => ['nullable', 'string', Rule::exists(StockMake::class, 'id')],
            'model_id' => ['nullable', 'string', Rule::exists(StockModel::class, 'id')],
            'condition' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
