<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Rules;

use Closure;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class UniqueArabicWithSpecialCharacters implements ValidationRule
{
    public function __construct(
        private string $modelClass,
        private ?string $propertyName = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $property = $this->propertyName ?? $attribute;
        $modelClass = $this->modelClass;

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            $fail("The model class '{$modelClass}' for :attribute does not exist.");

            return;
        }

        /** @var Model $model */
        $model = new $modelClass;
        $searchableField = ar_searchable($property);
        $normalized = ArabicFilter::forSearch($value);

        if (! Schema::hasColumn($model->getTable(), $searchableField)) {
            $fail('The searchable column for :attribute is missing.');

            return;
        }

        if (! $modelClass::query()->where($searchableField, $normalized)->exists()) {
            return;
        }

        $fail('A match for :attribute was found, therefore it is not unique.');
    }
}
