<?php

namespace App\Actions\Backoffice\Shared\Notes;

use App\Models\Note;

class DeleteNoteAction
{
    public function execute(Note $note): void
    {
        $note->delete();
    }
}
