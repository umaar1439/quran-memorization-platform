<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('from_mosque_id')
                ->nullable()
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('to_mosque_id')
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('from_circle_id')
                ->nullable()
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('to_circle_id')
                ->nullable()
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('transferred_by_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
