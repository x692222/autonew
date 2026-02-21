<?php

namespace App\Support\BankingDetails;

use App\Models\Billing\BankingDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BankingDetailsIndexService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?string $dealerId = null): LengthAwarePaginator
    {
        return BankingDetail::query()
            ->when($dealerId, fn ($query) => $query->forDealer($dealerId), fn ($query) => $query->system())
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('bank', 'like', "%{$search}%")
                    ->orWhere('account_holder', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('branch_name', 'like', "%{$search}%")
                    ->orWhere('branch_code', 'like', "%{$search}%")
                    ->orWhere('swift_code', 'like', "%{$search}%")
                    ->orWhere('other_details', 'like', "%{$search}%")))
            ->latest()
            ->paginate((int) ($filters['rowsPerPage'] ?? 10))
            ->appends($filters);
    }

    /**
     * @param  callable(BankingDetail):array<string, bool>  $abilityResolver
     * @return array<string, mixed>
     */
    public function toArray(BankingDetail $row, callable $abilityResolver): array
    {
        return [
            'id' => $row->id,
            'bank' => $row->bank,
            'account_holder' => $row->account_holder,
            'account_number' => $row->account_number,
            'branch_name' => $row->branch_name,
            'branch_code' => $row->branch_code,
            'swift_code' => $row->swift_code,
            'other_details' => $row->other_details,
            'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
            'can' => $abilityResolver($row),
        ];
    }
}
