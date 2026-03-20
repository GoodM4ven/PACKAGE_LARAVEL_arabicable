<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Workbench\App\Livewire\QuranReader;

beforeEach(function (): void {
    config([
        'tests.created_quran_verses' => false,
        'tests.created_quran_words' => false,
        'tests.created_quran_mushaf_lines' => false,
    ]);

    if (! Schema::hasTable('quran_verses')) {
        Schema::create('quran_verses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('ayah_index');
            $table->unsignedSmallInteger('mushaf_page')->nullable();
            $table->unsignedTinyInteger('mushaf_line')->nullable();
            $table->text('text_uthmani');
            $table->text('text_searchable');
            $table->text('text_searchable_typed');
            $table->timestamps();
        });

        config(['tests.created_quran_verses' => true]);
    }

    if (! Schema::hasTable('quran_words')) {
        Schema::create('quran_words', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('verse_id');
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('ayah_index');
            $table->unsignedSmallInteger('word_position');
            $table->unsignedInteger('global_word_index');
            $table->string('token_uthmani', 191);
            $table->string('token_searchable', 191);
            $table->string('token_searchable_typed', 191);
            $table->string('token_stem', 191)->nullable();
            $table->string('token_root', 191)->nullable();
            $table->string('token_lemma', 191)->nullable();
        });

        config(['tests.created_quran_words' => true]);
    }

    if (! Schema::hasTable('quran_mushaf_lines')) {
        Schema::create('quran_mushaf_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('layout_key', 64);
            $table->unsignedSmallInteger('page_number');
            $table->unsignedTinyInteger('line_number');
            $table->string('line_type', 24)->default('ayah');
            $table->boolean('is_centered')->default(false);
            $table->unsignedInteger('first_word_index')->nullable();
            $table->unsignedInteger('last_word_index')->nullable();
            $table->unsignedTinyInteger('surah_number')->nullable();
            $table->timestamps();
        });

        config(['tests.created_quran_mushaf_lines' => true]);
    }

    DB::table('quran_words')->whereIn('verse_id', [65001, 65002])->delete();
    DB::table('quran_verses')->whereIn('id', [65001, 65002])->delete();
    DB::table('quran_mushaf_lines')
        ->where('layout_key', 'tests-fixture')
        ->where('page_number', 220)
        ->where('line_number', 1)
        ->delete();

    $timestamp = now();

    DB::table('quran_verses')->insert([
        [
            'id' => 65001,
            'ayah_index' => 65001,
            'surah_number' => 250,
            'ayah_number' => 1,
            'mushaf_page' => 220,
            'mushaf_line' => 1,
            'text_uthmani' => 'فَسْـَٔلِ الَّذِينَ يَقْرَءُونَ الْكِتَابَ',
            'text_searchable' => 'فسل الذين يقرؤون الكتاب',
            'text_searchable_typed' => 'فسل الذين يقرؤون الكتاب',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 65002,
            'ayah_index' => 65002,
            'surah_number' => 251,
            'ayah_number' => 1,
            'mushaf_page' => 158,
            'mushaf_line' => 1,
            'text_uthmani' => 'أُبَلِّغُكُمْ رِسَالَاتِ رَبِّي',
            'text_searchable' => 'ابلغكم رسالات ربي',
            'text_searchable_typed' => 'ابلغكم رسالات ربي',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);

    DB::table('quran_words')->insert([
        [
            'verse_id' => 65001,
            'surah_number' => 250,
            'ayah_number' => 1,
            'ayah_index' => 65001,
            'word_position' => 1,
            'global_word_index' => 900001,
            'token_uthmani' => 'فَسْـَٔلِ',
            'token_searchable' => 'فسل',
            'token_searchable_typed' => 'فسل',
            'token_stem' => 'سل',
            'token_root' => 'سال',
            'token_lemma' => 'سال',
        ],
        [
            'verse_id' => 65002,
            'surah_number' => 251,
            'ayah_number' => 1,
            'ayah_index' => 65002,
            'word_position' => 1,
            'global_word_index' => 900002,
            'token_uthmani' => 'رِسَالَاتِ',
            'token_searchable' => 'رسالات',
            'token_searchable_typed' => 'رسالات',
            'token_stem' => 'رسالة',
            'token_root' => 'رسل',
            'token_lemma' => 'رسالة',
        ],
    ]);

    DB::table('quran_mushaf_lines')->insert([
        'layout_key' => 'tests-fixture',
        'page_number' => 220,
        'line_number' => 1,
        'line_type' => 'ayah',
        'is_centered' => false,
        'first_word_index' => 1,
        'last_word_index' => 200000,
        'surah_number' => 250,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
});

afterEach(function (): void {
    DB::table('quran_words')->whereIn('verse_id', [65001, 65002])->delete();
    DB::table('quran_verses')->whereIn('id', [65001, 65002])->delete();
    DB::table('quran_mushaf_lines')
        ->where('layout_key', 'tests-fixture')
        ->where('page_number', 220)
        ->where('line_number', 1)
        ->delete();

    if ((bool) config('tests.created_quran_mushaf_lines')) {
        Schema::dropIfExists('quran_mushaf_lines');
    }

    if ((bool) config('tests.created_quran_words')) {
        Schema::dropIfExists('quran_words');
    }

    if ((bool) config('tests.created_quran_verses')) {
        Schema::dropIfExists('quran_verses');
    }
});

function quranReaderSearchAyahIndexes(string $query, int $limit = 7000): array
{
    $component = new QuranReader;

    $normalizeMethod = new ReflectionMethod($component, 'normalizeQuranSearchQuery');
    $normalizeMethod->setAccessible(true);

    $buildMatchesMethod = new ReflectionMethod($component, 'buildSearchMatches');
    $buildMatchesMethod->setAccessible(true);

    $normalizedQuery = trim((string) $normalizeMethod->invoke($component, $query));

    if ($normalizedQuery === '') {
        return [];
    }

    $hasTypedWordColumn = Schema::hasTable('quran_words') && Schema::hasColumn('quran_words', 'token_searchable_typed');

    /** @var array<int, array{ayah_index: int}> $matches */
    $matches = $buildMatchesMethod->invoke($component, $normalizedQuery, $limit, $hasTypedWordColumn);

    return array_values(array_map(
        static fn (array $match): int => (int) ($match['ayah_index'] ?? 0),
        $matches,
    ));
}

it('avoids unrelated رسالات matches when searching فسأل', function (): void {
    $ayahIndexes = quranReaderSearchAyahIndexes('فسأل');

    expect($ayahIndexes)
        ->not->toBeEmpty()
        ->toContain(65001)
        ->not->toContain(65002);
});

it('treats فاسأل as the same query family as فسأل', function (): void {
    $pastForm = quranReaderSearchAyahIndexes('فسأل');
    $imperativeForm = quranReaderSearchAyahIndexes('فاسأل');

    expect($imperativeForm)
        ->not->toBeEmpty()
        ->toContain(65001)
        ->not->toContain(65002)
        ->and($pastForm)->toContain(65001);
});
