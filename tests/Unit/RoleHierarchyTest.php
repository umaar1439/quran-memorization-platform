<?php

declare(strict_types=1);

use App\Support\RoleHierarchy;

test('RoleHierarchy ranks every seeded role and fails closed for unknown roles', function () {
    $hierarchy = new RoleHierarchy;

    expect($hierarchy->rankOf('super-admin'))->toBe(50)
        ->and($hierarchy->rankOf('org-admin'))->toBe(40)
        ->and($hierarchy->rankOf('supervisor'))->toBe(30)
        ->and($hierarchy->rankOf('teacher'))->toBe(20)
        ->and($hierarchy->rankOf('student'))->toBe(10)
        ->and($hierarchy->rankOf('parent'))->toBe(10)
        ->and($hierarchy->rankOf('unknown-role'))->toBe(0);
});

test('RoleHierarchy resolves the highest rank a user holds', function () {
    $hierarchy = new RoleHierarchy;

    expect($hierarchy->highestRank(['teacher']))->toBe(20)
        ->and($hierarchy->highestRank(['teacher', 'org-admin']))->toBe(40)
        ->and($hierarchy->highestRank([]))->toBe(0);
});

test('an actor can assign roles at or below their own rank but never above it', function () {
    $hierarchy = new RoleHierarchy;

    expect($hierarchy->canAssignRank(40, 'org-admin'))->toBeTrue()
        ->and($hierarchy->canAssignRank(40, 'supervisor'))->toBeTrue()
        ->and($hierarchy->canAssignRank(40, 'student'))->toBeTrue()
        // رتبة أعلى من الفاعل = تصعيد صلاحيات.
        ->and($hierarchy->canAssignRank(40, 'super-admin'))->toBeFalse()
        ->and($hierarchy->canAssignRank(30, 'org-admin'))->toBeFalse()
        ->and($hierarchy->canAssignRank(20, 'supervisor'))->toBeFalse()
        // رتبة غير معروفة (0) لا تمنح شيئاً.
        ->and($hierarchy->canAssignRank(0, 'parent'))->toBeFalse();
});

test('only a super-admin rank can assign the super-admin role', function () {
    $hierarchy = new RoleHierarchy;

    expect($hierarchy->canAssignRank(50, 'super-admin'))->toBeTrue()
        ->and($hierarchy->canAssignRank(40, 'super-admin'))->toBeFalse();
});

test('management requires a strictly higher rank, except for super-admin', function () {
    $hierarchy = new RoleHierarchy;

    expect($hierarchy->canManageRank(40, 30))->toBeTrue()
        ->and($hierarchy->canManageRank(50, 40))->toBeTrue()
        // النظير لا يُدار (منع الاستيلاء على حساب مدير آخر بنفس الرتبة).
        ->and($hierarchy->canManageRank(40, 40))->toBeFalse()
        ->and($hierarchy->canManageRank(30, 40))->toBeFalse()
        // super-admin يدير أي رتبة.
        ->and($hierarchy->canManageRank(50, 50))->toBeTrue()
        ->and($hierarchy->canManageRank(50, 0))->toBeTrue();
});
