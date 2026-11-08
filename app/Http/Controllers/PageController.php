<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * الصفحات الثابتة (Welcome/Dashboard) — في Controller بدل Closure داخل
 * ملف المسارات حتى يعمل `php artisan route:cache` في الإنتاج.
 */
class PageController extends Controller
{
    public function welcome(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
        ]);
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard');
    }
}
