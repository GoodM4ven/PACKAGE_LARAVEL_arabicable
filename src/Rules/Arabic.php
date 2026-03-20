<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Arabic implements ValidationRule
{
    public function __construct(
        private bool $withHarakat = false,
        private bool $withPunctuation = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('يمكن لحقل :attribute احتواء حروف عربية فقط .');

            return;
        }

        $pattern = '/^[\x{0621}-\x{063A}\x{0641}-\x{064A}\s';

        if ($this->withHarakat) {
            $pattern .= '\x{064B}-\x{065F}';
        }

        if ($this->withPunctuation) {
            $pattern .= '\x{060C}\x{061B}\x{061F}\x{066B}\x{066C}\x{066D}\x{06D4}\x{2026}\x{0021}\x{002E}';
        }

        $pattern .= ']*$/u';

        if (preg_match($pattern, $value) === 1) {
            return;
        }

        $fail(match (true) {
            default => 'يمكن لحقل :attribute احتواء حروف عربية فقط .',
            $this->withHarakat && ! $this->withPunctuation => 'يمكن لحقل :attribute احتواء الحروف العربية والحركات فقط .',
            ! $this->withHarakat && $this->withPunctuation => 'يمكن لحقل :attribute احتواء الحروف العربية وأدوات التنقيط فقط .',
            $this->withHarakat && $this->withPunctuation => 'يمكن لحقل :attribute احتواء الحروف العربية والحركات وأدوات التنقيط فقط .',
        });
    }
}
