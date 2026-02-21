<?php

namespace App\Support\Notes;

use App\Http\Resources\KeyValueOptions\NoteAuthorCollection;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Dealer\DealerUser;
use App\Models\Invoice\Invoice;
use App\Models\Leads\Lead;
use App\Models\Note;
use App\Models\Quotation\Quotation;
use App\Models\Stock\Stock;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class NotesControllerService
{
    public function resolveGuard(): string
    {
        if (auth('dealer')->check()) {
            return 'dealer';
        }

        return 'backoffice';
    }

    public function resolveNoteable(string $noteableType, string $noteableId): Model
    {
        $class = config("notes.noteables.{$noteableType}");

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Unsupported note target.');
        }

        /** @var Model|null $noteable */
        $noteable = $class::query()->find($noteableId);

        if (! $noteable) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $noteable;
    }

    public function authorizeNoteable(Model $noteable, bool $isDealerContext): void
    {
        $dealer = $this->resolveDealerFromNoteable($noteable);

        if ($dealer) {
            if ($isDealerContext) {
                $actor = auth('dealer')->user();
                abort_if(! $actor, Response::HTTP_FORBIDDEN);
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
            abort_if(! $dealer, Response::HTTP_FORBIDDEN);
            Gate::authorize('showNotes', $dealer);
            return;
        }

        if ($noteable instanceof DealerUser) {
            $noteable->loadMissing('dealer:id');
            Gate::authorize('showNotes', $noteable->dealer);
            return;
        }

        if ($noteable instanceof Quotation) {
            if ($isDealerContext) {
                abort(Response::HTTP_FORBIDDEN);
            }

            Gate::authorize('viewAny', Quotation::class);
            return;
        }

        if ($noteable instanceof Invoice) {
            if ($isDealerContext) {
                abort(Response::HTTP_FORBIDDEN);
            }

            Gate::authorize('viewAny', Invoice::class);
            return;
        }

        abort(Response::HTTP_FORBIDDEN);
    }

    public function ensureNoteBelongsToNoteable(Note $note, Model $noteable): void
    {
        if ($note->noteable_type !== get_class($noteable) || (string) $note->noteable_id !== (string) $noteable->getKey()) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function noteableLabel(Model $noteable): string
    {
        if ($noteable instanceof Dealer || $noteable instanceof DealerBranch) {
            return (string) $noteable->name;
        }

        if ($noteable instanceof DealerSalePerson || $noteable instanceof DealerUser) {
            return trim((string) $noteable->firstname . ' ' . (string) $noteable->lastname);
        }

        if ($noteable instanceof Stock || $noteable instanceof Lead) {
            return (string) ($noteable->name ?: $noteable->internal_reference ?: $noteable->getKey());
        }

        if ($noteable instanceof Quotation) {
            return (string) $noteable->quote_identifier;
        }

        if ($noteable instanceof Invoice) {
            return (string) $noteable->invoice_identifier;
        }

        return (string) $noteable->getKey();
    }

    public function authorLabel(Note $note): string
    {
        $author = $note->author;
        if (! $author) {
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

    public function buildAuthorOptions(Model $noteable, bool $isDealerContext): array
    {
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

    public function columns(bool $canManageBackofficeOnly): array
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

    public function resolveDealerFromNoteable(Model $noteable): ?Dealer
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

        if ($noteable instanceof Quotation) {
            $noteable->loadMissing('dealer:id');
            return $noteable->dealer;
        }

        if ($noteable instanceof Invoice) {
            $noteable->loadMissing('dealer:id');
            return $noteable->dealer;
        }

        return null;
    }

    private function authorOptionsForUsers(Collection $users): array
    {
        $sorted = $users
            ->sortBy(function ($user): string {
                $firstname = trim((string) ($user->firstname ?? ''));
                $lastname = trim((string) ($user->lastname ?? ''));
                $fullName = trim($firstname . ' ' . $lastname);

                return mb_strtolower($fullName !== '' ? $fullName : (string) ($user->email ?? $user->id));
            })
            ->values();

        return (new NoteAuthorCollection($sorted))->resolve();
    }
}
