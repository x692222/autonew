<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\CreateDealerConfigurationLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\DestroyDealerConfigurationLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\EditDealerConfigurationLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\IndexDealerConfigurationLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\StoreDealerConfigurationLeadPipelinesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines\UpdateDealerConfigurationLeadPipelinesRequest;
use App\Http\Resources\Backoffice\Shared\Leads\LeadPipelineIndexResource;
use App\Models\Leads\LeadPipeline;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadPipelinesController extends Controller
{
    public function index(IndexDealerConfigurationLeadPipelinesRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
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

        $request->attributes->set('lead_pipeline_context', [
            'can_edit' => $actor?->hasPermissionTo('editPipelines', 'dealer') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deletePipelines', 'dealer') ?? false,
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

        return Inertia::render('GuardDealer/DealerConfiguration/LeadPipelines/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
        ]);
    }

    public function create(CreateDealerConfigurationLeadPipelinesRequest $request): Response
    {
        return Inertia::render('GuardDealer/DealerConfiguration/LeadPipelines/Create', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $request->user('dealer')->dealer->id,
                'name' => $request->user('dealer')->dealer->name,
            ],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.lead-pipelines.index')),
        ]);
    }

    public function store(StoreDealerConfigurationLeadPipelinesRequest $request): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        $data = $request->safe()->except(['return_to']);

        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        $dealer->pipelines()->create($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.lead-pipelines.index')))
            ->with('success', 'Lead pipeline created.');
    }

    public function edit(EditDealerConfigurationLeadPipelinesRequest $request, LeadPipeline $leadPipeline): Response
    {
        return Inertia::render('GuardDealer/DealerConfiguration/LeadPipelines/Edit', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $request->user('dealer')->dealer->id,
                'name' => $request->user('dealer')->dealer->name,
            ],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.lead-pipelines.index')),
            'data' => [
                'id' => $leadPipeline->id,
                'name' => $leadPipeline->name,
                'is_default' => (bool) $leadPipeline->is_default,
            ],
        ]);
    }

    public function update(UpdateDealerConfigurationLeadPipelinesRequest $request, LeadPipeline $leadPipeline): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        $data = $request->safe()->except(['return_to']);

        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        $leadPipeline->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.lead-pipelines.index')))
            ->with('success', 'Lead pipeline updated.');
    }

    public function destroy(DestroyDealerConfigurationLeadPipelinesRequest $request, LeadPipeline $leadPipeline): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $leadPipeline->delete();

        return back()->with('success', 'Lead pipeline deleted.');
    }
}
