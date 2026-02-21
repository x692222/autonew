<?php

namespace App\Http\Controllers\Backoffice\Shared;

use App\Actions\Backoffice\Shared\Notes\CreateNoteAction;
use App\Actions\Backoffice\Shared\Notes\DeleteNoteAction;
use App\Actions\Backoffice\Shared\Notes\UpdateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Notes\IndexNotesRequest;
use App\Http\Requests\Backoffice\Shared\Notes\UpsertNoteRequest;
use App\Models\Dealer\DealerUser;
use App\Models\Note;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Notes\NotesControllerService;
use Symfony\Component\HttpFoundation\Response;

class NotesController extends Controller
{
    public function __construct(
        private readonly NotesControllerService $notesService,
        private readonly CreateNoteAction $createNoteAction,
        private readonly UpdateNoteAction $updateNoteAction,
        private readonly DeleteNoteAction $deleteNoteAction,
    ) {}

    public function index(IndexNotesRequest $request, string $noteableType, string $noteableId): JsonResponse
    {
        $guard = $this->notesService->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $filters = $request->validated();

        $noteable = $this->notesService->resolveNoteable($noteableType, $noteableId);
        $this->notesService->authorizeNoteable(noteable: $noteable, isDealerContext: $isDealerContext);

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
                    'author_name' => $this->notesService->authorLabel($note),
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
                'label' => $this->notesService->noteableLabel($noteable),
            ],
            'filters' => [
                'search' => $filters['search'] ?? '',
                'author_guard' => $isDealerContext ? '' : ($filters['author_guard'] ?? ''),
                'author_id' => $filters['author_id'] ?? '',
                'backoffice_only' => $canManageBackofficeOnly && array_key_exists('backoffice_only', $filters)
                    ? (string) $filters['backoffice_only']
                    : '',
            ],
            'columns' => $this->notesService->columns(canManageBackofficeOnly: $canManageBackofficeOnly),
            'records' => $records,
            'authorOptions' => $this->notesService->buildAuthorOptions(noteable: $noteable, isDealerContext: $isDealerContext),
            'context' => [
                'guard' => $guard,
                'can_manage_backoffice_only' => $canManageBackofficeOnly,
            ],
        ]);
    }

    public function store(UpsertNoteRequest $request, string $noteableType, string $noteableId): JsonResponse
    {
        $guard = $this->notesService->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $data = $request->validated();

        $noteable = $this->notesService->resolveNoteable($noteableType, $noteableId);
        $actor = $isDealerContext ? $request->user('dealer') : $request->user('backoffice');

        $this->notesService->authorizeNoteable(noteable: $noteable, isDealerContext: $isDealerContext);

        $note = $this->createNoteAction->execute($noteable, [
            'note' => $data['note'],
            'backoffice_only' => $isDealerContext ? false : (bool) ($data['backoffice_only'] ?? false),
            'author_type' => $actor ? get_class($actor) : null,
            'author_id' => $actor?->getKey(),
        ]);

        return response()->json(['id' => $note->id], Response::HTTP_CREATED);
    }

    public function update(UpsertNoteRequest $request, string $noteableType, string $noteableId, Note $note): JsonResponse
    {
        $guard = $this->notesService->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $data = $request->validated();

        $noteable = $this->notesService->resolveNoteable($noteableType, $noteableId);
        $this->notesService->authorizeNoteable(noteable: $noteable, isDealerContext: $isDealerContext);
        $this->notesService->ensureNoteBelongsToNoteable(note: $note, noteable: $noteable);

        if ($isDealerContext && $note->backoffice_only) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->updateNoteAction->execute($note, [
            'note' => $data['note'],
            'backoffice_only' => $isDealerContext ? false : (bool) ($data['backoffice_only'] ?? false),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $noteableType, string $noteableId, Note $note): JsonResponse
    {
        $guard = $this->notesService->resolveGuard();
        $isDealerContext = $guard === 'dealer';

        $noteable = $this->notesService->resolveNoteable($noteableType, $noteableId);
        $this->notesService->authorizeNoteable(noteable: $noteable, isDealerContext: $isDealerContext);
        $this->notesService->ensureNoteBelongsToNoteable(note: $note, noteable: $noteable);

        if ($isDealerContext && $note->backoffice_only) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->deleteNoteAction->execute($note);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
