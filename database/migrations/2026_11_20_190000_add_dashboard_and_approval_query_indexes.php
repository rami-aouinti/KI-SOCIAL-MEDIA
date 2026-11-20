<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_ideas', function (Blueprint $table): void {
            $table->index(
                ['weekly_plan_id', 'planned_at'],
                'content_ideas_plan_planned_at_index',
            );
        });

        Schema::table('content_version_approvals', function (Blueprint $table): void {
            $table->index(
                ['content_version_id', 'decision', 'content_hash', 'reviewed_at'],
                'content_approvals_workflow_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('content_version_approvals', function (Blueprint $table): void {
            $table->dropIndex('content_approvals_workflow_lookup_index');
        });

        Schema::table('content_ideas', function (Blueprint $table): void {
            $table->dropIndex('content_ideas_plan_planned_at_index');
        });
    }
};
