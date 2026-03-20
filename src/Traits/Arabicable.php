<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Traits;

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Observers\ArabicableModelObserver;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Support\Exceptions\ArabicableException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait Arabicable
{
    public static function bootArabicable(): void
    {
        static::observe(ArabicableModelObserver::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearchableTranslations(): array
    {
        if (! isset($this->translatable) || ! is_array($this->translatable)) {
            throw new ArabicableException(
                'Please implement Spatie Laravel Translatable\'s $translatable property before using getSearchableTranslations().',
            );
        }

        $translations = [];

        foreach ($this->translatable as $property) {
            if (! is_string($property)) {
                continue;
            }

            $translatedValues = $this->getAttribute($property);

            if (is_string($translatedValues)) {
                $decoded = json_decode($translatedValues, true);
                $translatedValues = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($translatedValues)) {
                continue;
            }

            foreach ($translatedValues as $locale => $value) {
                if (! is_string($locale)) {
                    continue;
                }

                $searchableLabel = ar_searchable($property);
                if ($locale === 'ar') {
                    $propertyValue = $this->getAttribute($searchableLabel);

                    if (! is_string($propertyValue) || trim($propertyValue) === '') {
                        if (! is_string($value)) {
                            continue;
                        }

                        $propertyValue = ArabicFilter::forSearch($value);
                    }

                    $translations["{$searchableLabel}_{$locale}"] = $propertyValue;

                    continue;
                }

                if (! is_string($value)) {
                    continue;
                }

                $translations["{$searchableLabel}_{$locale}"] = $value;
            }
        }

        return $translations;
    }

    public function scopeSearchArabic(Builder $query, string $column, string $needle): Builder
    {
        $query = $this->scopeWhereArabicLike($query, $column, $needle);

        return $this->scopeOrderByArabicRelevance($query, $column, $needle);
    }

    public function scopeSearchArabicComprehensive(
        Builder $query,
        string $column,
        string $needle = '',
        ?int $maxTerms = null,
    ): Builder {
        if ($needle === '') {
            $needle = $column;
            $column = 'content';
        }

        $query = $this->scopeWhereArabicComprehensive($query, $column, $needle, $maxTerms);

        return $this->scopeOrderByArabicRelevance($query, $column, $needle);
    }

    public function scopeWhereArabicLike(Builder $query, string $column, string $needle): Builder
    {
        $needle = trim($needle);

        if ($needle === '') {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $searchableColumn = ar_searchable($column);
        $stemColumn = ar_stem($column);

        $hasSearchableColumn = Schema::hasColumn($table, $searchableColumn);
        $hasStemColumn = Schema::hasColumn($table, $stemColumn);

        $normalizedArabic = ArabicFilter::withoutHarakat($needle);
        $normalizedSearch = ArabicFilter::forSearch($needle);
        $normalizedStem = ArabicFilter::forStem($needle);

        return $query->where(function (Builder $inner) use (
            $column,
            $searchableColumn,
            $stemColumn,
            $hasSearchableColumn,
            $hasStemColumn,
            $normalizedArabic,
            $normalizedSearch,
            $normalizedStem,
        ): void {
            $inner->where($column, 'like', "%{$normalizedArabic}%");

            if ($hasSearchableColumn) {
                $inner->orWhere($searchableColumn, 'like', "%{$normalizedSearch}%");
            }

            if ($hasStemColumn && $normalizedStem !== '') {
                $inner->orWhere($stemColumn, 'like', "%{$normalizedStem}%");
            }
        });
    }

    public function scopeWhereArabicComprehensive(
        Builder $query,
        string $column,
        string $needle = '',
        ?int $maxTerms = null,
    ): Builder {
        if ($needle === '') {
            $needle = $column;
            $column = 'content';
        }

        $needle = trim($needle);

        if ($needle === '') {
            return $query;
        }

        $maxTerms = $maxTerms ?? (int) ArabicableConfig::get('arabicable.search.comprehensive.max_terms', 60);
        $plan = Arabic::buildComprehensiveSearchPlan($needle, max(1, $maxTerms));
        $terms = $plan['terms'];

        if ($terms === []) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $searchableColumn = ar_searchable($column);
        $stemColumn = ar_stem($column);

        $hasSearchableColumn = Schema::hasColumn($table, $searchableColumn);
        $hasStemColumn = Schema::hasColumn($table, $stemColumn);

        return $query->where(function (Builder $inner) use (
            $column,
            $searchableColumn,
            $stemColumn,
            $hasSearchableColumn,
            $hasStemColumn,
            $terms,
        ): void {
            $first = true;

            foreach ($terms as $term) {
                if (trim($term) === '') {
                    continue;
                }

                $normalizedArabic = ArabicFilter::withoutHarakat($term);
                $normalizedSearch = ArabicFilter::forSearch($term);
                $normalizedStem = ArabicFilter::forStem($term);

                if ($first) {
                    $inner->where($column, 'like', "%{$normalizedArabic}%");
                    $first = false;
                } else {
                    $inner->orWhere($column, 'like', "%{$normalizedArabic}%");
                }

                if ($hasSearchableColumn) {
                    $inner->orWhere($searchableColumn, 'like', "%{$normalizedSearch}%");
                }

                if ($hasStemColumn && $normalizedStem !== '') {
                    $inner->orWhere($stemColumn, 'like', "%{$normalizedStem}%");
                }
            }
        });
    }

    public function scopeOrderByArabicRelevance(Builder $query, string $column, string $needle): Builder
    {
        $needle = trim($needle);

        if ($needle === '') {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $searchableColumn = ar_searchable($column);
        $stemColumn = ar_stem($column);

        $hasSearchableColumn = Schema::hasColumn($table, $searchableColumn);
        $hasStemColumn = Schema::hasColumn($table, $stemColumn);

        $weights = ArabicableConfig::get('arabicable.search.ranking_weights', []);
        $exactWeight = (int) ($weights['exact'] ?? 100);
        $searchableWeight = (int) ($weights['searchable'] ?? 80);
        $stemWeight = (int) ($weights['stemmed'] ?? 60);
        $tokenWeight = (int) ($weights['token_overlap'] ?? 40);

        $normalizedArabic = ArabicFilter::withoutHarakat($needle);
        $normalizedSearch = ArabicFilter::forSearch($needle);
        $normalizedStem = ArabicFilter::forStem($needle);
        $tokens = array_values(array_filter(explode(' ', $normalizedSearch), static fn (string $part): bool => $part !== ''));

        $grammar = $query->getQuery()->getGrammar();
        $baseColumn = $grammar->wrap($table.'.'.$column);

        $parts = [];
        $bindings = [];

        $parts[] = "CASE WHEN {$baseColumn} = ? THEN {$exactWeight} ELSE 0 END";
        $bindings[] = $normalizedArabic;

        if ($hasSearchableColumn) {
            $wrappedSearchColumn = $grammar->wrap($table.'.'.$searchableColumn);

            $parts[] = "CASE WHEN {$wrappedSearchColumn} = ? THEN {$searchableWeight} ELSE 0 END";
            $bindings[] = $normalizedSearch;

            if ($tokens !== []) {
                $distributedTokenWeight = max(1, intdiv($tokenWeight, max(1, count($tokens))));

                foreach ($tokens as $token) {
                    $parts[] = "CASE WHEN {$wrappedSearchColumn} LIKE ? THEN {$distributedTokenWeight} ELSE 0 END";
                    $bindings[] = "%{$token}%";
                }
            }
        }

        if ($hasStemColumn && $normalizedStem !== '') {
            $wrappedStemColumn = $grammar->wrap($table.'.'.$stemColumn);
            $parts[] = "CASE WHEN {$wrappedStemColumn} = ? THEN {$stemWeight} ELSE 0 END";
            $bindings[] = $normalizedStem;
        }

        $expression = implode(' + ', $parts);

        return $query
            ->orderByRaw("({$expression}) DESC", $bindings)
            ->orderBy($query->getModel()->getQualifiedKeyName());
    }
}
