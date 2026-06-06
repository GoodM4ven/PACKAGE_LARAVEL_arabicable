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
    DB::table('quran_verses')->whereIn('id', [
        9,
        157,
        208,
        2030,
        2163,
        2284,
        2357,
        3706,
        3745,
        5257,
        6222,
        6223,
        6224,
        6225,
    ])->delete();
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
            'id' => 9,
            'ayah_index' => 9,
            'surah_number' => 2,
            'ayah_number' => 2,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ذلك الكتاب لا ريب فيه هدي للمتقين',
            'text_searchable' => 'ذالك الكتاب لا ريب فيه هدي للمتقين',
            'text_searchable_typed' => 'ذالك الكتاب لا ريب فيه هدي للمتقين',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 157,
            'ayah_index' => 157,
            'surah_number' => 2,
            'ayah_number' => 144,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ومن حيث خرجت فول وجهك شطر المسجد الحرام وحيث ما كنتم فولوا وجوهكم شطره ليلا يكون للناس عليكم حجة الا الذين ظلموا منهم فلا تخشوهم واخشوني ولاتم نعمتي عليكم ولعلكم تهتدون',
            'text_searchable' => 'ومن حيث خرجت فول وجهك شطر المسجد الحرام وحيث ما كنتم فولوا وجوهكم شطره ليلا يكون للناس عليكم حجة الا الذين ظلموا منهم فلا تخشوهم واخشوني ولاتم نعمتي عليكم ولعلكم تهتدون',
            'text_searchable_typed' => 'ومن حيث خرجت فول وجهك شطر المسجد الحرام وحيث ما كنتم فولوا وجوهكم شطره ليلا يكون للناس عليكم حجة الا الذين ظلموا منهم فلا تخشوهم واخشوني ولاتم نعمتي عليكم ولعلكم تهتدون',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 208,
            'ayah_index' => 208,
            'surah_number' => 2,
            'ayah_number' => 201,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ومنهم من يقول ربنا ءاتنا في الدنيا حسنة وفي الءاخرة حسنة وقنا عذاب النار',
            'text_searchable' => 'ومنهم من يقول ربنا ءاتنا في الدنيا حسنة وفي الءاخرة حسنة وقنا عذاب النار',
            'text_searchable_typed' => 'ومنهم من يقول ربنا ءاتنا في الدنيا حسنة وفي الءاخرة حسنة وقنا عذاب النار',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 2030,
            'ayah_index' => 2030,
            'surah_number' => 17,
            'ayah_number' => 1,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'سبحان الذي اسريا بعبده ليلا من المسجد الحرام الي المسجد الاقصا الذي باركنا حوله لنريه من ءاياتنا انه هو السميع البصير',
            'text_searchable' => 'سبحان الذي اسريا بعبده ليلا من المسجد الحرام الي المسجد الاقصا الذي باركنا حوله لنريه من ءاياتنا انه هو السميع البصير',
            'text_searchable_typed' => 'سبحان الذي اسريا بعبده ليلا من المسجد الحرام الي المسجد الاقصا الذي باركنا حوله لنريه من ءاياتنا انه هو السميع البصير',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 2163,
            'ayah_index' => 2163,
            'surah_number' => 18,
            'ayah_number' => 23,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ولا تقولن لشايء اني فاعل ذالك غدا',
            'text_searchable' => 'ولا تقولن لشايء اني فاعل ذالك غدا',
            'text_searchable_typed' => 'ولا تقولن لشايء اني فاعل ذالك غدا',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 2284,
            'ayah_index' => 2284,
            'surah_number' => 19,
            'ayah_number' => 34,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ذالك عيسي ابن مريم قول الحق الذي فيه يمترون',
            'text_searchable' => 'ذالك عيسي ابن مريم قول الحق الذي فيه يمترون',
            'text_searchable_typed' => 'ذالك عيسي ابن مريم قول الحق الذي فيه يمترون',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 2357,
            'ayah_index' => 2357,
            'surah_number' => 20,
            'ayah_number' => 9,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'وهل اتياك حديث موسيا',
            'text_searchable' => 'وهل اتياك حديث موسيا',
            'text_searchable_typed' => 'وهل اتياك حديث موسيا',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 3706,
            'ayah_index' => 3706,
            'surah_number' => 36,
            'ayah_number' => 1,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'يس',
            'text_searchable' => 'يس',
            'text_searchable_typed' => 'يس',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 3745,
            'ayah_index' => 3745,
            'surah_number' => 36,
            'ayah_number' => 40,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'لا الشمس ينبغي لها ان تدرك القمر ولا اليل سابق النهار وكل في فلك يسبحون',
            'text_searchable' => 'لا الشمس ينبغي لها ان تدرك القمر ولا اليل سابق النهار وكل في فلك يسبحون',
            'text_searchable_typed' => 'لا الشمس ينبغي لها ان تدرك القمر ولا اليل سابق النهار وكل في فلك يسبحون',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 5257,
            'ayah_index' => 5257,
            'surah_number' => 67,
            'ayah_number' => 16,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ءامنتم من في السماء ان يخسف بكم الارض فاذا هي تمور',
            'text_searchable' => 'ءامنتم من في السماء ان يخسف بكم الارض فاذا هي تمور',
            'text_searchable_typed' => 'ءامنتم من في السماء ان يخسف بكم الارض فاذا هي تمور',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 6222,
            'ayah_index' => 6222,
            'surah_number' => 112,
            'ayah_number' => 1,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'قل هو الله احد',
            'text_searchable' => 'قل هو الله احد',
            'text_searchable_typed' => 'قل هو الله احد',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 6223,
            'ayah_index' => 6223,
            'surah_number' => 112,
            'ayah_number' => 2,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'الله الصمد',
            'text_searchable' => 'الله الصمد',
            'text_searchable_typed' => 'الله الصمد',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 6224,
            'ayah_index' => 6224,
            'surah_number' => 112,
            'ayah_number' => 3,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'لم يلد ولم يولد',
            'text_searchable' => 'لم يلد ولم يولد',
            'text_searchable_typed' => 'لم يلد ولم يولد',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => 6225,
            'ayah_index' => 6225,
            'surah_number' => 112,
            'ayah_number' => 4,
            'mushaf_page' => null,
            'mushaf_line' => null,
            'text_uthmani' => 'ولم يكن له كفوا احد',
            'text_searchable' => 'ولم يكن له كفوا احد',
            'text_searchable_typed' => 'ولم يكن له كفوا احد',
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

    $buildMatchesMethod = new ReflectionMethod($component, 'buildSearchMatches');
    $buildMatchesMethod->setAccessible(true);

    $hasTypedWordColumn = Schema::hasTable('quran_words') && Schema::hasColumn('quran_words', 'token_searchable_typed');

    /** @var array<int, array{ayah_index: int}> $matches */
    $matches = $buildMatchesMethod->invoke($component, $query, $limit, $hasTypedWordColumn);

    return array_values(array_map(
        static fn (array $match): int => (int) $match['ayah_index'],
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

it('matches hidden-character variants through package defaults', function (): void {
    $hiddenCharsQuery = "ف\u{200F}اسأل";
    $hiddenCharResults = quranReaderSearchAyahIndexes($hiddenCharsQuery);

    expect($hiddenCharResults)
        ->not->toBeEmpty()
        ->toContain(65001)
        ->not->toContain(65002);
});

it('finds exact quran ayah matches for hamza and orthography variants', function (string $query, array $expectedAyahIndexes): void {
    expect(quranReaderSearchAyahIndexes($query))->toBe($expectedAyahIndexes);
})->with([
    'ذلك الكتاب' => [
        'query' => 'ذلك الكتاب لا ريب فيه',
        'expectedAyahIndexes' => [9],
    ],
    'double hamza' => [
        'query' => 'أأمنتم من في السماء',
        'expectedAyahIndexes' => [5257],
    ],
    'solar and night orthography' => [
        'query' => 'لَا الشَّمْسُ يَنبَغِي لَهَا أَن تُدْرِكَ الْقَمَرَ وَلَا اللَّيْلُ سَابِقُ النَّهَارِ',
        'expectedAyahIndexes' => [3745],
    ],
    'musa orthography' => [
        'query' => 'وَهَلْ أَتَاكَ حَدِيثُ مُوسَىٰ',
        'expectedAyahIndexes' => [2357],
    ],
    'dua with akhira' => [
        'query' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً',
        'expectedAyahIndexes' => [208],
    ],
    'yasin alias' => [
        'query' => 'ياسين',
        'expectedAyahIndexes' => [3706],
    ],
]);

it('splits verse separators into separate exact ayah matches', function (): void {
    $query = 'قُلْ هُوَ اللَّهُ أَحَدٌ ۝ اللَّهُ الصَّمَدُ ۝ لَمْ يَلِدْ وَلَمْ يُولَدْ ۝ وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ';

    expect(quranReaderSearchAyahIndexes($query))->toBe([6222, 6223, 6224, 6225]);
});
