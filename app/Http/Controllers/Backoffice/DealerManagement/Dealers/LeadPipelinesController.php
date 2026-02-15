<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\CreateDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\DestroyDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\EditDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\IndexDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\StoreDealerLeadPipelinesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines\UpdateDealerLeadPipelinesRequest;
use App\Http\Resources\Backoffice\Leads\LeadPipelineIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
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

        $columns = collect(['name', 'stages_count'])
            ->map(fn (string $key) => [
                'name' => $key,
                'label' => Str::headline($key),
                'sortable' => true,
                'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
                'field' => $key,
                'numeric' => Str::endsWith($key, '_count'),
            ])->values()->all();

        return Inertia::render('DealerManagement/Dealers/Tabs/LeadPipelines', [
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
        return Inertia::render('DealerManagement/Dealers/LeadPipelines/Create', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)),
        ]);
    }

    public function store(StoreDealerLeadPipelinesRequest $request, Dealer $dealer): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);

        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        $dealer->pipelines()->create($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)))
            ->with('success', 'Lead pipeline created.');
    }

    public function edit(EditDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline): Response
    {
        return Inertia::render('DealerManagement/Dealers/LeadPipelines/Edit', [
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

    public function update(UpdateDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);

        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        $leadPipeline->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-pipelines.index', $dealer->id)))
            ->with('success', 'Lead pipeline updated.');
    }

    public function destroy(DestroyDealerLeadPipelinesRequest $request, Dealer $dealer, LeadPipeline $leadPipeline): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $leadPipeline->delete();

        return back()->with('success', 'Lead pipeline deleted.');
    }
}
