<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Text;

use GoodMaven\Arabicable\Models\ArabicStopWord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ArabicStopWords
{
    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_keys($this->vocalizedMap());
    }

    public function isStopWord(string $word): bool
    {
        return array_key_exists($word, $this->vocalizedMap());
    }

    /**
     * @return array<string, string>
     */
    public function vocalizedMap(): array
    {
        return Cache::rememberForever('arabicable.stop_words.map', function (): array {
            if (! Schema::hasTable('arabic_stop_words')) {
                return [];
            }

            return ArabicStopWord::query()
                ->get(['word', 'vocalized'])
                ->filter(static fn (ArabicStopWord $row): bool => trim((string) $row->word) !== '')
                ->mapWithKeys(static function (ArabicStopWord $row): array {
                    $word = trim((string) $row->word);
                    $vocalized = trim((string) ($row->vocalized ?? ''));

                    return [$word => ($vocalized !== '' ? $vocalized : $word)];
                })
                ->all();
        });
    }

    public function vocalize(string $word): string
    {
        $map = $this->vocalizedMap();

        return $map[$word] ?? $word;
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    public function removeFrom(array $words): array
    {
        $lookup = $this->vocalizedMap();

        return array_values(array_filter(
            $words,
            static fn (string $word): bool => $word !== '' && ! array_key_exists($word, $lookup),
        ));
    }

    public function clearCache(): void
    {
        Cache::forget('arabicable.stop_words.map');
    }
}
