<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Observers;

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class ArabicableModelObserver
{
    /**
     * @var array<string, array<string, bool>>
     */
    private static array $columnExistenceCache = [];

    public function creating(Model $model): void
    {
        $this->handleArabicAttributes($model);
    }

    public function updating(Model $model): void
    {
        $this->handleArabicAttributes($model, true);
    }

    private function handleArabicAttributes(Model $model, bool $checkDirty = false): void
    {
        $tableName = $model->getTable();

        foreach ($model->getAttributes() as $key => $value) {
            if ($checkDirty && ! $model->isDirty($key)) {
                continue;
            }

            if ($this->shouldHandleArabic($tableName, $key)) {
                $sourceValue = $this->resolveSourceValue($model, $key, $value);

                if ($sourceValue !== null) {
                    $this->updateArabicAttributes($model, $tableName, $key, $sourceValue);
                } elseif ($this->shouldClearArabicColumns($model, $key, $value)) {
                    $this->clearArabicAttributes($model, $tableName, $key, $value);
                }

                continue;
            }

            if ($this->shouldHandleIndianNumbers($tableName, $key) && is_string($value)) {
                $model->{ar_indian($key)} = Arabic::convertNumeralsToIndian($value);
            }
        }
    }

    private function shouldHandleArabic(string $tableName, string $key): bool
    {
        return $this->hasColumnCached($tableName, ar_with_harakat($key))
            && $this->hasColumnCached($tableName, ar_searchable($key));
    }

    private function shouldHandleIndianNumbers(string $tableName, string $key): bool
    {
        return $this->hasColumnCached($tableName, ar_indian($key));
    }

    private function resolveSourceValue(Model $model, string $key, mixed $value): ?string
    {
        $isTranslatable = $this->isSpatieTranslatableAttribute($model, $key);

        if ($isTranslatable) {
            if (is_array($value)) {
                $arabic = Arr::get($value, 'ar');

                return is_string($arabic) ? $arabic : null;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    $arabic = Arr::get($decoded, 'ar');

                    return is_string($arabic) ? $arabic : null;
                }
            }

            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    private function updateArabicAttributes(Model $model, string $tableName, string $key, string $value): void
    {
        $model->{ar_with_harakat($key)} = ArabicFilter::withHarakat($value);
        $model->{ar_searchable($key)} = ArabicFilter::forSearch($value);
        if ($this->hasColumnCached($tableName, ar_stem($key))) {
            $model->{ar_stem($key)} = ArabicFilter::forStem($value);
        }

        if ($this->isSpatieTranslatableAttribute($model, $key)) {
            $existing = (array) $model->getAttribute($key);
            $existing['ar'] = ArabicFilter::withoutHarakat($value);
            $model->{$key} = $existing;

            return;
        }

        $model->{$key} = ArabicFilter::withoutHarakat($value);
    }

    private function isSpatieTranslatableAttribute(Model $model, string $key): bool
    {
        if (method_exists($model, 'getTranslatableAttributes')) {
            /** @var mixed $attributes */
            $attributes = $model->getTranslatableAttributes();

            if (is_array($attributes) && in_array($key, $attributes, true)) {
                return true;
            }
        }

        $casts = $model->getCasts();

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        return in_array($casts[$key], ['array', 'json'], true);
    }

    private function shouldClearArabicColumns(Model $model, string $key, mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $isTranslatable = $this->isSpatieTranslatableAttribute($model, $key);

        if (! $isTranslatable) {
            if (is_string($value)) {
                return trim($value) === '';
            }

            return false;
        }

        if (is_array($value) && array_key_exists('ar', $value)) {
            $arabic = $value['ar'];

            return $arabic === null || (is_string($arabic) && trim($arabic) === '');
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && array_key_exists('ar', $decoded)) {
                $arabic = $decoded['ar'];

                return $arabic === null || (is_string($arabic) && trim($arabic) === '');
            }
        }

        return false;
    }

    private function clearArabicAttributes(Model $model, string $tableName, string $key, mixed $value): void
    {
        if ($this->hasColumnCached($tableName, ar_with_harakat($key))) {
            $model->{ar_with_harakat($key)} = null;
        }

        if ($this->hasColumnCached($tableName, ar_searchable($key))) {
            $model->{ar_searchable($key)} = null;
        }

        if ($this->hasColumnCached($tableName, ar_stem($key))) {
            $model->{ar_stem($key)} = null;
        }

        if (! $this->isSpatieTranslatableAttribute($model, $key)) {
            if ($value === null) {
                $model->{$key} = null;
            }

            return;
        }

        $existing = (array) $model->getAttribute($key);
        if (array_key_exists('ar', $existing)) {
            $existing['ar'] = null;
            $model->{$key} = $existing;
        }
    }

    private function hasColumnCached(string $tableName, string $column): bool
    {
        if (! array_key_exists($tableName, self::$columnExistenceCache)) {
            self::$columnExistenceCache[$tableName] = [];
        }

        if (! array_key_exists($column, self::$columnExistenceCache[$tableName])) {
            self::$columnExistenceCache[$tableName][$column] = Schema::hasColumn($tableName, $column);
        }

        return self::$columnExistenceCache[$tableName][$column];
    }
}
