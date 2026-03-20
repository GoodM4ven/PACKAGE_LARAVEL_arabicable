<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Text;

class ArabicStemmer
{
    /**
     * @var array<int, string>
     */
    private const PROTECTED_LEXEMES = [
        'الله',
        'لله',
        'بالله',
        'والله',
        'تالله',
        'اللهم',
    ];

    /**
     * @var array<string, string>
     */
    private const POSSESSIVE_FEMININE_BASE_OVERRIDES = [
        'أمت' => 'أمة',
        'رحمت' => 'رحمة',
        'نعمت' => 'نعمة',
    ];

    private string $verbPre = 'وأسفلي';

    private string $verbPost = 'ومكانيه';

    private int $verbMaxPre = 4;

    private int $verbMaxPost = 6;

    private int $verbMinStem = 2;

    private string $nounPre = 'ابفكلوأ';

    private string $nounPost = 'اتةكمنهوي';

    private int $nounMaxPre = 4;

    private int $nounMaxPost = 6;

    private int $nounMinStem = 2;

    private string $verbMay;

    private string $nounMay;

    public function __construct()
    {
        $this->verbMay = $this->verbPre.$this->verbPost;
        $this->nounMay = $this->nounPre.$this->nounPost;
    }

    public function stem(string $word): string
    {
        $word = trim($word);

        if ($word === '') {
            return '';
        }

        if (in_array($word, self::PROTECTED_LEXEMES, true)) {
            return $word;
        }

        $candidate = $this->stripPossessiveSuffix($word);

        $nounStem = $this->roughStem(
            $candidate,
            $this->nounMay,
            $this->nounPre,
            $this->nounPost,
            $this->nounMaxPre,
            $this->nounMaxPost,
            $this->nounMinStem,
        );

        $verbStem = $this->roughStem(
            $candidate,
            $this->verbMay,
            $this->verbPre,
            $this->verbPost,
            $this->verbMaxPre,
            $this->verbMaxPost,
            $this->verbMinStem,
        );

        if ($nounStem === null && $verbStem === null) {
            return $candidate;
        }

        if ($nounStem === null) {
            return $verbStem;
        }

        if ($verbStem === null) {
            return $nounStem;
        }

        $stem = mb_strlen($nounStem, 'UTF-8') <= mb_strlen($verbStem, 'UTF-8')
            ? $nounStem
            : $verbStem;

        // Avoid overly aggressive feminine marker stripping on short ambiguous stems.
        if ($this->isLikelyOverStrippedFeminine($candidate, $stem)) {
            return $candidate;
        }

        return $stem;
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    public function stemMany(array $words): array
    {
        return array_map(fn (string $word): string => $this->stem($word), $words);
    }

    private function roughStem(
        string $word,
        string $notChars,
        string $preChars,
        string $postChars,
        int $maxPre,
        int $maxPost,
        int $minStem,
    ): ?string {
        $right = -1;
        $left = -1;
        $max = mb_strlen($word, 'UTF-8');

        for ($i = 0; $i < $max; $i++) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');

            if (mb_strpos($notChars, $needle, 0, 'UTF-8') !== false) {
                continue;
            }

            if ($right === -1) {
                $right = $i;
            }

            $left = $i;
        }

        if ($right === -1 || $left === -1) {
            return null;
        }

        if ($right > $maxPre) {
            $right = $maxPre;
        }

        if ($max - $left - 1 > $maxPost) {
            $left = $max - $maxPost - 1;
        }

        for ($i = 0; $i < $right; $i++) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');

            if (mb_strpos($preChars, $needle, 0, 'UTF-8') !== false) {
                continue;
            }

            $right = $i;

            break;
        }

        for ($i = $max - 1; $i > $left; $i--) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');

            if (mb_strpos($postChars, $needle, 0, 'UTF-8') !== false) {
                continue;
            }

            $left = $i;

            break;
        }

        if ($left - $right < $minStem) {
            return null;
        }

        return mb_substr($word, $right, $left - $right + 1, 'UTF-8');
    }

    private function stripPossessiveSuffix(string $word): string
    {
        $suffixes = ['كما', 'كم', 'كن', 'هم', 'هن', 'نا', 'ها', 'ه'];

        foreach ($suffixes as $suffix) {
            if (! str_ends_with($word, $suffix)) {
                continue;
            }

            $baseLength = mb_strlen($word, 'UTF-8') - mb_strlen($suffix, 'UTF-8');

            if ($baseLength < 3) {
                continue;
            }

            $base = mb_substr($word, 0, $baseLength, 'UTF-8');

            if (array_key_exists($base, self::POSSESSIVE_FEMININE_BASE_OVERRIDES)) {
                return self::POSSESSIVE_FEMININE_BASE_OVERRIDES[$base];
            }

            return $base;
        }

        return $word;
    }

    private function isLikelyOverStrippedFeminine(string $source, string $stem): bool
    {
        if (! str_ends_with($source, 'ة')) {
            return false;
        }

        if ($stem !== mb_substr($source, 0, -1, 'UTF-8')) {
            return false;
        }

        return mb_strlen($stem, 'UTF-8') <= 3;
    }
}
