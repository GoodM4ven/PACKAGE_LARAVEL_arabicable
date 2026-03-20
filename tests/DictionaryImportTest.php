<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Database\Seeders\ImportArabicCommonTextsSeeder;
use GoodMaven\Arabicable\Database\Seeders\ImportArabicStopWordsSeeder;
use GoodMaven\Arabicable\Models\ArabicStopWord;
use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
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

    if (! Schema::hasTable('common_arabic_texts')) {
        Schema::create('common_arabic_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30);
            $table->arabicString('content', length: 120, isUnique: true);
            $table->timestamps();
        });
    }

    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', __DIR__.'/Fixtures/stopwords-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_classified', __DIR__.'/Fixtures/stopwords-classified-mini.tsv');
});

it('imports stop words from a small fixture quickly', function (): void {
    app(ImportArabicStopWordsSeeder::class)->run();

    expect(ArabicStopWord::query()->count())->toBe(2)
        ->and(ArabicStopWord::query()->where('word', 'ان')->value('vocalized'))->toBe('أَنَّ');
});

it('imports common texts from classified stop-words fixture quickly', function (): void {
    app(ImportArabicCommonTextsSeeder::class)->run();

    expect(CommonArabicText::query()->count())->toBe(4);
    expect(CommonArabicText::query()->where('content', 'الله')->first()?->type->value)->toBe('name');
});

it('imports dictionaries from compiled core fixtures', function (): void {
    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', __DIR__.'/Fixtures/stopwords-forms-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_classified', __DIR__.'/Fixtures/stopwords-classified-mini.tsv');

    app(ImportArabicStopWordsSeeder::class)->run();
    app(ImportArabicCommonTextsSeeder::class)->run();

    expect(ArabicStopWord::query()->where('word', 'ان')->where('source', 'arabicstopwords-forms')->exists())->toBeTrue();
    expect(CommonArabicText::query()->where('content', 'الله')->exists())->toBeTrue();
});
