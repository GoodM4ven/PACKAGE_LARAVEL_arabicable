<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Models\ArabicStopWord;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;

beforeEach(function (): void {
    ArabicableConfig::set('arabicable.data_sources.word_variants', __DIR__.'/Fixtures/verbs-classified-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', __DIR__.'/Fixtures/stopwords-forms-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.search.comprehensive.expand_with_word_variants', true);
    ArabicableConfig::set('arabicable.search.comprehensive.max_word_variants_per_token', 25);
    ArabicableConfig::set('arabicable.search.comprehensive.max_variant_terms', 120);
    ArabicableConfig::set('arabicable.search.comprehensive.min_variant_term_length', 2);
    ArabicableConfig::set('arabicable.search.comprehensive.variant_mode', 'all');
    ArabicableConfig::set('arabicable.search.comprehensive.strip_stop_words_from_variants', true);

    app(ArabicWordVariants::class)->clearCache();
});

it('expands tokens into word-family variants using configured lexical sources', function (): void {
    $variants = Arabic::expandWordVariants('يضرب');

    expect($variants)->toContain('يضرب');
    expect($variants)->toContain('ضرب');
    expect($variants)->toContain('ضارب');
    expect($variants)->toContain('ضروب');
});

it('injects lexical variants into comprehensive search plans when enabled', function (): void {
    $plan = Arabic::buildComprehensiveSearchPlan('يضرب', 80);

    expect($plan['terms'])->toContain('ضارب');
    expect($plan['terms'])->toContain('ضروب');
});

it('skips lexical variants in comprehensive plans when disabled', function (): void {
    ArabicableConfig::set('arabicable.search.comprehensive.expand_with_word_variants', false);
    app(ArabicWordVariants::class)->clearCache();

    $plan = Arabic::buildComprehensiveSearchPlan('يضرب', 80);

    expect($plan['terms'])->not->toContain('ضروب');
});

it('supports roots-only and stems-only variant modes', function (): void {
    $rootsOnly = Arabic::expandWordVariants('يضرب', mode: 'roots');
    $stemsOnly = Arabic::expandWordVariants('يضرب', mode: 'stems');
    $originalOnly = Arabic::expandWordVariants('يضرب', mode: 'original_words');

    expect($rootsOnly)->toContain('ضرب');
    expect($rootsOnly)->not->toContain('ضارب');
    expect($stemsOnly)->toContain('ضرب');
    expect($stemsOnly)->toContain('ضارب');
    expect($originalOnly)->toContain('ضارب');
    expect($originalOnly)->toContain('ضروب');
});

it('never emits stop-word variants when stop-word stripping is enabled', function (): void {
    $tempPath = sys_get_temp_dir().'/arabicable-word-variants-stopword-'.uniqid('', true).'.tsv';

    file_put_contents($tempPath, implode(PHP_EOL, [
        'word_with_harakat	unvocalized	unmarked	root	kind	source',
        'مِنْ	من	من	من	part	test',
        'يَضْرِبُ	يضرب	يضرب	ضرب	verb	test',
        'ضَارِبٌ	ضارب	ضارب	ضرب	noun	test',
    ]));

    ArabicableConfig::set('arabicable.data_sources.word_variants', $tempPath);
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicStopWord::query()->updateOrCreate(
        ['word' => 'من'],
        ['vocalized' => 'مِنْ', 'source' => 'test'],
    );
    app(ArabicWordVariants::class)->clearCache();

    $variants = Arabic::expandWordVariants(['من', 'يضرب'], mode: 'all', stripStopWords: true);

    expect($variants)->not->toContain('من');
    expect($variants)->toContain('ضرب');
    expect($variants)->toContain('ضارب');

    @unlink($tempPath);
});

it('does not drop lexical families because of stop-word lemma or stem columns', function (): void {
    $variantsPath = sys_get_temp_dir().'/arabicable-word-variants-akh-'.uniqid('', true).'.tsv';
    $stopWordsPath = sys_get_temp_dir().'/arabicable-stop-words-akh-'.uniqid('', true).'.tsv';

    file_put_contents($variantsPath, implode(PHP_EOL, [
        'word_with_harakat	unvocalized	unmarked	root	kind	source',
        'أَخٌ	اخ	اخ	اخو	noun	test',
        'إِخْوَانٌ	اخوان	اخوان	اخو	noun	test',
        'إِخْوَةٌ	اخوة	اخوة	اخو	noun	test',
    ]));

    file_put_contents($stopWordsPath, implode(PHP_EOL, [
        'word	vocalized	type	category	lemma	stem	tags	source',
        'من	مِنْ	part	test	اخ	اخ	test	test',
    ]));

    ArabicableConfig::set('arabicable.data_sources.word_variants', $variantsPath);
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.stop_words_forms', $stopWordsPath);
    app(ArabicWordVariants::class)->clearCache();

    $variants = Arabic::expandWordVariants('اخ', mode: 'original_words', stripStopWords: true);

    expect($variants)->toContain('اخوان');
    expect($variants)->toContain('اخوة');

    @unlink($variantsPath);
    @unlink($stopWordsPath);
});
