<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowDealerTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        $ability = match ($this->route()?->getName()) {
            'backoffice.dealer-management.dealers.overview' => 'show',
            'backoffice.dealer-management.dealers.branches' => 'showBranches',
            'backoffice.dealer-management.dealers.sales-people' => 'showSalesPeople',
            'backoffice.dealer-management.dealers.users' => 'showUsers',
            'backoffice.dealer-management.dealers.stock' => 'showStock',
            'backoffice.dealer-management.dealers.leads' => 'showLeads',
            'backoffice.dealer-management.dealers.lead-pipelines.index' => 'showLeadPipelines',
            'backoffice.dealer-management.dealers.lead-stages.index' => 'showLeadStages',
            'backoffice.dealer-management.dealers.notification-history' => 'showNotificationHistory',
            'backoffice.dealer-management.dealers.settings' => 'showSettings',
            'backoffice.dealer-management.dealers.billings' => 'showBillings',
            'backoffice.dealer-management.dealers.audit-log' => 'showAuditLog',
            default => null,
        };

        if (!$ability) {
            return false;
        }

        return Gate::inspect($ability, $dealer)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
