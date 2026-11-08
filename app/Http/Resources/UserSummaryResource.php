<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ملخص مستخدم آمن للعرض داخل الاستجابات — بلا بريد إلكتروني أو أي PII
 * غير لازمة، وبلا كلمة المرور أو التفضيلات (§4 حماية البيانات).
 *
 * @mixin User
 */
class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'locale' => $this->locale,
            'status' => $this->status?->value ?? $this->status,
        ];
    }
}
