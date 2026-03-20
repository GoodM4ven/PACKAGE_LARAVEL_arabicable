<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Concerns;

use Illuminate\Database\Schema\Blueprint;

trait HasArabicableMigrationBlueprintMacros
{
    public function arabicableMigrationBlueprintMacros(): void
    {
        Blueprint::macro('indianDate', function (string $columnName, bool $isNullable = false, bool $isUnique = false): Blueprint {
            $field = $this->date($columnName);
            if ($isNullable) {
                $field->nullable();
            }
            if ($isUnique) {
                $field->unique();
            }

            $indianField = $this->string(ar_indian($columnName), 10);
            if ($isNullable) {
                $indianField->nullable();
            }
            if ($isUnique) {
                $indianField->unique();
            }

            return $this;
        });

        Blueprint::macro(
            'arabicString',
            function (
                string $columnName,
                int $length = 255,
                bool $isNullable = false,
                bool $isUnique = false,
                bool $supportsFullSearch = false,
                ?bool $isTranslatable = null,
            ): Blueprint {
                $supportsFullSearch = app()->environment('testing') ? false : $supportsFullSearch;

                $field = $isTranslatable
                    ? $this->json($columnName)
                    : $this->string($columnName, $length);

                if ($isNullable) {
                    $field->nullable();
                }
                if ($isUnique) {
                    $field->unique();
                }

                $searchField = $this->string(ar_searchable($columnName), $length);
                if ($isNullable) {
                    $searchField->nullable();
                }
                if ($supportsFullSearch) {
                    $searchField->fulltext();
                }

                $harakatField = $this->string(ar_with_harakat($columnName), $length);
                if ($isNullable) {
                    $harakatField->nullable();
                }

                $stemField = $this->string(ar_stem($columnName), $length);
                if ($isNullable) {
                    $stemField->nullable();
                }

                return $this;
            },
        );

        Blueprint::macro(
            'arabicTinyText',
            function (
                string $columnName,
                bool $isNullable = false,
                bool $isUnique = false,
                bool $supportsFullSearch = false,
                ?bool $isTranslatable = null,
            ): Blueprint {
                $supportsFullSearch = app()->environment('testing') ? false : $supportsFullSearch;

                $field = $isTranslatable
                    ? $this->json($columnName)
                    : $this->tinyText($columnName);

                if ($isNullable) {
                    $field->nullable();
                }
                if ($isUnique) {
                    $field->unique();
                }

                $searchField = $this->tinyText(ar_searchable($columnName));
                if ($isNullable) {
                    $searchField->nullable();
                }
                if ($supportsFullSearch) {
                    $searchField->fulltext();
                }

                $harakatField = $this->tinyText(ar_with_harakat($columnName));
                if ($isNullable) {
                    $harakatField->nullable();
                }

                $stemField = $this->tinyText(ar_stem($columnName));
                if ($isNullable) {
                    $stemField->nullable();
                }

                return $this;
            },
        );

        Blueprint::macro(
            'arabicText',
            function (
                string $columnName,
                bool $isNullable = false,
                bool $isUnique = false,
                ?bool $isTranslatable = null,
            ): Blueprint {
                $field = $isTranslatable
                    ? $this->json($columnName)
                    : $this->text($columnName);

                if ($isNullable) {
                    $field->nullable();
                }
                if ($isUnique) {
                    $field->unique();
                }

                $searchField = $this->text(ar_searchable($columnName));
                if ($isNullable) {
                    $searchField->nullable();
                }
                if (! app()->environment('testing')) {
                    $searchField->fulltext();
                }

                $harakatField = $this->text(ar_with_harakat($columnName));
                if ($isNullable) {
                    $harakatField->nullable();
                }

                $stemField = $this->text(ar_stem($columnName));
                if ($isNullable) {
                    $stemField->nullable();
                }

                return $this;
            },
        );

        Blueprint::macro(
            'arabicMediumText',
            function (
                string $columnName,
                bool $isNullable = false,
                bool $isUnique = false,
                ?bool $isTranslatable = null,
            ): Blueprint {
                $field = $isTranslatable
                    ? $this->json($columnName)
                    : $this->mediumText($columnName);

                if ($isNullable) {
                    $field->nullable();
                }
                if ($isUnique) {
                    $field->unique();
                }

                $searchField = $this->mediumText(ar_searchable($columnName));
                if ($isNullable) {
                    $searchField->nullable();
                }
                if (! app()->environment('testing')) {
                    $searchField->fulltext();
                }

                $harakatField = $this->mediumText(ar_with_harakat($columnName));
                if ($isNullable) {
                    $harakatField->nullable();
                }

                $stemField = $this->mediumText(ar_stem($columnName));
                if ($isNullable) {
                    $stemField->nullable();
                }

                return $this;
            },
        );

        Blueprint::macro(
            'arabicLongText',
            function (
                string $columnName,
                bool $isNullable = false,
                bool $isUnique = false,
                ?bool $isTranslatable = null,
            ): Blueprint {
                $field = $isTranslatable
                    ? $this->json($columnName)
                    : $this->longText($columnName);

                if ($isNullable) {
                    $field->nullable();
                }
                if ($isUnique) {
                    $field->unique();
                }

                $searchField = $this->longText(ar_searchable($columnName));
                if ($isNullable) {
                    $searchField->nullable();
                }
                if (! app()->environment('testing')) {
                    $searchField->fulltext();
                }

                $harakatField = $this->longText(ar_with_harakat($columnName));
                if ($isNullable) {
                    $harakatField->nullable();
                }

                $stemField = $this->longText(ar_stem($columnName));
                if ($isNullable) {
                    $stemField->nullable();
                }

                return $this;
            },
        );
    }
}
