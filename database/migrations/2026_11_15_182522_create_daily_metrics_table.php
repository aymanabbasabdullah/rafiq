<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregated daily counters (ADR-014). No visitor identifiers, IPs, user agents,
 * answers or message text are ever stored here.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric', 64);
            $table->string('dimension', 64)->default('');
            $table->unsignedBigInteger('value')->default(0);

            $table->unique(['date', 'metric', 'dimension']);
            $table->index(['metric', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
