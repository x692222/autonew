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
                    ->where('label', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%")))
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
            'label' => $row->label,
            'details' => $row->details,
            'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
            'can' => $abilityResolver($row),
        ];
    }
}

