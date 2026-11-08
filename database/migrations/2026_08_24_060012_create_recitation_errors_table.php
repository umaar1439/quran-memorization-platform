<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recitation_id')
                ->constrained('recitations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('error_type'); // memorization|tajweed|pronunciation|tashkeel
            $table->unsignedInteger('count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_errors');
    }
};
