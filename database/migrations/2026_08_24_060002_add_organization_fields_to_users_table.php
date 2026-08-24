<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تعديل جدول users الموجود من Breeze:
     * organization_id nullable (لأجل Super Admin العابر للمنظمات فقط)،
     * بيانات ملف شخصي أساسية، وحذف ناعم.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('locale', 10)->default('ar')->after('phone');
            $table->string('status')->default('active')->after('locale'); // active|inactive|suspended
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['phone', 'locale', 'status', 'last_login_at', 'deleted_at']);
        });
    }
};
