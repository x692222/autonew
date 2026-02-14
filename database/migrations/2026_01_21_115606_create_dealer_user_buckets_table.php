<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_user_buckets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('name', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('dealer_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_user_buckets');
    }
};
