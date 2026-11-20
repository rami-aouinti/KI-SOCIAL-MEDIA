<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_handoff_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_version_id')
                ->constrained('content_versions')
                ->cascadeOnDelete();
            $table->foreignId('content_version_approval_id')
                ->constrained('content_version_approvals')
                ->restrictOnDelete();
            $table->string('idempotency_key', 150)->unique();
            $table->json('payload');
            $table->string('status', 20)->default('handed_off');
            $table->timestamp('handed_off_at');
            $table->timestamps();

            $table->unique('content_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_handoff_records');
    }
};
