<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('mosque_id')
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('circle_id')
                ->nullable()
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('enrollment_number')->unique();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('current_juz')->nullable();
            $table->string('status')->default('active'); // active|transferred|graduated|withdrawn
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'mosque_id']);
            $table->index(['organization_id', 'circle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
