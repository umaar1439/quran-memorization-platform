<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول عالمي (Reference Data) — لا organization_id، مشترك بين كل المستأجرين.
     */
    public function up(): void
    {
        Schema::create('surahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique(); // 1-114
            $table->string('name_arabic');
            $table->string('name_english');
            $table->string('revelation_type'); // meccan|medinan
            $table->unsignedSmallInteger('ayah_count');
            $table->unsignedSmallInteger('page_start')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surahs');
    }
};
