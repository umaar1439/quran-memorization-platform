<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * يضيف notification_preferences (JSON) على users — تفضيلات المستخدم
     * لقنوات الإشعارات (database/mail، وقابلة للتوسّع لاحقاً بـ SMS/WhatsApp)
     * بحسب نوع الإشعار (Phase 13 نقطة 2).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
