<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Rules;

use Closure;
use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Facades\Arabic;
use Illuminate\Contracts\Validation\ValidationRule;

final class UncommonArabic implements ValidationRule
{
    /**
     * @param  array<int, CommonArabicTextType>  $excludedTypes
     */
    public function __construct(
        private array $excludedTypes = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('النص الخاص بحقل :attribute يحتوي على كلمات شائعة فحسب .');

            return;
        }

        if ((string) Arabic::removeCommons($value, $this->excludedTypes, asString: true) !== '') {
            return;
        }

        $fail('النص الخاص بحقل :attribute يحتوي على كلمات شائعة فحسب .');
    }
}
