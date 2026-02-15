<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\CreateDealerLeadStagesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\DestroyDealerLeadStagesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\EditDealerLeadStagesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\IndexDealerLeadStagesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\StoreDealerLeadStagesRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages\UpdateDealerLeadStagesRequest;
use App\Http\Resources\Backoffice\Leads\LeadStageIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadStage;
use App\Support\Options\DealerOptions;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadStagesController extends Controller
{
    public function index(IndexDealerLeadStagesRequest $request, Dealer $dealer): Response
    {
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
        $actor = $request->user('backoffice');

        $request->attributes->set('lead_stage_context', [
            'can_edit' => $actor?->hasPermissionTo('editDealershipPipelineStages', 'backoffice') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deleteDealershipPipelineStages', 'backoffice') ?? false,
        ]);

        $records->setCollection(
            $records->getCollection()->map(fn (LeadStage $stage) => (new LeadStageIndexResource($stage))->toArray($request))
        );

        $columns = collect([
            'pipeline',
            'name',
            'sort_order',
            'is_terminal',
            'is_won',
            'is_lost',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => true,
            'align' => in_array($key, ['is_terminal', 'is_won', 'is_lost'], true)
                ? 'center'
                : (Str::endsWith($key, '_count') || in_array($key, ['sort_order'], true) ? 'right' : 'left'),
            'field' => $key,
            'numeric' => in_array($key, ['sort_order'], true),
        ])->values()->all();

        return Inertia::render('DealerManagement/Dealers/Tabs/LeadStages', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'lead-stages',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
        ]);
    }

    public function create(CreateDealerLeadStagesRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('DealerManagement/Dealers/LeadStages/Create', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.lead-stages.index', $dealer->id)),
            'pipelines' => DealerOptions::pipelinesList((string) $dealer->id)->resolve(),
        ]);
    }

    public function store(StoreDealerLeadStagesRequest $request, Dealer $dealer): RedirectResponse
    {
        LeadStage::query()->create($request->safe()->except(['return_to']));

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-stages.index', $dealer->id)))
            ->with('success', 'Lead stage created.');
    }

    public function edit(EditDealerLeadStagesRequest $request, Dealer $dealer, LeadStage $leadStage): Response
    {
        return Inertia::render('DealerManagement/Dealers/LeadStages/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.lead-stages.index', $dealer->id)),
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

    public function update(UpdateDealerLeadStagesRequest $request, Dealer $dealer, LeadStage $leadStage): RedirectResponse
    {
        $leadStage->update($request->safe()->except(['return_to']));

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.lead-stages.index', $dealer->id)))
            ->with('success', 'Lead stage updated.');
    }

    public function destroy(DestroyDealerLeadStagesRequest $request, Dealer $dealer, LeadStage $leadStage): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $leadStage->delete();

        return back()->with('success', 'Lead stage deleted.');
    }
}
