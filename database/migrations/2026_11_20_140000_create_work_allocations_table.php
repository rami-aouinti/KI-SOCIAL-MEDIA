<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_allocations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('content_idea_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('person_key', 30);
            $table->string('work_type', 30);
            $table->unsignedSmallInteger('estimated_minutes');

            $table->timestamps();

            $table->unique(
                ['content_idea_id', 'person_key', 'work_type'],
                'work_allocation_idea_person_type_unique'
            );
            $table->index(['person_key', 'work_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_allocations');
    }
};
