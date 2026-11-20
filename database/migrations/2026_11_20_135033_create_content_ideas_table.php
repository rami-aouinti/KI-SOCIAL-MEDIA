<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_ideas', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('weekly_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title', 150);
            $table->text('topic');
            $table->string('target_audience', 150);

            $table->json('channels');
            $table->string('format', 50);

            $table->string('priority_type', 20);
            $table->string('responsible_person', 30);

            $table->date('planned_at');
            $table->unsignedInteger('estimated_minutes');

            $table->string('status', 30)->default('planned');

            $table->timestamps();

            $table->index(['weekly_plan_id', 'priority_type']);
            $table->index(['responsible_person', 'planned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_ideas');
    }
};
