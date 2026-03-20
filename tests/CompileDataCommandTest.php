<?php

declare(strict_types=1);

it('compiles core stop-words and paired vocalizations from raw data', function (): void {
    $rawPath = sys_get_temp_dir().'/arabicable-compile-'.bin2hex(random_bytes(8));

    $makeDir = static function (string $path): void {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    };

    $write = static function (string $path, string $content) use ($makeDir): void {
        $makeDir(dirname($path));
        file_put_contents($path, $content);
    };

    $write($rawPath.'/stop-words/stop-words-all-forms-01.tsv', implode("\n", [
        "word\tvocalized\ttype\tcategory\tlemma\tprocletic\tstem\tencletic\ttags",
        "من\tمِنْ\tحرف\tحرف جر\tمن\t\tمن\t\tstopword",
        '',
    ]));
    $write($rawPath.'/stop-words/stop-words-main-01.json', json_encode(['ان'], JSON_UNESCAPED_UNICODE));

    $write($rawPath.'/vocalizations/source-vocalized-words-01.txt', "الْفَائِزُونَ\n");
    $write($rawPath.'/vocalizations/source-unvocalized-words-02.txt', "الفائزون\n");

    $this->artisan('arabicable:compile-data', ['--raw-data-path' => $rawPath])->assertExitCode(0);

    $stopWords = (string) file_get_contents($rawPath.'/stop-words/compiled-stop-words-forms.tsv');
    $vocalizations = (string) file_get_contents($rawPath.'/vocalizations/compiled-vocalizations.tsv');
    $report = (string) file_get_contents($rawPath.'/index/compiled-source-report.tsv');

    expect($stopWords)->toContain('من')
        ->and($stopWords)->toContain('stopwords-kalimat-forms')
        ->and($vocalizations)->toContain("الْفَائِزُونَ\tvocalizations-paired-words")
        ->and($vocalizations)->toContain('vocalizations-paired-words')
        ->and($report)->toContain('stop-words/stop-words-all-forms-01.tsv');

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rawPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());

            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($rawPath);
});
