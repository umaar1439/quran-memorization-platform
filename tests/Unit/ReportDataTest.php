<?php

declare(strict_types=1);

use App\DTOs\ReportData;

test('ReportData maps the report type and optional parameters', function () {
    $withParameters = ReportData::fromArray([
        'report_type' => 'attendance_summary',
        'parameters' => ['from' => '2026-01-01'],
    ]);

    $withoutParameters = ReportData::fromArray(['report_type' => 'students_summary']);

    expect($withParameters->reportType)->toBe('attendance_summary')
        ->and($withParameters->parameters)->toBe(['from' => '2026-01-01'])
        ->and($withoutParameters->parameters)->toBeNull();
});

test('ReportData keeps an explicitly empty parameters array', function () {
    $dto = ReportData::fromArray(['report_type' => 'students_summary', 'parameters' => []]);

    expect($dto->parameters)->toBe([]);
});
