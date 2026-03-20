<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Rules\UniqueArabicWithSpecialCharacters;
use GoodMaven\Arabicable\Traits\Arabicable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

if (! class_exists('ArabicableTestNote')) {
    class ArabicableTestNote extends Model
    {
        use Arabicable;

        protected $table = 'arabicable_test_notes';

        protected $guarded = [];
    }
}

it('registers migration macros and observer updates related columns', function (): void {
    Schema::create('arabicable_test_notes', function ($table): void {
        $table->id();
        $table->arabicString('content', length: 100);
        $table->timestamps();
    });

    $record = ArabicableTestNote::query()->create([
        'content' => 'بِسْمِ اللَّه',
    ]);

    expect($record->content)->toBe('بسم الله');
    expect($record->{ar_with_harakat('content')})->toBe('بِسْمِ اللَّه');
    expect($record->{ar_searchable('content')})->toBe('بسم الله');
    expect($record->{ar_stem('content')})->toBe('بسم الله');

    $validator = validator()->make(
        ['content' => 'بِسْمِ اللَّه'],
        ['content' => [new UniqueArabicWithSpecialCharacters(ArabicableTestNote::class)]],
    );

    expect($validator->fails())->toBeTrue();
});
