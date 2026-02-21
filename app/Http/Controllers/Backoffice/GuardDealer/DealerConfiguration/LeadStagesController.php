<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Actions\Backoffice\Shared\LeadStages\CreateLeadStageAction;
use App\Actions\Backoffice\Shared\LeadStages\UpdateLeadStageAction;
use App\Actions\Backoffice\Shared\LeadStages\DeleteLeadStageAction;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\CreateDealerConfigurationLeadStagesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\DestroyDealerConfigurationLeadStagesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\EditDealerConfigurationLeadStagesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\IndexDealerConfigurationLeadStagesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\StoreDealerConfigurationLeadStagesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages\UpdateDealerConfigurationLeadStagesRequest;
use App\Http\Resources\Backoffice\Shared\Leads\LeadStageIndexResource;
use App\Models\Leads\LeadStage;
use App\Support\Options\DealerOptions;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadStagesController extends Controller
{
    public function index(IndexDealerConfigurationLeadStagesRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $filters = $request->validated();

        $query = LeadStage::query()
            ->select(['id', 'pipeline_id', 'name', 'sort_order', 'is_terminal', 'is_won', 'is_lost'])
            ->whereIn('pipeline_id', $dealer->pipelines()->select('id'))
            ->with('pipeline:id,name')
            ->filterSearch($filters['search'] ?? null, ['name'])
            ->when($filters['pipeline_id'] ?? null, fn ($builder, $pipelineId) => $builder->where('pipeline_id', $pipelineId))
            ->when(isset($filters['is_terminal']) && $filters['is_terminal'] !== null, fn ($builder) => $builder->where('is_terminal', (bool) $filters['is_terminal']))
            ->when(isset($filters['is_won']) && $filters['is_won'] !== null, fn ($builder) => $builder->where('is_won', (bool) $filters['is_won']))
            ->when(isset($filters['is_lost']) && $filters['is_lost'] !== null, fn ($builder) => $builder->where('is_lost', (bool) $filters['is_lost']));

        $sortBy = $filters['sortBy'] ?? 'sort_order';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'pipeline' => $query->orderByRaw("(select name from lead_pipelines where lead_pipelines.id = lead_stages.pipeline_id limit 1) {$direction}"),
            'name' => $query->orderBy('name', $direction),
            'is_terminal' => $query->orderBy('is_terminal', $direction),
            'is_won' => $query->orderBy('is_won', $direction),
            'is_lost' => $query->orderBy('is_lost', $direction),
            default => $query->orderBy('sort_order', $direction),
        };

        $records = $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);

        $request->attributes->set('lead_stage_context', [
            'can_edit' => $actor?->hasPermissionTo('editPipelineStages', 'dealer') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deletePipelineStages', 'dealer') ?? false,
        ]);

        $records->setCollection(
            $records->getCollection()->map(fn (LeadStage $stage) => (new LeadStageIndexResource($stage))->toArray($request))
        );

        $columns = DataTableColumnBuilder::make(
            keys: ['pipeline', 'name', 'sort_order', 'is_terminal', 'is_won', 'is_lost'],
            allSortable: true,
            numericKeys: ['sort_order'],
            alignOverrides: ['is_terminal' => 'center', 'is_won' => 'center', 'is_lost' => 'center']
        );

        return Inertia::render('GuardDealer/DealerConfiguration/LeadStages/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
        ]);
    }

    public function create(CreateDealerConfigurationLeadStagesRequest $request): Response
    {
        $dealer = $request->user('dealer')->dealer;

        return Inertia::render('GuardDealer/DealerConfiguration/LeadStages/Create', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.lead-stages.index')),
            'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
        ]);
    }

    public function store(StoreDealerConfigurationLeadStagesRequest $request, CreateLeadStageAction $action): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        $action->execute($dealer, $request->safe()->except(['return_to']));

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.lead-stages.index')))
            ->with('success', 'Lead stage created.');
    }

    public function edit(EditDealerConfigurationLeadStagesRequest $request, LeadStage $leadStage): Response
    {
        $dealer = $request->user('dealer')->dealer;

        return Inertia::render('GuardDealer/DealerConfiguration/LeadStages/Edit', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.lead-stages.index')),
            'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
            'data' => [
                'id' => $leadStage->id,
                'pipeline_id' => $leadStage->pipeline_id,
                'name' => $leadStage->name,
                'sort_order' => $leadStage->sort_order,
                'is_terminal' => (bool) $leadStage->is_terminal,
                'is_won' => (bool) $leadStage->is_won,
                'is_lost' => (bool) $leadStage->is_lost,
            ],
        ]);
    }

    public function update(UpdateDealerConfigurationLeadStagesRequest $request, LeadStage $leadStage, UpdateLeadStageAction $action): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        $action->execute($dealer, $leadStage, $request->safe()->except(['return_to']));

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.lead-stages.index')))
            ->with('success', 'Lead stage updated.');
    }

    public function destroy(DestroyDealerConfigurationLeadStagesRequest $request, LeadStage $leadStage, DeleteLeadStageAction $action): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($dealer, $leadStage);

        return back()->with('success', 'Lead stage deleted.');
    }
}
