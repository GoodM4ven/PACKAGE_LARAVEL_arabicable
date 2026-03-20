<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Database\Factories;

use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Models\CommonArabicText;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommonArabicText>
 */
class CommonArabicTextFactory extends Factory
{
    public function modelName(): string
    {
        return (string) ArabicableConfig::get('arabicable.common_arabic_text.model');
    }

    public function definition(): array
    {
        return [
            'type' => CommonArabicTextType::random(),
            'content' => fake('ar_SA')->unique()->sentence(rand(1, 4)),
        ];
    }
}
