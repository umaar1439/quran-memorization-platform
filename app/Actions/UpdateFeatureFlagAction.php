<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureFlag;
use App\Models\User;
use App\Support\FeatureFlagCache;
use Illuminate\Support\Facades\DB;

/**
 * تبديل علم ميزة مع إبطال الذاكرة فوراً (وإلا بقي التغيير غير مرئي حتى TTL).
 *
 * يستخدم updateOrCreate حتى ينشئ تجاوزاً خاصاً بالمنظمة عند غيابه، بدل
 * الاعتماد على وجود سجل مسبق من الـ Seeder.
 */
class UpdateFeatureFlagAction
{
    public function __construct(
        private readonly FeatureFlagCache $flags,
    ) {}

    public function execute(FeatureFlag $flag, bool $enabled, User $actor): FeatureFlag
    {
        $updated = DB::transaction(function () use ($flag, $enabled, $actor): FeatureFlag {
            $flag->update([
                'enabled' => $enabled,
                'updated_by' => $actor->id,
            ]);

            return $flag->refresh();
        });

        $this->flags->forget($updated->organization_id);
        $this->flags->forget($actor->organization_id);

        return $updated;
    }
}
