<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Enums;

enum CommonArabicTextType: string
{
    case Separator = 'separator';
    case Verb = 'verb';
    case Noun = 'noun';
    case Name = 'name';
    case Sentence = 'sentence';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
