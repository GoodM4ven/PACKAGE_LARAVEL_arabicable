<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('common_arabic_texts')) {
        Schema::create('common_arabic_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30);
            $table->arabicString('content', length: 160);
            $table->timestamps();
        });
    }

    ArabicableConfig::set('arabicable.data_sources.word_variants', __DIR__.'/Fixtures/verbs-classified-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', __DIR__.'/Fixtures/stopwords-forms-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.search.comprehensive.expand_with_word_variants', true);
    ArabicableConfig::set('arabicable.search.comprehensive.variant_mode', 'all');
    ArabicableConfig::set('arabicable.search.comprehensive.strip_stop_words_from_variants', true);
    app(ArabicWordVariants::class)->clearCache();
});

it('uses lexical variants inside comprehensive search scope', function (): void {
    CommonArabicText::query()->create([
        'type' => 'separator',
        'content' => 'هذه ضروب متعددة من البيان',
    ]);

    CommonArabicText::query()->create([
        'type' => 'separator',
        'content' => 'نص آخر بعيد',
    ]);

    $results = CommonArabicText::query()
        ->searchArabicComprehensive('content', 'يضرب')
        ->pluck('content')
        ->all();

    expect($results)->toContain('هذه ضروب متعددة من البيان');
});
