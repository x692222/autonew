<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\IndexPendingFeatureTagsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\UpdatePendingFeatureTagRequest;
use App\Models\Stock\StockFeatureTag;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PendingFeatureTagsController extends Controller
{
    public function index(IndexPendingFeatureTagsRequest $request): Response
    {
        $filters = $request->validated();

        $query = StockFeatureTag::query()
            ->with([
                'requestedByUser:id,firstname,lastname,email',
                'requestedByDealerUser:id,firstname,lastname,email,dealer_id',
                'requestedByDealerUser.dealer:id,name',
                'reviewedByUser:id,firstname,lastname',
            ])
            ->when(($filters['status'] ?? 'pending') === 'pending', fn ($q) => $q->pendingReview())
            ->when(($filters['status'] ?? null) === 'reviewed', fn ($q) => $q->whereNotNull('reviewed_at'))
            ->when(! empty($filters['search']), function ($builder) use ($filters) {
                $search = (string) $filters['search'];
                $builder->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('stock_type', 'like', "%{$search}%")
                        ->orWhereHas('requestedByUser', fn ($q) => $q->where('email', 'like', "%{$search}%"))
                        ->orWhereHas('requestedByDealerUser', fn ($q) => $q->where('email', 'like', "%{$search}%"));
                });
            });

        $sortBy = $filters['sortBy'] ?? 'created_at';
        $descending = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        match ($sortBy) {
            'name' => $query->orderBy('name', $descending),
            'stock_type' => $query->orderBy('stock_type', $descending),
            default => $query->orderBy('created_at', $descending),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection($records->getCollection()->map(function (StockFeatureTag $tag) use ($request) {
            $actor = $request->user('backoffice');
            $submittedByName = $tag->requestedByUser
                ? trim((string) $tag->requestedByUser->firstname . ' ' . $tag->requestedByUser->lastname)
                : trim((string) optional($tag->requestedByDealerUser)->firstname . ' ' . optional($tag->requestedByDealerUser)->lastname);

            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'stock_type' => $tag->stock_type,
                'is_approved' => (bool) $tag->is_approved,
                'reviewed_at' => optional($tag->reviewed_at)?->toDateTimeString(),
                'reviewed_by' => $tag->reviewedByUser ? trim((string) $tag->reviewedByUser->firstname . ' ' . $tag->reviewedByUser->lastname) : null,
                'submitted_by_guard' => $tag->requested_by_user_id ? 'backoffice' : ($tag->requested_by_dealer_user_id ? 'dealer' : '-'),
                'submitted_by_name' => $submittedByName ?: '-',
                'submitted_by_email' => $tag->requestedByUser?->email ?: $tag->requestedByDealerUser?->email,
                'dealer_name' => $tag->requestedByDealerUser?->dealer?->name,
                'can' => [
                    'update' => $actor?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false,
                ],
            ];
        }));

        return Inertia::render('GuardBackoffice/System/PendingFeatureTags/Index', [
            'publicTitle' => 'Pending Feature Tags',
            'filters' => $filters,
            'statusOptions' => [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Reviewed', 'value' => 'reviewed'],
            ],
            'columns' => [
                ['name' => 'name', 'label' => 'Tag', 'sortable' => true, 'align' => 'left', 'field' => 'name'],
                ['name' => 'stock_type', 'label' => 'Type', 'sortable' => true, 'align' => 'left', 'field' => 'stock_type'],
                ['name' => 'submitted_by_name', 'label' => 'Submitted By', 'sortable' => false, 'align' => 'left', 'field' => 'submitted_by_name'],
                ['name' => 'dealer_name', 'label' => 'Dealer', 'sortable' => false, 'align' => 'left', 'field' => 'dealer_name'],
                ['name' => 'is_approved', 'label' => 'Approved', 'sortable' => false, 'align' => 'left', 'field' => 'is_approved'],
                ['name' => 'reviewed_at', 'label' => 'Reviewed At', 'sortable' => true, 'align' => 'left', 'field' => 'reviewed_at'],
            ],
            'records' => $records,
        ]);
    }

    public function update(UpdatePendingFeatureTagRequest $request, StockFeatureTag $stockFeatureTag): RedirectResponse
    {
        $stockFeatureTag->update([
            'is_approved' => (bool) $request->validated('is_approved'),
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user('backoffice')?->id,
        ]);

        return back()->with('success', 'Feature tag review saved.');
    }
}

