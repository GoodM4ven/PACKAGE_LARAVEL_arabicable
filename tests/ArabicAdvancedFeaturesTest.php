<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Traits\Arabicable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (! class_exists('ArabicableTranslatableNote')) {
    class ArabicableTranslatableNote extends Model
    {
        use Arabicable;

        protected $table = 'arabicable_translatable_notes';

        protected $guarded = [];

        protected array $translatable = ['title'];

        protected function casts(): array
        {
            return [
                'title' => 'array',
            ];
        }
    }
}

beforeEach(function (): void {
    if (! Schema::hasTable('arabicable_translatable_notes')) {
        Schema::create('arabicable_translatable_notes', function (Blueprint $table): void {
            $table->id();
            $table->arabicString('title', length: 120, isTranslatable: true);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('common_arabic_texts')) {
        Schema::create('common_arabic_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30);
            $table->arabicString('content', length: 120);
            $table->timestamps();
        });
    }

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
});

it('handles translatable Arabic payloads and searchable translations', function (): void {
    $record = ArabicableTranslatableNote::query()->create([
        'title' => [
            'ar' => 'بِسْمِ اللَّه',
            'en' => 'In the name of Allah',
        ],
    ]);

    expect($record->title['ar'])->toBe('بسم الله');
    expect($record->{ar_searchable('title')})->toBe('بسم الله');
    expect($record->{ar_stem('title')})->toBe('بسم الله');

    $searchableTranslations = $record->getSearchableTranslations();

    expect($searchableTranslations['title_searchable_ar'])->toBe('بسم الله');
    expect($searchableTranslations['title_searchable_en'])->toBe('In the name of Allah');
});

it('orders Arabic matches by relevance', function (): void {
    CommonArabicText::query()->create(['type' => 'separator', 'content' => 'طلب العلم']);
    CommonArabicText::query()->create(['type' => 'separator', 'content' => 'من طلب العلم نال']);
    CommonArabicText::query()->create(['type' => 'separator', 'content' => 'العلم طريق']);

    $ordered = CommonArabicText::query()
        ->searchArabic('content', 'طلب العلم')
        ->pluck('content')
        ->all();

    expect($ordered[0] ?? null)->toBe('طلب العلم');
});

it('supports comprehensive Arabic search scope with normalized terms', function (): void {
    CommonArabicText::query()->create(['type' => 'separator', 'content' => 'ضرب زيد مثالا']);
    CommonArabicText::query()->create(['type' => 'separator', 'content' => 'الضرب مفيد في التدريب']);

    $results = CommonArabicText::query()
        ->searchArabicComprehensive('يضرب')
        ->pluck('content')
        ->all();

    expect($results)->not->toBeEmpty();
});

it('does not synthesize dictionary harakat', function (): void {
    expect(Arabic::addHarakat('ان من خير'))->toBe('ان من خير');
});
