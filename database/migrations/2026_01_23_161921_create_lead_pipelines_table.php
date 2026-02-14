<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_pipelines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'deleted_at']);
            $table->index(['dealer_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_pipelines');
    }
};
