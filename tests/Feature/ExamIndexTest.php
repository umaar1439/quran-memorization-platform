<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/*
 * حماية من الانحدار: قائمة الامتحانات تُفلتر بالحلقة/المسجد وتُرتّب على
 * scheduled_at داخل المستأجر، لذا يجب أن تبقى الفهارس المركبة موجودة.
 */
test('the exams table keeps the composite indexes used by the exam list query', function () {
    $indexes = collect(Schema::getIndexes('exams'))->keyBy('name');

    expect($indexes->keys()->all())->toContain(
        'exams_organization_scheduled_index',
        'exams_circle_scheduled_index',
        'exams_mosque_scheduled_index',
    );

    expect($indexes['exams_organization_scheduled_index']['columns'])->toBe(['organization_id', 'scheduled_at'])
        ->and($indexes['exams_circle_scheduled_index']['columns'])->toBe(['circle_id', 'scheduled_at'])
        ->and($indexes['exams_mosque_scheduled_index']['columns'])->toBe(['mosque_id', 'scheduled_at']);
});
