<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('content_briefing_id')
                ->constrained('content_briefings')
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->string('media_reference', 500)->nullable();
            $table->json('channels');
            $table->date('scheduled_at');
            $table->char('content_hash', 64);
            $table->string('status', 20)->default('draft');

            $table->timestamps();

            $table->unique(
                ['content_briefing_id', 'version_number'],
                'content_version_briefing_number_unique'
            );
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
