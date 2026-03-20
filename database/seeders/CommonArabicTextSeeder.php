<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Database\Seeders;

use GoodMaven\Arabicable\Enums\ArabicLinguisticConcept;
use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Models\CommonArabicText;
use Illuminate\Database\Seeder;

class CommonArabicTextSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['type' => CommonArabicTextType::Separator->value, 'content' => 'من'],
            ['type' => CommonArabicTextType::Separator->value, 'content' => 'هو'],
            ['type' => CommonArabicTextType::Separator->value, 'content' => 'كان'],
            ['type' => CommonArabicTextType::Verb->value, 'content' => 'قال'],
            ['type' => CommonArabicTextType::Noun->value, 'content' => 'رجل'],
            ['type' => CommonArabicTextType::Name->value, 'content' => 'الله'],
            ['type' => CommonArabicTextType::Sentence->value, 'content' => 'صلى الله عليه وسلم'],
        ];

        CommonArabicText::query()->upsert($records, ['type', 'content']);

        Arabic::clearConceptCache(ArabicLinguisticConcept::CommonTexts);
    }
}
