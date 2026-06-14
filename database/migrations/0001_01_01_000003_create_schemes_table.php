<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('department')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget_allocated', 16, 2)->default(0);
            $table->decimal('budget_disbursed', 16, 2)->default(0);
            $table->unsignedBigInteger('target_beneficiaries')->default(0);
            $table->unsignedBigInteger('enrolled_beneficiaries')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes');
    }
};
