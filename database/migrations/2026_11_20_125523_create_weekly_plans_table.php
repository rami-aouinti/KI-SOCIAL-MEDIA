<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')
                ->comment('Monday of the editorial week');

            $table->string('seasonal_occasion')->nullable();

            $table->json('input_data');

            $table->json('capacity_limits');

            $table->string('status', 30)->default('draft');

            $table->timestamps();

            $table->unique('week_start');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_plans');
    }
};
