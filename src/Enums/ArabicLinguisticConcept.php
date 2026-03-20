<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Enums;

enum ArabicLinguisticConcept: string
{
    case All = 'all';
    case CommonTexts = 'common-texts';
    case StopWords = 'stop-words';
    case Vocalizations = 'vocalizations';
    case MorphVariants = 'morph-variants';
}
