<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspiration_references', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('weekly_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('channel', 30);
            $table->string('source_id', 100);
            $table->text('description');

            $table->json('metrics')->nullable();

            $table->timestamps();

            $table->unique(
                ['weekly_plan_id', 'source_id'],
                'inspiration_plan_source_unique'
            );

            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspiration_references');
    }
};
