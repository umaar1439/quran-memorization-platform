<?php

declare(strict_types=1);

use App\DTOs\SettingEntryData;

test('SettingEntryData maps a validated setting entry', function () {
    $dto = SettingEntryData::fromArray([
        'key' => 'circle_label',
        'value' => 'حلقة',
        'group' => 'labels',
    ]);

    expect($dto->key)->toBe('circle_label')
        ->and($dto->value)->toBe('حلقة')
        ->and($dto->group)->toBe('labels');
});

test('SettingEntryData normalises a missing value and group to null', function () {
    $dto = SettingEntryData::fromArray(['key' => 'date_format']);

    expect($dto->value)->toBeNull()
        ->and($dto->group)->toBeNull();
});
