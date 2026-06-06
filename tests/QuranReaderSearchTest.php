<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Workbench\App\Livewire\QuranReader;

beforeEach(function (): void {
    config([
        'tests.created_quran_verses' => false,
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

    if ((bool) config('tests.created_quran_verses')) {
        $timestamp = now();

        DB::table('quran_verses')->insert([
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
        ]);
    }

    if ((bool) config('tests.created_quran_mushaf_lines')) {
        $timestamp = now();

        DB::table('quran_mushaf_lines')->insert([
            [
                'layout_key' => 'tests-fixture',
                'page_number' => 1,
                'line_number' => 1,
                'line_type' => 'ayah',
                'is_centered' => false,
                'first_word_index' => 1,
                'last_word_index' => 1000000,
                'surah_number' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }
});

afterEach(function (): void {
    if ((bool) config('tests.created_quran_verses')) {
        Schema::dropIfExists('quran_verses');
    }

    if ((bool) config('tests.created_quran_mushaf_lines')) {
        Schema::dropIfExists('quran_mushaf_lines');
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
