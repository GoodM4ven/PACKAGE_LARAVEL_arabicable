<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Models\ArabicStopWord;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;
use Livewire\Livewire;
use Workbench\App\Livewire\Demo;

beforeEach(function (): void {
    ArabicableConfig::set('arabicable.data_sources.word_variants', __DIR__.'/Fixtures/verbs-classified-compiled-mini.tsv');
    ArabicableConfig::set('arabicable.data_sources.quran_word_index', __DIR__.'/Fixtures/quran-word-index-compiled-mini.tsv');
    ArabicStopWord::query()->updateOrCreate(
        ['word' => 'من'],
        ['vocalized' => 'مِنْ', 'source' => 'test'],
    );
    app(ArabicWordVariants::class)->clearCache();
});

it('runs processing only when explicitly triggered', function (): void {
    Livewire::test(Demo::class)
        ->set('query', 'فاقتضاربايننا')
        ->assertSet('activeQuery', '')
        ->call('runProcessing')
        ->assertSet('activeQuery', 'فاقتضاربايننا');
});

it('respects analysis toggles for expensive steps', function (): void {
    Livewire::test(Demo::class)
        ->set('query', 'مِنْ طَلَبِ العِلْمِ')
        ->set('enableSanitization', false)
        ->call('runProcessing')
        ->assertViewHas('analysis', static function (array $analysis): bool {
            return $analysis['sanitized'] === 'مِنْ طَلَبِ العِلْمِ';
        });
});

it('clears both staged and active query values', function (): void {
    Livewire::test(Demo::class)
        ->set('query', 'اختبار')
        ->call('runProcessing')
        ->call('clearQuery')
        ->assertSet('query', '')
        ->assertSet('activeQuery', '');
});

it('loads the predefined example and processes it', function (): void {
    Livewire::test(Demo::class)
        ->call('addExample')
        ->assertSet('query', 'مَن لَم يَدَعْ قَولَ الزُّورِ والعَمَلَ به والجَهلَ، فليس لله حاجة في أن يدع طعامه وشرابه...')
        ->assertSet('activeQuery', 'مَن لَم يَدَعْ قَولَ الزُّورِ والعَمَلَ به والجَهلَ، فليس لله حاجة في أن يدع طعامه وشرابه...');
});

it('returns expanded search terms when comprehensive mode is enabled', function (): void {
    Livewire::test(Demo::class)
        ->set('query', 'يضرب من')
        ->set('enableComprehensiveSearch', true)
        ->call('runProcessing')
        ->assertViewHas('analysis', static function (array $analysis): bool {
            return $analysis['terms'] !== [] && ! in_array('من', $analysis['variants'], true);
        });
});

it('applies roots-only mode to morph variant output', function (): void {
    Livewire::test(Demo::class)
        ->set('query', 'يضرب')
        ->set('enableComprehensiveSearch', true)
        ->set('variantMode', 'roots')
        ->call('runProcessing')
        ->assertViewHas('analysis', static function (array $analysis): bool {
            return in_array('ضرب', $analysis['variants'], true)
                && ! in_array('ضارب', $analysis['variants'], true);
        });
});
