<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Rules\Arabic;
use GoodMaven\Arabicable\Rules\ArabicWithSpecialCharacters;
use GoodMaven\Arabicable\Rules\UncommonArabic;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('common_arabic_texts')) {
        Schema::create('common_arabic_texts', function ($table): void {
            $table->id();
            $table->string('type', 30);
            $table->arabicString('content', length: 40);
            $table->timestamps();
        });
    }
});

it('validates Arabic rule combinations', function (): void {
    expect(
        validator()->make(['text' => 'بسم الله'], ['text' => [new Arabic]])->fails()
    )->toBeFalse();

    expect(
        validator()->make(['text' => 'بِسْمِ اللَّه'], ['text' => [new Arabic]])->fails()
    )->toBeTrue();

    expect(
        validator()->make(['text' => 'بِسْمِ اللَّه'], ['text' => [new Arabic(withHarakat: true)]])->fails()
    )->toBeFalse();

    expect(
        validator()->make(['text' => 'بِسْمِ اللَّه!'], ['text' => [new Arabic(withHarakat: true)]])->fails()
    )->toBeTrue();
});

it('validates ArabicWithSpecialCharacters rule', function (): void {
    expect(
        validator()->make(['text' => 'بِسْمِ اللَّه!'], ['text' => [new ArabicWithSpecialCharacters]])->fails()
    )->toBeFalse();

    expect(
        validator()->make(['text' => 'Hello'], ['text' => [new ArabicWithSpecialCharacters]])->fails()
    )->toBeTrue();
});

it('validates uncommon Arabic phrases', function (): void {
    CommonArabicText::query()->create([
        'type' => CommonArabicTextType::Separator,
        'content' => 'هو',
    ]);

    CommonArabicText::query()->create([
        'type' => CommonArabicTextType::Separator,
        'content' => 'من',
    ]);

    expect(
        validator()->make(['text' => 'هو من'], ['text' => [new UncommonArabic]])->fails()
    )->toBeTrue();

    expect(
        validator()->make(['text' => 'هو من أهل الخير'], ['text' => [new UncommonArabic]])->fails()
    )->toBeFalse();
});
