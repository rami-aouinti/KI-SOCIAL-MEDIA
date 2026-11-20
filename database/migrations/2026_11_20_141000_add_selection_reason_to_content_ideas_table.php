<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_ideas', function (Blueprint $table): void {
            $table->text('selection_reason')->nullable()->after('priority_type');
        });
    }

    public function down(): void
    {
        Schema::table('content_ideas', function (Blueprint $table): void {
            $table->dropColumn('selection_reason');
        });
    }
};
