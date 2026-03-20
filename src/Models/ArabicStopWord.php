<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Models;

use Illuminate\Database\Eloquent\Model;

class ArabicStopWord extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'word',
        'vocalized',
        'lemma',
        'type',
        'category',
        'stem',
        'tags',
        'source',
    ];
}
