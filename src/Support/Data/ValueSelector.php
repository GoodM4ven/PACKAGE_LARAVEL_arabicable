<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Data;

use UnitEnum;

class ValueSelector
{
    /**
     * @template TItem of UnitEnum
     *
     * @param  array<int, TItem>  $items
     * @param  array<int, TItem>|TItem  $only
     * @param  array<int, TItem>|TItem  $except
     * @return array<int, TItem>
     */
    public static function filterEnums(array $items, array|UnitEnum $only = [], array|UnitEnum $except = []): array
    {
        $onlyItems = self::wrapEnums($only);
        $exceptItems = self::wrapEnums($except);

        if ($onlyItems !== []) {
            $items = array_values(array_filter(
                $items,
                static fn (UnitEnum $item): bool => in_array($item, $onlyItems, true),
            ));
        }

        if ($exceptItems !== []) {
            $items = array_values(array_filter(
                $items,
                static fn (UnitEnum $item): bool => ! in_array($item, $exceptItems, true),
            ));
        }

        return $items;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    public static function isEnumArray(array $values, string $enumClass): bool
    {
        if (! enum_exists($enumClass)) {
            return false;
        }

        foreach ($values as $value) {
            if (! $value instanceof $enumClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, UnitEnum>|UnitEnum  $values
     * @return array<int, UnitEnum>
     */
    private static function wrapEnums(array|UnitEnum $values): array
    {
        if ($values instanceof UnitEnum) {
            return [$values];
        }

        return array_values($values);
    }
}
