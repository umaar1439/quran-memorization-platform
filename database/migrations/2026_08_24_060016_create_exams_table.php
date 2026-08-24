<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('mosque_id')
                ->nullable()
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('circle_id')
                ->nullable()
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('title');
            $table->string('exam_type');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('draft'); // draft|scheduled|in_progress|completed|approved
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
