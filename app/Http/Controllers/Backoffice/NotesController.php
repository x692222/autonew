<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Leads\Lead;
use App\Models\Note;
use App\Models\Stock\Stock;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class NotesController extends Controller
{
    public function index(Request $request, string $noteableType, string $noteableId): JsonResponse
    {
        $guard = $this->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'author_guard' => ['nullable', 'in:backoffice,dealer'],
            'author_id' => ['nullable', 'uuid'],
            'backoffice_only' => ['nullable', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sortBy' => ['nullable', 'in:created_at,updated_at'],
            'descending' => ['nullable'],
        ]);

        $noteable = $this->resolveNoteable($noteableType, $noteableId);
        $this->authorizeNoteable($noteable, $isDealerContext);

        $sortBy = $filters['sortBy'] ?? 'created_at';
        $direction = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        $canManageBackofficeOnly = !$isDealerContext;
        $query = $noteable->notes()->newQuery()
            ->where('noteable_type', get_class($noteable))
            ->where('noteable_id', $noteable->getKey())
            ->with('author')
            ->when($isDealerContext, fn ($builder) => $builder->where('backoffice_only', false))
            ->when(($filters['search'] ?? null), fn ($builder, $search) => $builder->where('note', 'like', '%' . $search . '%'))
            ->when(!empty($filters['author_id']), fn ($builder) => $builder->where('author_id', $filters['author_id']))
            ->when(
                $canManageBackofficeOnly && array_key_exists('backoffice_only', $filters) && $filters['backoffice_only'] !== null,
                fn ($builder) => $builder->where('backoffice_only', (bool) ((int) $filters['backoffice_only']))
            )
            ->when(
                !$isDealerContext && ($filters['author_guard'] ?? null),
                function ($builder, string $guard) {
                    $authorType = $guard === 'backoffice' ? User::class : DealerUser::class;
                    $builder->where('author_type', $authorType);
                }
            )
            ->orderBy($sortBy, $direction);

        $rowsPerPage = (int) ($filters['rowsPerPage'] ?? 15);
        $records = $query->paginate($rowsPerPage);

        $canModify = true;

        $records->setCollection(
            $records->getCollection()->map(function (Note $note) use ($canModify): array {
                return [
                    'id' => $note->id,
                    'note' => (string) $note->note,
                    'backoffice_only' => (bool) $note->backoffice_only,
                    'author_guard' => $note->author_type === User::class ? 'Backoffice' : 'Dealer',
                    'author_name' => $this->authorLabel($note),
                    'created_at' => optional($note->created_at)?->format('Y-m-d H:i'),
                    'can' => [
                        'edit' => $canModify,
                        'delete' => $canModify,
                    ],
                ];
            })
        );

        return response()->json([
            'noteable' => [
                'type' => $noteableType,
                'id' => (string) $noteable->getKey(),
                'label' => $this->noteableLabel($noteable),
            ],
            'filters' => [
                'search' => $filters['search'] ?? '',
                'author_guard' => $isDealerContext ? '' : ($filters['author_guard'] ?? ''),
                'author_id' => $filters['author_id'] ?? '',
                'backoffice_only' => $canManageBackofficeOnly && array_key_exists('backoffice_only', $filters)
                    ? (string) $filters['backoffice_only']
                    : '',
            ],
            'columns' => $this->columns($canManageBackofficeOnly),
            'records' => $records,
            'authorOptions' => $this->buildAuthorOptions($noteable, $isDealerContext),
            'context' => [
                'guard' => $guard,
                'can_manage_backoffice_only' => $canManageBackofficeOnly,
            ],
        ]);
    }

    public function store(Request $request, string $noteableType, string $noteableId): JsonResponse
    {
        $guard = $this->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $data = $request->validate([
            'note' => ['required', 'string'],
            'backoffice_only' => ['nullable', 'boolean'],
        ]);

        $noteable = $this->resolveNoteable($noteableType, $noteableId);
        $actor = $isDealerContext ? $request->user('dealer') : $request->user('backoffice');

        $this->authorizeNoteable($noteable, $isDealerContext);

        $note = $noteable->notes()->create([
            'note' => $data['note'],
            'backoffice_only' => $isDealerContext ? false : (bool) ($data['backoffice_only'] ?? false),
            'author_type' => $actor ? get_class($actor) : null,
            'author_id' => $actor?->getKey(),
        ]);

        return response()->json(['id' => $note->id], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $noteableType, string $noteableId, Note $note): JsonResponse
    {
        $guard = $this->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $data = $request->validate([
            'note' => ['required', 'string'],
            'backoffice_only' => ['nullable', 'boolean'],
        ]);

        $noteable = $this->resolveNoteable($noteableType, $noteableId);
        $this->authorizeNoteable($noteable, $isDealerContext);
        $this->ensureNoteBelongsToNoteable($note, $noteable);

        if ($isDealerContext && $note->backoffice_only) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $note->update([
            'note' => $data['note'],
            'backoffice_only' => $isDealerContext ? false : (bool) ($data['backoffice_only'] ?? false),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $noteableType, string $noteableId, Note $note): JsonResponse
    {
        $guard = $this->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $noteable = $this->resolveNoteable($noteableType, $noteableId);
        $this->authorizeNoteable($noteable, $isDealerContext);
        $this->ensureNoteBelongsToNoteable($note, $noteable);

        if ($isDealerContext && $note->backoffice_only) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $note->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    private function resolveNoteable(string $noteableType, string $noteableId): Model
    {
        $class = config("notes.noteables.{$noteableType}");

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Unsupported note target.');
        }

        /** @var Model|null $noteable */
        $noteable = $class::query()->find($noteableId);

        if (!$noteable) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $noteable;
    }

    private function authorizeNoteable(Model $noteable, bool $isDealerContext): void
    {
        $dealer = $this->resolveDealerFromNoteable($noteable);

        if ($dealer) {
            if ($isDealerContext) {
                $actor = auth('dealer')->user();
                abort_if(!$actor, Response::HTTP_FORBIDDEN);
                Gate::forUser($actor)->authorize('dealerConfigurationShowNotes', $dealer);
            } else {
                Gate::authorize('showNotes', $dealer);
            }

            return;
        }

        if ($noteable instanceof Dealer) {
            Gate::authorize('showNotes', $noteable);
            return;
        }

        if ($noteable instanceof DealerBranch) {
            $noteable->loadMissing('dealer:id');
            Gate::authorize('showNotes', $noteable->dealer);
            return;
        }

        if ($noteable instanceof DealerSalePerson) {
            $noteable->loadMissing('branch:id,dealer_id');
            $dealer = Dealer::query()->find($noteable->branch?->dealer_id);
            abort_if(!$dealer, Response::HTTP_FORBIDDEN);
            Gate::authorize('showNotes', $dealer);
            return;
        }

        if ($noteable instanceof DealerUser) {
            $noteable->loadMissing('dealer:id');
            Gate::authorize('showNotes', $noteable->dealer);
            return;
        }

        abort(Response::HTTP_FORBIDDEN);
    }

    private function ensureNoteBelongsToNoteable(Note $note, Model $noteable): void
    {
        if ($note->noteable_type !== get_class($noteable) || (string) $note->noteable_id !== (string) $noteable->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function noteableLabel(Model $noteable): string
    {
        if ($noteable instanceof Dealer) {
            return (string) $noteable->name;
        }

        if ($noteable instanceof DealerBranch) {
            return (string) $noteable->name;
        }

        if ($noteable instanceof DealerSalePerson) {
            return trim((string) $noteable->firstname . ' ' . (string) $noteable->lastname);
        }

        if ($noteable instanceof DealerUser) {
            return trim((string) $noteable->firstname . ' ' . (string) $noteable->lastname);
        }

        if ($noteable instanceof Stock) {
            return (string) ($noteable->name ?: $noteable->internal_reference ?: $noteable->getKey());
        }

        if ($noteable instanceof Lead) {
            return (string) ($noteable->name ?: $noteable->internal_reference ?: $noteable->getKey());
        }

        return (string) $noteable->getKey();
    }

    private function authorLabel(Note $note): string
    {
        $author = $note->author;
        if (!$author) {
            return '-';
        }

        $firstname = trim((string) ($author->firstname ?? ''));
        $lastname = trim((string) ($author->lastname ?? ''));
        $fullName = trim($firstname . ' ' . $lastname);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($author->email ?? '-');
    }

    private function buildAuthorOptions(Model $noteable): array
    {
        $isDealerContext = $this->resolveGuard() === 'dealer';

        $authors = $noteable->notes()
            ->newQuery()
            ->select(['author_type', 'author_id'])
            ->whereNotNull('author_type')
            ->whereNotNull('author_id')
            ->when($isDealerContext, fn ($builder) => $builder->where('backoffice_only', false))
            ->distinct()
            ->get();

        $backofficeIds = $authors
            ->where('author_type', User::class)
            ->pluck('author_id')
            ->unique()
            ->values();

        $dealerIds = $authors
            ->where('author_type', DealerUser::class)
            ->pluck('author_id')
            ->unique()
            ->values();

        return [
            'backoffice' => $this->authorOptionsForUsers(User::query()->whereIn('id', $backofficeIds)->get()),
            'dealer' => $this->authorOptionsForUsers(DealerUser::query()->whereIn('id', $dealerIds)->get()),
        ];
    }

    private function authorOptionsForUsers(Collection $users): array
    {
        return $users
            ->map(function ($user): array {
                $firstname = trim((string) ($user->firstname ?? ''));
                $lastname = trim((string) ($user->lastname ?? ''));
                $fullName = trim($firstname . ' ' . $lastname);

                return [
                    'value' => $user->id,
                    'label' => $fullName !== '' ? $fullName : (string) ($user->email ?? $user->id),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function resolveGuard(): string
    {
        if (auth('dealer')->check()) {
            return 'dealer';
        }

        return 'backoffice';
    }

    private function columns(bool $canManageBackofficeOnly): array
    {
        $columns = [
            ['name' => 'created_at', 'label' => 'Created At', 'sortable' => true, 'align' => 'left', 'field' => 'created_at', 'numeric' => false],
            ['name' => 'author_guard', 'label' => 'Posted By Guard', 'sortable' => false, 'align' => 'left', 'field' => 'author_guard', 'numeric' => false],
            ['name' => 'author_name', 'label' => 'Posted By', 'sortable' => false, 'align' => 'left', 'field' => 'author_name', 'numeric' => false],
            ['name' => 'note', 'label' => 'Note', 'sortable' => false, 'align' => 'left', 'field' => 'note', 'numeric' => false],
        ];

        if ($canManageBackofficeOnly) {
            array_splice($columns, 3, 0, [[
                'name' => 'backoffice_only',
                'label' => 'Backoffice Only',
                'sortable' => false,
                'align' => 'center',
                'field' => 'backoffice_only',
                'numeric' => false,
            ]]);
        }

        return $columns;
    }

    private function resolveDealerFromNoteable(Model $noteable): ?Dealer
    {
        if ($noteable instanceof Dealer) {
            return $noteable;
        }

        if ($noteable instanceof DealerBranch) {
            $noteable->loadMissing('dealer:id');
            return $noteable->dealer;
        }

        if ($noteable instanceof DealerSalePerson) {
            $noteable->loadMissing('branch:id,dealer_id', 'branch.dealer:id');
            return $noteable->branch?->dealer;
        }

        if ($noteable instanceof DealerUser) {
            $noteable->loadMissing('dealer:id');
            return $noteable->dealer;
        }

        if ($noteable instanceof Stock) {
            $noteable->loadMissing('branch:id,dealer_id', 'branch.dealer:id');
            return $noteable->branch?->dealer;
        }

        if ($noteable instanceof Lead) {
            $noteable->loadMissing('dealer:id');
            return $noteable->dealer;
        }

        return null;
    }
}
