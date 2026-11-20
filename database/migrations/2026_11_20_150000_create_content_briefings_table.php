<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_briefings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('content_idea_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->text('hook');
            $table->longText('body');
            $table->longText('filming_plan');
            $table->json('on_screen_text');
            $table->text('cover_idea');
            $table->text('cta_text');
            $table->string('appointment_url', 500);
            $table->string('status', 20)->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_briefings');
    }
};
