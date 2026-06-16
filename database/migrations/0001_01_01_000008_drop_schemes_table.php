<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The scheme-monitoring module was removed; drop its table where it exists.
    public function up(): void
    {
        Schema::dropIfExists('schemes');
    }

    public function down(): void
    {
        // No-op: the module (and its create migration) no longer exist.
    }
};
