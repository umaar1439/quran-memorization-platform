<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * غلاف لتحليلات الطالب (مصفوفة محسوبة في Action) لتوحيد شكل الاستجابة
 * عبر نفس عقد API Resources بدلاً من إرجاع مصفوفة خام.
 */
class StudentAnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
