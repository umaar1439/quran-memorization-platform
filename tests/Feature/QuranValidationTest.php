<?php

use App\Rules\ValidAyahRange;
use App\Rules\ValidSurahNumber;
use Database\Seeders\SurahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurahSeeder::class);
});

test('surah number 115 is rejected', function () {
    $validator = Validator::make(
        ['surah_number' => 115],
        ['surah_number' => [new ValidSurahNumber]],
    );

    expect($validator->fails())->toBeTrue();
});

test('surah number 0 is rejected', function () {
    $validator = Validator::make(
        ['surah_number' => 0],
        ['surah_number' => [new ValidSurahNumber]],
    );

    expect($validator->fails())->toBeTrue();
});

test('a valid surah number between 1 and 114 passes', function () {
    $validator = Validator::make(
        ['surah_number' => 2],
        ['surah_number' => [new ValidSurahNumber]],
    );

    expect($validator->fails())->toBeFalse();
});

test('ayah_from greater than ayah_to is rejected', function () {
    $validator = Validator::make(
        ['ayah_to' => 2],
        ['ayah_to' => [new ValidAyahRange(1, 5)]],
    );

    expect($validator->fails())->toBeTrue();
});

test('ayah greater than the surah ayah count is rejected', function () {
    // سورة الفاتحة (رقم 1) تحتوي 7 آيات فقط.
    $validator = Validator::make(
        ['ayah_to' => 10],
        ['ayah_to' => [new ValidAyahRange(1, 1)]],
    );

    expect($validator->fails())->toBeTrue();
});

test('a valid ayah range within the surah bounds passes', function () {
    $validator = Validator::make(
        ['ayah_to' => 7],
        ['ayah_to' => [new ValidAyahRange(1, 1)]],
    );

    expect($validator->fails())->toBeFalse();
});
