<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained('short_links')->cascadeOnDelete();
            $table->string('visitor_id')->nullable()->index();
            $table->boolean('is_unique')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->string('referer')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_link_clicks');
    }
};
