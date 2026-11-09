<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * مجموعة الصلاحيات المُصادَق عليها لدور واحد، تُبنى من
 * SyncRolePermissionsRequest::toData() وتُمرَّر إلى SyncRolePermissionsAction.
 *
 * التطبيع (إزالة التكرار) يحدث هنا مرة واحدة بدل تكراره في الـ Request
 * والـ Action معاً.
 */
final readonly class RolePermissionData
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $permissions = array_map(
            static fn (mixed $name): string => (string) $name,
            (array) ($data['permissions'] ?? []),
        );

        return new self(array_values(array_unique($permissions)));
    }
}
