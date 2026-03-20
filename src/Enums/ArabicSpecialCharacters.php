<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Enums;

use GoodMaven\Arabicable\Support\Config\ArabicableConfig;

enum ArabicSpecialCharacters: string
{
    case Harakat = 'harakat';

    case IndianNumerals = 'indian-numerals';
    case ArabicNumerals = 'arabic-numerals';

    case PunctuationMarks = 'punctuation-marks';
    case ForeignPunctuationMarks = 'foreign-punctuation-marks';
    case ArabicPunctuationMarks = 'arabic-punctuation-marks';

    case EnclosingMarks = 'enclosing-marks';
    case EnclosingStarterMarks = 'enclosing-starter-marks';
    case EnclosingEnderMarks = 'enclosing-ender-marks';
    case ArabicEnclosingMarks = 'arabic-enclosing-marks';
    case ArabicEnclosingStarterMarks = 'arabic-enclosing-starter-marks';
    case ArabicEnclosingEnderMarks = 'arabic-enclosing-ender-marks';

    /**
     * @return array<int, string>
     */
    public function get(): array
    {
        return match ($this) {
            self::Harakat => ArabicableConfig::get('arabicable.special_characters.harakat', []),
            self::IndianNumerals => ArabicableConfig::get('arabicable.special_characters.indian_numerals', []),
            self::ArabicNumerals => ArabicableConfig::get('arabicable.special_characters.arabic_numerals', []),
            self::PunctuationMarks => ArabicableConfig::get('arabicable.special_characters.punctuation_marks', []),
            self::ForeignPunctuationMarks => ArabicableConfig::get('arabicable.special_characters.foreign_punctuation_marks', []),
            self::ArabicPunctuationMarks => ArabicableConfig::get('arabicable.special_characters.arabic_punctuation_marks', []),
            self::EnclosingMarks => ArabicableConfig::get('arabicable.special_characters.enclosing_marks', []),
            self::EnclosingStarterMarks => ArabicableConfig::get('arabicable.special_characters.enclosing_starter_marks', []),
            self::EnclosingEnderMarks => ArabicableConfig::get('arabicable.special_characters.enclosing_ender_marks', []),
            self::ArabicEnclosingMarks => ArabicableConfig::get('arabicable.special_characters.arabic_enclosing_marks', []),
            self::ArabicEnclosingStarterMarks => ArabicableConfig::get('arabicable.special_characters.arabic_enclosing_starter_marks', []),
            self::ArabicEnclosingEnderMarks => ArabicableConfig::get('arabicable.special_characters.arabic_enclosing_ender_marks', []),
        };
    }
}
