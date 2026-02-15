<?php

namespace App\Http\Controllers\Backoffice\System;

use App\Enums\SystemRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\System\DestroySystemRequestManagementRequest;
use App\Http\Requests\Backoffice\System\IndexSystemRequestManagementRequest;
use App\Http\Requests\Backoffice\System\StoreSystemRequest;
use App\Http\Requests\Backoffice\System\UpdateSystemRequestManagementRequest;
use App\Models\System\SystemRequest;
use App\Notifications\System\SystemRequestStatusUpdatedNotification;
use App\Support\System\SystemRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SystemRequestsController extends Controller
{
    public function __construct(private readonly SystemRequestService $systemRequestService)
    {
    }

    public function store(StoreSystemRequest $request): JsonResponse
    {
        $backofficeUser = $request->user('backoffice');
        $dealerUser = $request->user('dealer');

        $this->systemRequestService->create(
            type: $request->validated('type'),
            subject: $request->validated('subject'),
            message: $request->validated('message'),
            userId: $backofficeUser?->id,
            dealerUserId: $dealerUser?->id,
        );

        return response()->json(['ok' => true]);
    }

    public function index(IndexSystemRequestManagementRequest $request): Response
    {
        $filters = $request->validated();

        $query = SystemRequest::query()
            ->with([
                'user:id,firstname,lastname,email',
                'dealerUser:id,firstname,lastname,email,dealer_id',
                'dealerUser.dealer:id,name',
                'requestable',
            ])
            ->when(! empty($filters['search']), function ($builder) use ($filters) {
                $search = (string) $filters['search'];
                $builder->where(function ($query) use ($search) {
                    $query
                        ->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"))
                        ->orWhereHas('dealerUser', fn ($q) => $q->where('email', 'like', "%{$search}%"));
                });
            })
            ->when(! empty($filters['status']), fn ($builder) => $builder->where('status', $filters['status']));

        $sortBy = $filters['sortBy'] ?? 'created_at';
        $descending = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'status' => $query->orderBy('status', $descending),
            'subject' => $query->orderBy('subject', $descending),
            default => $query->orderBy('created_at', $descending),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(function (SystemRequest $row) use ($request) {
                $actor = $request->user('backoffice');
                $requestable = $row->requestable;
                $requestableLabel = (string) (data_get($requestable, 'name') ?: '-');

                $submittedBy = $row->user
                    ? trim((string) $row->user->firstname . ' ' . $row->user->lastname)
                    : trim((string) optional($row->dealerUser)->firstname . ' ' . optional($row->dealerUser)->lastname);

                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'subject' => $row->subject,
                    'message' => Str::limit((string) $row->message, 250),
                    'full_message' => $row->message,
                    'status' => (string) $row->status?->value,
                    'response' => $row->response,
                    'created_at' => optional($row->created_at)?->toDateTimeString(),
                    'requestable_type' => $row->requestable_type,
                    'requestable_id' => $row->requestable_id,
                    'requestable_label' => $requestableLabel,
                    'submitted_by_guard' => $row->user_id ? 'backoffice' : ($row->dealer_user_id ? 'dealer' : '-'),
                    'submitted_by_name' => $submittedBy ?: '-',
                    'submitted_by_email' => $row->user?->email ?: $row->dealerUser?->email,
                    'dealer_name' => $row->dealerUser?->dealer?->name,
                    'can' => [
                        'update' => $actor?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false,
                        'delete' => $actor?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false,
                    ],
                ];
            })
        );

        return Inertia::render('System/SystemRequests/Index', [
            'publicTitle' => 'System Requests',
            'filters' => $filters,
            'statusOptions' => collect(SystemRequestStatusEnum::cases())->map(fn ($s) => [
                'label' => Str::headline(Str::lower($s->value)),
                'value' => $s->value,
            ])->values()->all(),
            'columns' => [
                ['name' => 'subject', 'label' => 'Subject', 'sortable' => true, 'align' => 'left', 'field' => 'subject'],
                ['name' => 'type', 'label' => 'Type', 'sortable' => false, 'align' => 'left', 'field' => 'type'],
                ['name' => 'status', 'label' => 'Status', 'sortable' => true, 'align' => 'left', 'field' => 'status'],
                ['name' => 'submitted_by_name', 'label' => 'Submitted By', 'sortable' => false, 'align' => 'left', 'field' => 'submitted_by_name'],
                ['name' => 'dealer_name', 'label' => 'Dealer', 'sortable' => false, 'align' => 'left', 'field' => 'dealer_name'],
                ['name' => 'requestable_label', 'label' => 'Linked Item', 'sortable' => false, 'align' => 'left', 'field' => 'requestable_label'],
                ['name' => 'created_at', 'label' => 'Created', 'sortable' => true, 'align' => 'left', 'field' => 'created_at'],
            ],
            'records' => $records,
        ]);
    }

    public function update(UpdateSystemRequestManagementRequest $request, SystemRequest $systemRequest): RedirectResponse
    {
        $data = $request->validated();

        $systemRequest->update([
            'status' => $data['status'],
            'response' => $data['response'] ?? null,
        ]);

        if (($data['send_email'] ?? false) === true) {
            $recipient = $systemRequest->user ?: $systemRequest->dealerUser;
            if ($recipient) {
                $recipient->notify(new SystemRequestStatusUpdatedNotification($systemRequest->fresh()));
            }
        }

        return back()->with('success', 'System request updated.');
    }

    public function destroy(DestroySystemRequestManagementRequest $request, SystemRequest $systemRequest): RedirectResponse
    {
        $systemRequest->delete();
        return back()->with('success', 'System request deleted.');
    }
}
