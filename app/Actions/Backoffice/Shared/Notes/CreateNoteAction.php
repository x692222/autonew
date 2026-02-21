<?php

namespace App\Actions\Backoffice\Shared\Notes;

use App\Models\Note;
use Illuminate\Database\Eloquent\Model;

class CreateNoteAction
{
    public function execute(Model $noteable, array $data): Note
    {
        return $noteable->notes()->create($data);
    }
}
