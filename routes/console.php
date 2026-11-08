<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| تُعرَّف هنا لا في Kernel. withoutOverlapping يمنع تشغيل نسختين متزامنتين،
| و onOneServer يضمن التنفيذ على خادم واحد عند تشغيل أكثر من نسخة.
*/

// حذف توكنات Sanctum المنتهية (تنظيف أمني دوري).
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

// حذف مهام الطابور الفاشلة القديمة لمنع تضخّم جدول failed_jobs.
Schedule::command('queue:prune-failed --hours=168')
    ->weekly()
    ->withoutOverlapping()
    ->onOneServer();

// حذف دفعات الطابور المنتهية (queue batching).
Schedule::command('queue:prune-batches --hours=48')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();
