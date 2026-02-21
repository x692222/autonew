<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Actions\Backoffice\Shared\LeadPipelines\CreateLeadPipelineAction;
use App\Actions\Backoffice\Shared\LeadPipelines\UpdateLeadPipelineAction;
use App\Actions\Backoffice\Shared\LeadPipelines\DeleteLeadPipelineAction;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\CreateDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\DestroyDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\EditDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\IndexDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\StoreDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadPipelines\UpdateDealerLeadPipelinesRequest;
use App\Http\Resources\Backoffice\Shared\Leads\LeadPipelineIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadPipelinesController extends Controller
{
    public function index(IndexDealerLeadPipelinesRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();

        $query = $dealer->pipelines()
            ->select(['id', 'dealer_id', 'name', 'is_default'])
            ->withCount('stages')
            ->filterSearch($filters['search'] ?? null, ['name']);

        $sortBy = $filters['sortBy'] ?? 'name';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'stages_count' => $query->orderBy('stages_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        $records = $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);
        $actor = $request->user('backoffice');

        $request->attributes->set('lead_pipeline_context', [
            'can_edit' => $actor?->hasPermissionTo('editDealershipPipelines', 'backoffice') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deleteDealershipPipelines', 'backoffice') ?? false,
        ]);

        $records->setCollection(
            $records->getCollection()->map(fn (LeadPipeline $pipeline) => (new LeadPipelineIndexResource($pipeline))->toArray($request))
        );

        $columns = DataTableColumnBuilder::make(
            keys: ['name', 'stages_count'],
            allSortable: true,
            numericCountSuffix: true
        );

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/LeadPipelines', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'lead-pipelines',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
        ]);
    }

    public function create(CreateDealerLeadPipelinesRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/LeadPipelines/Create', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)),
        ]);
    }

    public function store(StoreDealerLeadPipelinesRequest $request, Dealer $dealer, CreateLeadPipelineAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $action->execute($dealer, $data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)))
            ->with('success', 'Lead pipeline created.');
    }

    public function edit(EditDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/LeadPipelines/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)),
            'data' => [
                'id' => $leadPipeline->id,
                'name' => $leadPipeline->name,
                'is_default' => (bool) $leadPipeline->is_default,
            ],
        ]);
    }

    public function update(UpdateDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline, UpdateLeadPipelineAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $action->execute($dealer, $leadPipeline, $data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)))
            ->with('success', 'Lead pipeline updated.');
    }

    public function destroy(DestroyDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline, DeleteLeadPipelineAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($dealer, $leadPipeline);

        return back()->with('success', 'Lead pipeline deleted.');
    }
}
