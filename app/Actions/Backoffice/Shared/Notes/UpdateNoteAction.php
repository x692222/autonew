<?php

namespace App\Actions\Backoffice\Shared\Notes;

use App\Models\Note;

class UpdateNoteAction
{
    public function execute(Note $note, array $data): void
    {
        $note->update($data);
    }
}
