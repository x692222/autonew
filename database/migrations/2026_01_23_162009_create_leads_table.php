<?php

use App\Models\Leads\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Ownership / scoping
            $table->foreignId('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('dealer_branches')->restrictOnDelete();
            $table->foreignId('assigned_to_dealer_user_id')->nullable()->constrained('dealer_users')->restrictOnDelete();
            $table->foreignId('pipeline_id')->nullable()->constrained('lead_pipelines')->restrictOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('lead_stages')->restrictOnDelete();

            // ai mode
            $table->boolean('ai_mode')->default(false);
            $table->boolean('auto_capture_by_ai')->default(false);
            $table->boolean('auto_update_lead_context')->default(false);
            $table->longText('lead_context')->nullable();

            // Contact
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_no', 50)->nullable();

            // Meta
            $table->enum('source', Lead::LEAD_SOURCES)->nullable()->index();    // e.g. website_form, whatsapp, email
            $table->enum('status', Lead::LEAD_STATUSES)->default(Lead::LEAD_STATUS_OPEN)->index();     // open/closed/etc

            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
