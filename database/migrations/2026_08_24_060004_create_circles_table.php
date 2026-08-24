<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('mosque_id')
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('name');
            $table->string('level')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('active'); // active|inactive
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'mosque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
