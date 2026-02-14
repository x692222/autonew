<?php

use App\Models\Messaging\WhatsappTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

// @todo maak alle migrations bigintiger
            $table->foreignUuid('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->foreignUuid('provider_id')->constrained('system_whatsapp_providers')->restrictOnDelete();

            // Meta template unique identifier (Graph returns "id")
            $table->string('provider_template_id')->nullable();

            // Common send identifiers
            $table->string('name');        // template name
            $table->string('language');    // e.g. en_US

            // Meta metadata
            $table->string('category')->nullable(); // marketing / utility / authentication etc.
            $table->enum('status', WhatsappTemplate::STATUS_OPTIONS)->default(WhatsappTemplate::STATUS_PENDING)->index();   // APPROVED / REJECTED / etc.

            // Full template structure (header/body/footer/buttons/examples)
            $table->json('components')->nullable(); // Meta returns "components" array :contentReference[oaicite:1]{index=1}

            // Convenience: human readable body text for quick listing/searching
            $table->longText('body')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Uniqueness per dealer:
            $table->unique(['dealer_id', 'provider_template_id']);
            $table->unique(['dealer_id', 'name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};

