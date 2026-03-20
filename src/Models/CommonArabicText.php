<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Models;

use GoodMaven\Arabicable\Enums\CommonArabicTextType;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use GoodMaven\Arabicable\Traits\Arabicable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonArabicText extends Model
{
    use Arabicable;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'content',
    ];

    /**
     * @return array<string, class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommonArabicTextType::class,
        ];
    }

    protected static function newFactory(): Factory
    {
        /** @var class-string<Factory> $factory */
        $factory = ArabicableConfig::get('arabicable.common_arabic_text.factory');

        return $factory::new();
    }
}
