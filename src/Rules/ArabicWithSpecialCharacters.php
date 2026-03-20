<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Rules;

use Closure;
use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use GoodMaven\Arabicable\Support\Data\ValueSelector;
use GoodMaven\Arabicable\Support\Exceptions\ArabicableRuleException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

final class ArabicWithSpecialCharacters implements ValidationRule
{
    /**
     * @param  ArabicSpecialCharacters|array<int, ArabicSpecialCharacters>  $except
     * @param  ArabicSpecialCharacters|array<int, ArabicSpecialCharacters>  $only
     */
    public function __construct(
        private ArabicSpecialCharacters|array $except = [],
        private ArabicSpecialCharacters|array $only = [],
    ) {
        $except = Arr::wrap($this->except);
        $only = Arr::wrap($this->only);

        if (
            ($except !== [] && ! ValueSelector::isEnumArray($except, ArabicSpecialCharacters::class))
            || ($only !== [] && ! ValueSelector::isEnumArray($only, ArabicSpecialCharacters::class))
        ) {
            throw new ArabicableRuleException(
                "Only ArabicSpecialCharacters enum cases are allowed for 'except' and 'only' properties.",
            );
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('يجب أن يحتوي حقل :attribute على الأحرف العربية والعلامات الخاصة الموضحة فقط .');

            return;
        }

        $specialCharacterCases = ValueSelector::filterEnums(
            ArabicSpecialCharacters::cases(),
            $this->only,
            $this->except,
        );

        $arabicLetters = 'اأإآبتثجحخدذرزسشصضطظعغفقكلمنهويىءئة';
        $specialCharacters = collect($specialCharacterCases)
            ->map(static fn (ArabicSpecialCharacters $item): array => $item->get())
            ->collapse()
            ->toArray();

        $pattern = '/^['.preg_quote($arabicLetters.implode('', $specialCharacters), '/').'\s]+$/u';

        if (preg_match($pattern, $value) === 1) {
            return;
        }

        $fail('يجب أن يحتوي حقل :attribute على الأحرف العربية والعلامات الخاصة الموضحة فقط .');
    }
}
