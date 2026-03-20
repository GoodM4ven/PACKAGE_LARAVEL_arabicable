<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Models\ArabicStopWord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('arabic_stop_words')) {
        Schema::create('arabic_stop_words', function (Blueprint $table): void {
            $table->id();
            $table->string('word', 191);
            $table->string('vocalized', 191)->nullable();
            $table->string('lemma', 191)->nullable();
            $table->string('type', 80)->nullable();
            $table->string('category', 120)->nullable();
            $table->string('stem', 191)->nullable();
            $table->string('tags', 255)->nullable();
            $table->string('source', 80)->default('imported');
            $table->timestamps();
            $table->unique(['word', 'source']);
        });
    }

    ArabicStopWord::query()->create(['word' => 'من', 'vocalized' => 'مِنْ', 'source' => 'test']);
    ArabicStopWord::query()->create(['word' => 'في', 'vocalized' => 'فِي', 'source' => 'test']);
});

it('strips diacritics with optional shadda preservation', function (): void {
    expect(Arabic::removeDiacritics('بِسْمِ اللَّهِ'))->toBe('بسم الله');
    expect(Arabic::removeDiacritics('الرَّحْمَن', keepShadda: true))->toBe('الرّحمن');
});

it('identifies and strips weird characters when needed', function (): void {
    $special = Arabic::identifySpecialCharacters('نص🙂 تجريبي، !! @@', includeKnownPunctuation: false);

    expect(array_key_exists('🙂', $special))->toBeTrue();
    expect(array_key_exists('@', $special))->toBeTrue();

    expect(Arabic::stripWeirdCharacters('نص🙂 تجريبي، !! @@'))->toBe('نص تجريبي، !!');
});

it('extracts keywords with stemming-focused output', function (): void {
    $payload = Arabic::extractKeywords('من طلب العلم في الخير', stripCommons: false);

    expect($payload['tokens'])->toEqualCanonicalizing(['طلب', 'العلم', 'الخير']);
    expect($payload['keywords'])->not->toBe([]);
});

it('prepares memorization comparison text', function (): void {
    $filtered = ArabicFilter::forMemorizationComparison(
        'مِنْ طَلَبِ العِلْمِ في الخير',
        stripCommons: false,
    );

    expect($filtered)->toContain('طلب');
});

it('builds a comprehensive search plan using normalized and stemmed terms', function (): void {
    $plan = Arabic::buildComprehensiveSearchPlan('فَضَرَبَ من يضرب', 40);

    expect($plan['tokens'])->toContain('فضرب');
    expect($plan['stripped'])->not->toContain('من');
    expect($plan['terms'])->not->toContain('من');
    expect($plan['terms'])->not->toBe([]);
});

it('handles possessive noun endings and avoids short feminine over-stemming', function (): void {
    expect(Arabic::stemWord('طعامه'))->toBe('طعام');
    expect(Arabic::stemWord('شرابه'))->toBe('شراب');
    expect(Arabic::stemWord('حاجة'))->toBe('حاجة');
    expect(Arabic::stemWord('أمته'))->toBe('أمة');
    expect(Arabic::stemWord('رحمته'))->toBe('رحمة');
    expect(Arabic::stemWord('نعمته'))->toBe('نعمة');
    expect(Arabic::stemWord('بيته'))->toBe('بيت');
    expect(Arabic::stemWord('بنته'))->toBe('بنت');
});
