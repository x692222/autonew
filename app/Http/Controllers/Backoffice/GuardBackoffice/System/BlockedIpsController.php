<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\BlockedIps\IndexBlockedIpsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\BlockedIps\UnblockBlockedIpRequest;
use App\Models\Security\BlockedIp;
use App\Support\Security\GuardIpBlockService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BlockedIpsController extends Controller
{
    public function __construct(private readonly GuardIpBlockService $ipBlockService)
    {
    }

    public function index(IndexBlockedIpsRequest $request): Response
    {
        $filters = $request->validated();
        $actor = $request->user('backoffice');

        $query = BlockedIp::query()
            ->whereNotNull('blocked_at')
            ->when(! empty($filters['search']), function ($builder) use ($filters) {
                $search = (string) $filters['search'];
                $builder->where(function ($q) use ($search) {
                    $q->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('guard_name', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['guard_name']), fn ($q) => $q->where('guard_name', (string) $filters['guard_name']));

        $sortBy = (string) ($filters['sortBy'] ?? 'blocked_at');
        $descending = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'ip_address' => $query->orderBy('ip_address', $descending),
            'guard_name' => $query->orderBy('guard_name', $descending),
            'failed_attempts' => $query->orderBy('failed_attempts', $descending),
            default => $query->orderBy('blocked_at', $descending),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection($records->getCollection()->map(fn (BlockedIp $item) => [
            'id' => $item->id,
            'ip_address' => $item->ip_address,
            'guard_name' => strtoupper((string) $item->guard_name),
            'failed_attempts' => (int) $item->failed_attempts,
            'blocked_at' => optional($item->blocked_at)?->toDateTimeString(),
            'country' => $item->country,
            'created_at' => optional($item->created_at)?->toDateTimeString(),
            'can' => [
                'unblock' => $actor?->can('delete', $item) ?? false,
            ],
        ]));

        return Inertia::render('GuardBackoffice/System/BlockedIps/Index', [
            'publicTitle' => 'Blocked IPs',
            'filters' => $filters,
            'guardOptions' => $this->ipBlockService->guardOptions(),
            'columns' => [
                ['name' => 'ip_address', 'label' => 'IP Address', 'sortable' => true, 'align' => 'left', 'field' => 'ip_address'],
                ['name' => 'guard_name', 'label' => 'Guard', 'sortable' => true, 'align' => 'left', 'field' => 'guard_name'],
                ['name' => 'failed_attempts', 'label' => 'Failed Attempts', 'sortable' => true, 'align' => 'right', 'field' => 'failed_attempts', 'numeric' => true],
                ['name' => 'blocked_at', 'label' => 'Blocked At', 'sortable' => true, 'align' => 'left', 'field' => 'blocked_at'],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country'],
            ],
            'records' => $records,
        ]);
    }

    public function destroy(UnblockBlockedIpRequest $request, BlockedIp $blockedIp): RedirectResponse
    {
        $this->ipBlockService->unblock($blockedIp);

        return back()->with('success', 'IP unblocked.');
    }
}
