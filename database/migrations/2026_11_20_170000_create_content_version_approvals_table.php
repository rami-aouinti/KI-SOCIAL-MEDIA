<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_version_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_version_id')
                ->constrained('content_versions')
                ->cascadeOnDelete();
            $table->string('reviewer', 120);
            $table->string('decision', 20);
            $table->char('content_hash', 64);
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['content_version_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_version_approvals');
    }
};
