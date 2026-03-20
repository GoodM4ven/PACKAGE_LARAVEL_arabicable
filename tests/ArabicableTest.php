<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Enums\ArabicLinguisticConcept;
use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Models\CommonArabicText;
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

it('handles numeral and punctuation helpers', function (): void {
    expect(Arabic::convertNumeralsToIndian('1 2 34'))->toBe('١ ٢ ٣٤');
    expect(Arabic::convertNumeralsToArabic('١٢٣'))->toBe('123');

    expect(Arabic::convertNumeralsToArabicAndIndianSequences('11 2'))
        ->toBe('11 ١١ 2 ٢');

    expect(trim(Arabic::deduplicateArabicAndIndianNumeralSequences('11 ١١ 11 ١١')))
        ->toBe('11 ١١');

    expect(Arabic::convertPunctuationMarksToArabic('هل هذا صحيح, أم لا?'))
        ->toBe('هل هذا صحيح، أم لا؟');

    expect(Arabic::removeAllPunctuationMarks('نص، تجريبي!'))
        ->toBe('نص تجريبي');

    expect(Arabic::toTightPunctuationStyle('ومن ثم ، نظرت إليه .'))
        ->toBe('ومن ثم، نظرت إليه.');

    expect(Arabic::toLoosePunctuationStyle('ومن ثم، نظرت إليه.'))
        ->toBe('ومن ثم ، نظرت إليه .');
});

it('normalizes and filters Arabic text', function (): void {
    expect(Arabic::removeHarakat('بِسْمِ اللَّه'))->toBe('بسم الله');

    expect(Arabic::normalizeHuroof('أقبل الولد'))->toBe('اقبل الولد');
});

it('tokenizes search text with stable boundaries and digit splitting', function (): void {
    expect(Arabic::tokenize('الجهل،فليس 2026م🙂'))
        ->toBe(['الجهل', 'فليس', '2026', 'م']);
});

it('removes common words and clears related cache', function (): void {
    CommonArabicText::query()->create([
        'type' => CommonArabicTextType::Separator,
        'content' => 'من',
    ]);
    CommonArabicText::query()->create([
        'type' => CommonArabicTextType::Separator,
        'content' => 'هو',
    ]);

    Arabic::clearConceptCache(ArabicLinguisticConcept::CommonTexts);

    expect(Arabic::removeCommons('هو من أهل الصدق', asString: true))
        ->toBe('أهل الصدق');
});
