<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Actions\Backoffice\Shared\Leads\CreateLeadAction;
use App\Actions\Backoffice\Shared\Leads\UpdateLeadAction;
use App\Actions\Backoffice\Shared\Leads\DeleteLeadAction;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\CreateDealerLeadsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\DestroyDealerLeadRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\EditDealerLeadRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\IndexDealerLeadsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\ShowDealerLeadRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\StoreDealerLeadsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads\UpdateDealerLeadRequest;
use App\Http\Resources\Backoffice\Shared\Leads\LeadIndexResource;
use App\Http\Resources\Backoffice\Shared\Leads\LeadStageHistoryResource;
use App\Models\Dealer\Dealer;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStageEvent;
use App\Support\Options\DealerOptions;
use App\Support\Options\LeadOptions;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadsController extends Controller
{
    public function show(IndexDealerLeadsRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();

        $query = $dealer->leads()
            ->select([
                'id',
                'dealer_id',
                'branch_id',
                'assigned_to_dealer_user_id',
                'pipeline_id',
                'stage_id',
                'firstname',
                'lastname',
                'email',
                'contact_no',
                'status',
                'source',
                'created_at',
            ])
            ->with([
                'dealer:id,name',
                'branch:id,name',
                'assignedToDealerUser:id,firstname,lastname',
                'pipeline:id,name',
                'stage:id,name,pipeline_id',
                'stockItems:id,name,internal_reference,branch_id,published_at,is_active,is_sold',
                'stockItems.branch:id,dealer_id',
                'stockItems.branch.dealer:id,is_active',
            ])
            ->withCount(['notes', 'conversations'])
            ->filterSearch($filters['search'] ?? null, [
                'firstname',
                'lastname',
                'email',
                'contact_no',
                'source',
            ]);

        $query->when($filters['branch_id'] ?? null, fn (Builder $builder, string $branchId) => $builder->where('branch_id', $branchId));
        $query->when($filters['assigned_to_dealer_user_id'] ?? null, fn (Builder $builder, string $userId) => $builder->where('assigned_to_dealer_user_id', $userId));
        $query->when($filters['pipeline_id'] ?? null, fn (Builder $builder, string $pipelineId) => $builder->where('pipeline_id', $pipelineId));
        $query->when($filters['stage_id'] ?? null, fn (Builder $builder, string $stageId) => $builder->where('stage_id', $stageId));
        $query->when($filters['lead_status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
        $query->when($filters['source'] ?? null, fn (Builder $builder, string $source) => $builder->where('source', $source));

        $sortBy = $filters['sortBy'] ?? 'created_date';
        $direction = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'firstname' => $query->orderBy('firstname', $direction),
            'lastname' => $query->orderBy('lastname', $direction),
            'lead_status' => $query->orderBy('status', $direction),
            'source' => $query->orderBy('source', $direction),
            'branch' => $query->orderByRaw("(select name from dealer_branches where dealer_branches.id = leads.branch_id limit 1) {$direction}"),
            'assigned_to' => $query->orderByRaw("(select firstname from dealer_users where dealer_users.id = leads.assigned_to_dealer_user_id limit 1) {$direction}"),
            'pipeline' => $query->orderByRaw("(select name from lead_pipelines where lead_pipelines.id = leads.pipeline_id limit 1) {$direction}"),
            'stage' => $query->orderByRaw("(select name from lead_stages where lead_stages.id = leads.stage_id limit 1) {$direction}"),
            'notes_count' => $query->orderBy('notes_count', $direction),
            'conversations_count' => $query->orderBy('conversations_count', $direction),
            default => $query->orderBy('created_at', $direction),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $request->attributes->set('lead_context', [
            'can_manage' => Gate::forUser($request->user('backoffice'))->inspect('showLeads', $dealer)->allowed(),
            'can_show_notes' => Gate::forUser($request->user('backoffice'))->inspect('showNotes', $dealer)->allowed(),
        ]);

        $records->setCollection(
            $records->getCollection()->map(fn (Lead $lead) => (new LeadIndexResource($lead))->toArray($request))
        );

        $columns = DataTableColumnBuilder::make(
            keys: [
                'branch',
                'assigned_to',
                'pipeline',
                'stage',
                'firstname',
                'lastname',
                'lead_status',
                'source',
                'notes_count',
                'conversations_count',
                'created_date',
            ],
            sortableKeys: ['branch', 'assigned_to', 'pipeline', 'stage', 'firstname', 'lastname', 'lead_status', 'source', 'notes_count', 'conversations_count', 'created_date'],
            numericCountSuffix: true
        );

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/Leads', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'leads',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'options' => [
                'branches' => DealerOptions::branchesList((string) $dealer->id, false)->resolve(),
                'dealer_users' => DealerOptions::usersList((string) $dealer->id, false)->resolve(),
                'pipelines' => DealerOptions::pipelinesList((string) $dealer->id, false)->resolve(),
                'stages' => DealerOptions::stagesList((string) $dealer->id, null, false)->resolve(),
                'statuses' => LeadOptions::statusOptions(false)->resolve(),
                'sources' => LeadOptions::sourceOptions(false)->resolve(),
            ],
        ]);
    }

    public function create(CreateDealerLeadsRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Leads/Create', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'leads',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)),
            'options' => [
                'branches' => DealerOptions::branchesList((string) $dealer->id)->resolve(),
                'dealer_users' => DealerOptions::usersList((string) $dealer->id)->resolve(),
                'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
                'stages' => DealerOptions::stagesList((string) $dealer->id, null)->resolve(),
                'statuses' => LeadOptions::statusOptions()->resolve(),
                'sources' => LeadOptions::sourceOptions()->resolve(),
                'correspondence_languages' => LeadOptions::correspondenceLanguageOptions()->resolve(),
            ],
        ]);
    }

    public function store(StoreDealerLeadsRequest $request, Dealer $dealer, CreateLeadAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $data['dealer_id'] = $dealer->id;

        $action->execute($dealer, $data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)))
            ->with('success', 'Lead created.');
    }

    public function overview(ShowDealerLeadRequest $request, Dealer $dealer, Lead $lead): Response
    {
        $lead->load([
            'dealer:id,name',
            'branch:id,name',
            'assignedToDealerUser:id,firstname,lastname',
            'pipeline:id,name',
            'stage:id,name',
            'stockItems:id,name,internal_reference,published_at,is_active,is_sold,branch_id',
            'stockItems.branch:id,dealer_id',
            'stockItems.branch.dealer:id,is_active',
        ])->loadCount(['notes', 'conversations', 'stageEvents']);

        $returnTo = $request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id));

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Leads/Overview', [
            'publicTitle' => 'Lead Overview',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'lead' => [
                'id' => $lead->id,
                'firstname' => $lead->firstname,
                'lastname' => $lead->lastname,
                'email' => $lead->email,
                'contact_no' => $lead->contact_no,
                'source' => $lead->source,
                'status' => $lead->status,
                'correspondence_language' => optional($lead->correspondence_language)->value ?? $lead->correspondence_language,
                'registration_date' => optional($lead->registration_date)?->toDateString(),
                'branch' => $lead->branch?->name,
                'assigned_to' => $lead->assignedToDealerUser ? trim((string) $lead->assignedToDealerUser->firstname . ' ' . (string) $lead->assignedToDealerUser->lastname) : '-',
                'pipeline' => $lead->pipeline?->name,
                'stage' => $lead->stage?->name,
                'counts' => [
                    'notes' => (int) $lead->notes_count,
                    'conversations' => (int) $lead->conversations_count,
                    'stage_events' => (int) $lead->stage_events_count,
                ],
                'stock_items' => $lead->stockItems->map(fn ($stock) => [
                    'id' => $stock->id,
                    'name' => $stock->name,
                    'internal_reference' => $stock->internal_reference,
                    'is_live' => (bool) $stock->isLive($stock),
                ])->values()->all(),
                'can' => [
                    'edit' => Gate::forUser($request->user('backoffice'))->inspect('editLead', [$dealer, $lead])->allowed(),
                    'delete' => Gate::forUser($request->user('backoffice'))->inspect('deleteLead', [$dealer, $lead])->allowed(),
                    'show_notes' => Gate::forUser($request->user('backoffice'))->inspect('showNotes', $dealer)->allowed(),
                ],
            ],
            'returnTo' => $returnTo,
        ]);
    }

    public function conversations(ShowDealerLeadRequest $request, Dealer $dealer, Lead $lead): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Leads/Conversations', [
            'publicTitle' => 'Lead Conversations',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'lead' => ['id' => $lead->id],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)),
        ]);
    }

    public function stageHistory(ShowDealerLeadRequest $request, Dealer $dealer, Lead $lead): Response
    {
        $filters = $request->validated();

        $query = LeadStageEvent::query()
            ->where('lead_id', $lead->id)
            ->with([
                'fromStage:id,name',
                'toStage:id,name',
                'changedByDealerUser:id,firstname,lastname',
            ])
            ->orderByDesc('created_at');

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(fn (LeadStageEvent $item) => (new LeadStageHistoryResource($item))->toArray($request))
        );

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Leads/StageHistory', [
            'publicTitle' => 'Lead Stage History',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'lead' => ['id' => $lead->id],
            'records' => $records,
            'filters' => $filters,
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)),
        ]);
    }

    public function edit(EditDealerLeadRequest $request, Dealer $dealer, Lead $lead): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Leads/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'leads',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)),
            'data' => [
                'id' => $lead->id,
                'branch_id' => $lead->branch_id,
                'assigned_to_dealer_user_id' => $lead->assigned_to_dealer_user_id,
                'pipeline_id' => $lead->pipeline_id,
                'stage_id' => $lead->stage_id,
                'firstname' => $lead->firstname,
                'lastname' => $lead->lastname,
                'email' => $lead->email,
                'contact_no' => $lead->contact_no,
                'source' => $lead->source,
                'status' => $lead->status,
                'correspondence_language' => optional($lead->correspondence_language)->value ?? $lead->correspondence_language,
                'registration_date' => optional($lead->registration_date)?->toDateString(),
            ],
            'options' => [
                'branches' => DealerOptions::branchesList((string) $dealer->id)->resolve(),
                'dealer_users' => DealerOptions::usersList((string) $dealer->id)->resolve(),
                'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
                'stages' => DealerOptions::stagesList((string) $dealer->id, null)->resolve(),
                'statuses' => LeadOptions::statusOptions()->resolve(),
                'sources' => LeadOptions::sourceOptions()->resolve(),
                'correspondence_languages' => LeadOptions::correspondenceLanguageOptions()->resolve(),
            ],
        ]);
    }

    public function update(UpdateDealerLeadRequest $request, Dealer $dealer, Lead $lead, UpdateLeadAction $action): RedirectResponse
    {
        $action->execute($dealer, $lead, $request->safe()->except(['return_to']));

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)))
            ->with('success', 'Lead updated.');
    }

    public function destroy(DestroyDealerLeadRequest $request, Dealer $dealer, Lead $lead, DeleteLeadAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($dealer, $lead);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.leads', $dealer->id)))
            ->with('success', 'Lead deleted.');
    }
}
