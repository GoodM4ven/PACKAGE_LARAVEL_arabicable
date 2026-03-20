<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use GoodMaven\Arabicable\Facades\Arabic;
use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Config\ArabicableConfig;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Demo extends Component
{
    private const EXAMPLE_QUERY = 'مَن لَم يَدَعْ قَولَ الزُّورِ والعَمَلَ به والجَهلَ، فليس لله حاجة في أن يدع طعامه وشرابه...';

    private const PROTECTED_LEXEMES = [
        'الله',
        'لله',
        'بالله',
        'والله',
        'تالله',
        'اللهم',
    ];

    public string $query = '';

    public string $activeQuery = '';

    public string $numeralsMode = 'arabic';

    public string $variantMode = 'all';

    public bool $enableComprehensiveSearch = false;

    public bool $enableSanitization = true;

    public bool $enableCommonsRemoval = false;

    /**
     * @var array<int, array{role: string, content: string}>
     */
    public array $chatMessages = [];

    /**
     * @var array<string, mixed>
     */
    public array $analysis = [];

    public string $streamingReply = '';

    public string $streamingStatus = 'جاهز';

    public int $gregorianYear = 0;

    public int $gregorianMonth = 0;

    public int $gregorianDay = 0;

    public int $hijriYear = 0;

    public int $hijriMonth = 0;

    public int $hijriDay = 0;

    public string $dateConversionStatus = 'جاهز';

    public function mount(): void
    {
        $this->numeralsMode = (string) ArabicableConfig::get('arabicable.numerals.search_mode', 'arabic');
        $this->variantMode = (string) ArabicableConfig::get('arabicable.search.comprehensive.variant_mode', 'all');
        $this->analysis = $this->emptyAnalysis();

        $today = now();
        $this->gregorianYear = (int) $today->year;
        $this->gregorianMonth = (int) $today->month;
        $this->gregorianDay = (int) $today->day;

        $hijri = Arabic::gregorianToHijri($this->gregorianYear, $this->gregorianMonth, $this->gregorianDay);
        $this->hijriYear = $hijri['year'];
        $this->hijriMonth = $hijri['month'];
        $this->hijriDay = $hijri['day'];
    }

    public function updatedNumeralsMode(string $value): void
    {
        ArabicableConfig::set('arabicable.numerals.search_mode', $value);
    }

    public function clearQuery(): void
    {
        $this->query = '';
        $this->activeQuery = '';
        $this->analysis = $this->emptyAnalysis();
        $this->chatMessages = [];
        $this->streamingReply = '';
        $this->streamingStatus = 'جاهز';
    }

    public function addExampleAndRun(): void
    {
        $this->query = self::EXAMPLE_QUERY;

        // Trigger processing in a follow-up request so the input value appears first.
        $this->js('setTimeout(() => $wire.runProcessing(), 260);');
    }

    public function addExample(): void
    {
        $this->query = self::EXAMPLE_QUERY;
        $this->runProcessing();
    }

    public function convertGregorianToHijri(): void
    {
        $this->validate([
            'gregorianYear' => ['required', 'integer', 'min:1', 'max:9999'],
            'gregorianMonth' => ['required', 'integer', 'min:1', 'max:12'],
            'gregorianDay' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        try {
            $hijri = Arabic::gregorianToHijri($this->gregorianYear, $this->gregorianMonth, $this->gregorianDay);
            $this->hijriYear = $hijri['year'];
            $this->hijriMonth = $hijri['month'];
            $this->hijriDay = $hijri['day'];
            $this->dateConversionStatus = 'تم التحويل من الميلادي إلى الهجري';
        } catch (\Throwable) {
            $this->dateConversionStatus = 'تعذر تحويل التاريخ الميلادي';
        }
    }

    public function convertHijriToGregorian(): void
    {
        $this->validate([
            'hijriYear' => ['required', 'integer', 'min:1', 'max:20000'],
            'hijriMonth' => ['required', 'integer', 'min:1', 'max:12'],
            'hijriDay' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        try {
            $gregorian = Arabic::hijriToGregorian($this->hijriYear, $this->hijriMonth, $this->hijriDay);
            $this->gregorianYear = $gregorian['year'];
            $this->gregorianMonth = $gregorian['month'];
            $this->gregorianDay = $gregorian['day'];
            $this->dateConversionStatus = 'تم التحويل من الهجري إلى الميلادي';
        } catch (\Throwable) {
            $this->dateConversionStatus = 'تعذر تحويل التاريخ الهجري';
        }
    }

    public function runProcessing(): void
    {
        ArabicableConfig::set('arabicable.numerals.search_mode', $this->numeralsMode);
        ArabicableConfig::set('arabicable.search.comprehensive.variant_mode', $this->variantMode);
        $query = trim($this->query);

        if ($query === '') {
            return;
        }

        $this->activeQuery = $query;
        $this->pushChatMessage('user', $query);

        $this->beginAssistantStreaming();
        $this->analysis = $this->buildAnalysisWithStreaming($query);
        $this->finishAssistantStreaming();
    }

    public function render(): View
    {
        ArabicableConfig::set('arabicable.numerals.search_mode', $this->numeralsMode);
        ArabicableConfig::set('arabicable.search.comprehensive.variant_mode', $this->variantMode);

        return view('livewire.demo', [
            'analysis' => $this->analysis,
            'exampleQuery' => self::EXAMPLE_QUERY,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAnalysisWithStreaming(string $query): array
    {
        $this->setStreamingStatus('تهيئة النص للبحث');
        $search = ArabicFilter::forSearch($query);
        $this->appendStreamingReply('صيغة البحث (forSearch): '.$search."\n");

        $stem = implode(' ', $this->filterProtectedLexemes(Arabic::tokenize(ArabicFilter::forStem($query))));
        $this->appendStreamingReply('الصيغة المختصرة (forStem): '.$stem."\n");

        $sanitized = $this->enableSanitization
            ? Arabic::stripWeirdCharacters($query, keepHarakat: true, keepPunctuation: true)
            : $query;
        $this->appendStreamingReply('النص المنقح (stripWeirdCharacters): '.$sanitized."\n");

        $commonsRemoved = $this->enableCommonsRemoval
            ? (string) Arabic::removeCommons($query, asString: true)
            : '';
        $this->appendStreamingReply('بعد حذف الشائع (removeCommons): '.($commonsRemoved !== '' ? $commonsRemoved : '—')."\n\n");

        $this->setStreamingStatus('تحليل الكلمات');
        $tokens = Arabic::tokenize($query);
        $this->appendStreamingReply('تقسيم الكلمات (tokenize): '.$this->formatList($tokens, 120)."\n");

        $keywordPayload = Arabic::extractKeywords($query, stripCommons: false);
        $keywordPayload['keywords'] = $this->filterProtectedLexemes(
            $keywordPayload['keywords'],
        );
        $this->appendStreamingReply('الكلمات المفتاحية (extractKeywords): '.$this->formatList($keywordPayload['keywords'], 120)."\n");

        $this->setStreamingStatus('اشتقاق كلمات الأساس');
        $tokenBasis = $this->filterProtectedLexemes(array_values(array_unique(Arabic::removeStopWords($tokens))));
        $this->appendStreamingReply('كلمات الأساس بعد تنقية أدوات الربط: '.$this->formatList($tokenBasis, 120)."\n");

        $stems = $this->stemTokensWithStreaming($tokenBasis);
        $this->appendStreamingReply('الجذور الناتجة (stemWords): '.$this->formatList($stems, 120)."\n");

        $terms = $this->filterProtectedLexemes(array_values(array_unique(array_filter(array_merge(
            [$search],
            $tokenBasis,
            $stems,
        ), static fn (string $term): bool => trim($term) !== ''))));
        $terms = array_slice($terms, 0, 40);

        $variants = [];
        $variantTruncated = false;
        $hasLongQueryProfile = count($tokenBasis) > 4 || mb_strlen($query) > 32;

        if ($this->enableComprehensiveSearch) {
            $this->setStreamingStatus('توسيع المصطلحات');
            $this->appendStreamingReply("بدء التوسيع التدريجي للمصطلحات...\n");

            $maxVariantsPerToken = $this->variantMode === 'all' ? 8 : 14;
            $maxVariantTerms = $this->variantMode === 'all' ? 56 : 90;
            $maxTokensToExpand = $this->variantMode === 'all' ? 4 : 6;
            $variantTimeBudgetMs = $this->variantMode === 'all' ? 1400.0 : 2400.0;

            if ($hasLongQueryProfile) {
                $maxVariantsPerToken = min($maxVariantsPerToken, 6);
                $maxVariantTerms = min($maxVariantTerms, 24);
                $maxTokensToExpand = min($maxTokensToExpand, 2);
                $variantTimeBudgetMs = min($variantTimeBudgetMs, 700.0);
            }

            ArabicableConfig::set('arabicable.search.comprehensive.max_word_variants_per_token', $maxVariantsPerToken);
            ArabicableConfig::set('arabicable.search.comprehensive.max_variant_terms', $maxVariantTerms);

            $variantCandidates = array_slice(
                $this->buildVariantCandidates($tokenBasis, $keywordPayload['keywords']),
                0,
                $maxTokensToExpand,
            );
            $this->appendStreamingReply('مرشحو الاشتقاق: '.$this->formatList($variantCandidates, 30)."\n");

            $variantStartTime = microtime(true);
            $candidateCount = count($variantCandidates);

            foreach ($variantCandidates as $index => $token) {
                $elapsedMs = (microtime(true) - $variantStartTime) * 1000;

                if ($elapsedMs >= $variantTimeBudgetMs || count($variants) >= $maxVariantTerms) {
                    $variantTruncated = true;

                    break;
                }

                $this->setStreamingStatus(sprintf('توليد اشتقاقات (%d/%d): %s', $index + 1, $candidateCount, $token));
                $this->appendStreamingReply(sprintf("فحص «%s» (%d/%d)...\n", $token, $index + 1, $candidateCount));

                $tokenVariants = Arabic::expandWordVariants(
                    words: $token,
                    maxVariantsPerToken: $maxVariantsPerToken,
                    maxTerms: $maxVariantsPerToken,
                    mode: $this->variantMode,
                    stripStopWords: true,
                );

                $tokenVariants = $this->filterProtectedLexemes($tokenVariants);
                $newVariants = array_values(array_diff($tokenVariants, $variants));

                if ($newVariants !== []) {
                    $variants = array_values(array_unique(array_merge($variants, $newVariants)));
                    $variants = array_slice($variants, 0, $maxVariantTerms);
                    $this->appendStreamingReply('اشتقاقات «'.$token.'» (expandWordVariants): '.$this->formatList($newVariants, 30)."\n");
                } else {
                    $this->appendStreamingReply('لا توجد اشتقاقات جديدة لـ «'.$token."».\n");
                }
            }

            if ($variantTruncated) {
                $this->appendStreamingReply("تم اختصار الاشتقاقات لتسريع الاستجابة، لأنها خطوة اختيارية.\n");
            }

            $terms = array_values(array_unique(array_merge($terms, $variants)));
            $terms = array_slice($terms, 0, 40);
        }

        $this->appendStreamingReply('مصطلحات البحث الموسعة (buildComprehensiveSearchPlan): '.$this->formatList($terms, 120)."\n");
        $this->appendStreamingReply('مخرجات الاشتقاقات (expandWordVariants/'.$this->variantMode.'): '.($this->enableComprehensiveSearch
            ? $this->formatList($variants, 120)
            : 'غير مفعلة')."\n\n");

        $this->setStreamingStatus('تنسيق النص');
        $withHarakatStyle = ArabicFilter::withHarakat($query);
        $withoutHarakat = ArabicFilter::withoutHarakat($query);
        $withoutDiacritics = ArabicFilter::withoutDiacritics($query);
        $normalizedHuroof = Arabic::normalizeHuroof($query);

        $this->appendStreamingReply('بنمط التشكيل (withHarakat): '.$withHarakatStyle."\n");
        $this->appendStreamingReply('بدون تشكيل (withoutHarakat): '.$withoutHarakat."\n");
        $this->appendStreamingReply('بدون الحركات (withoutDiacritics): '.$withoutDiacritics."\n");
        $this->appendStreamingReply('تطبيع الحروف (normalizeHuroof): '.$normalizedHuroof."\n\n");

        $this->setStreamingStatus('الأرقام والترقيم');
        $numeralsIndic = Arabic::convertNumeralsToIndian($query);
        $numeralsAscii = Arabic::convertNumeralsToArabic($query);
        $numeralsSearchMode = Arabic::normalizeNumeralsForSearch($query);
        $tightPunctuation = Arabic::toTightPunctuationStyle($query);
        $loosePunctuation = Arabic::toLoosePunctuationStyle($query);
        $withoutPunctuation = Arabic::removeAllPunctuationMarks($query);

        $this->appendStreamingReply('للأرقام الهندية (convertNumeralsToIndian): '.$numeralsIndic."\n");
        $this->appendStreamingReply('للأرقام الغربية (convertNumeralsToArabic): '.$numeralsAscii."\n");
        $this->appendStreamingReply('أرقام البحث المعتمدة (normalizeNumeralsForSearch): '.$numeralsSearchMode."\n");
        $this->appendStreamingReply('ترقيم ملتصق (toTightPunctuationStyle): '.$tightPunctuation."\n");
        $this->appendStreamingReply('ترقيم متباعد (toLoosePunctuationStyle): '.$loosePunctuation."\n");
        $this->appendStreamingReply('بدون ترقيم (removeAllPunctuationMarks): '.$withoutPunctuation."\n\n");

        $this->setStreamingStatus('اكتمل');

        return [
            'search' => $search,
            'stem' => $stem,
            'sanitized' => $sanitized,
            'commons_removed' => $commonsRemoved,
            'with_harakat_style' => $withHarakatStyle,
            'without_harakat' => $withoutHarakat,
            'without_diacritics' => $withoutDiacritics,
            'normalized_huroof' => $normalizedHuroof,
            'tokens' => $tokens,
            'terms' => $terms,
            'variants' => $variants,
            'keywords' => $keywordPayload,
            'numerals_indic' => $numeralsIndic,
            'numerals_ascii' => $numeralsAscii,
            'numerals_search_mode' => $numeralsSearchMode,
            'tight_punctuation' => $tightPunctuation,
            'loose_punctuation' => $loosePunctuation,
            'without_punctuation' => $withoutPunctuation,
        ];
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>  $keywords
     * @return array<int, string>
     */
    private function buildVariantCandidates(array $tokens, array $keywords): array
    {
        $prioritized = array_values(array_unique(array_merge($keywords, $tokens)));

        return array_values(array_filter($prioritized, function (string $word): bool {
            if ($this->isProtectedLexeme($word)) {
                return false;
            }

            return mb_strlen($word) >= 3;
        }));
    }

    /**
     * @param  array<int, string>  $terms
     * @return array<int, string>
     */
    private function filterProtectedLexemes(array $terms): array
    {
        return array_values(array_filter($terms, fn (string $term): bool => ! $this->isProtectedLexeme($term)));
    }

    private function isProtectedLexeme(string $term): bool
    {
        $normalized = ArabicFilter::forSearch($term);

        return in_array($normalized, self::PROTECTED_LEXEMES, true);
    }

    private function beginAssistantStreaming(): void
    {
        $this->streamingReply = '';
        $this->streamingStatus = 'جارٍ التحليل...';

        if (! $this->shouldEmitStreamDirectives()) {
            return;
        }

        $this->stream(to: 'assistant-stream', content: '', replace: true);
        $this->stream(to: 'assistant-status', content: $this->streamingStatus, replace: true);
    }

    private function finishAssistantStreaming(): void
    {
        $reply = trim($this->streamingReply);

        if ($reply !== '') {
            $this->pushChatMessage('assistant', $reply);
        }

        if (! $this->shouldEmitStreamDirectives()) {
            $this->streamingReply = '';
            $this->streamingStatus = 'جاهز';

            return;
        }

        $this->stream(to: 'assistant-stream', content: '', replace: true);
        $this->stream(to: 'assistant-status', content: 'جاهز', replace: true);

        $this->streamingReply = '';
        $this->streamingStatus = 'جاهز';
    }

    private function setStreamingStatus(string $status): void
    {
        $this->streamingStatus = $status;

        if (! $this->shouldEmitStreamDirectives()) {
            return;
        }

        $this->stream(to: 'assistant-status', content: $status, replace: true);
    }

    private function appendStreamingReply(string $chunk): void
    {
        if ($chunk === '') {
            return;
        }

        if (! $this->shouldEmitStreamDirectives()) {
            $this->streamingReply .= $chunk;

            return;
        }

        $parts = preg_split('/(\s+)/u', $chunk, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            $this->streamingReply .= $chunk;
            $this->stream(to: 'assistant-stream', content: $chunk);

            return;
        }

        foreach ($parts as $part) {
            $this->streamingReply .= $part;
            $this->stream(to: 'assistant-stream', content: $part);
            usleep(1_000);
        }
    }

    private function pushChatMessage(string $role, string $content): void
    {
        $this->chatMessages[] = [
            'role' => $role,
            'content' => $content,
        ];

        $this->chatMessages = array_slice($this->chatMessages, -20);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function stemTokensWithStreaming(array $tokens): array
    {
        if ($tokens === []) {
            $this->appendStreamingReply("لا توجد كلمات قابلة للاشتقاق بعد التنقية.\n");

            return [];
        }

        $stems = [];
        $tokenCount = count($tokens);
        $previewLimit = 20;

        foreach ($tokens as $index => $token) {
            $this->setStreamingStatus(sprintf('اشتقاق كلمات الأساس (%d/%d): %s', $index + 1, $tokenCount, $token));

            $stem = Arabic::stemWord($token);
            $stems[] = $stem;

            if ($index < $previewLimit) {
                $this->appendStreamingReply('اشتقاق «'.$token.'»: '.$stem."\n");

                continue;
            }

            if ($index === $previewLimit) {
                $this->appendStreamingReply("تم اختصار عرض تفاصيل الاشتقاق لتسريع البث.\n");
            }
        }

        return $stems;
    }

    /**
     * @param  array<int, string>  $items
     */
    private function formatList(array $items, int $limit): string
    {
        $items = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            $items,
        ), static fn (string $item): bool => $item !== ''));

        if ($items === []) {
            return '—';
        }

        $display = array_slice($items, 0, max(1, $limit));
        $suffix = count($items) > count($display) ? ' ...' : '';

        return implode('، ', $display).$suffix;
    }

    private function shouldEmitStreamDirectives(): bool
    {
        return ! app()->runningUnitTests();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAnalysis(): array
    {
        return [
            'search' => '',
            'stem' => '',
            'sanitized' => '',
            'commons_removed' => '',
            'with_harakat_style' => '',
            'without_harakat' => '',
            'without_diacritics' => '',
            'normalized_huroof' => '',
            'tokens' => [],
            'terms' => [],
            'variants' => [],
            'keywords' => [
                'tokens' => [],
                'stems' => [],
                'keywords' => [],
            ],
            'numerals_indic' => '',
            'numerals_ascii' => '',
            'numerals_search_mode' => '',
            'tight_punctuation' => '',
            'loose_punctuation' => '',
            'without_punctuation' => '',
        ];
    }
}
