<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            $table->morphs('noteable');          // adds index automatically
            $table->nullableMorphs('author');    // adds index automatically

            $table->text('note');
            $table->boolean('backoffice_only')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // optional extra indices (safe)
            $table->index(['noteable_type', 'noteable_id', 'created_at']);
            $table->index(['backoffice_only']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
