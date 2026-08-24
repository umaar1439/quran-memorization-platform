<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول عالمي (Reference Data) — لا organization_id.
     */
    public function up(): void
    {
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')
                ->constrained('surahs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('number_in_surah');
            $table->unsignedSmallInteger('number_global')->unique();
            $table->unsignedTinyInteger('juz_number');
            $table->unsignedTinyInteger('hizb_number')->nullable();
            $table->unsignedSmallInteger('page_number')->nullable();

            $table->unique(['surah_id', 'number_in_surah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayahs');
    }
};
