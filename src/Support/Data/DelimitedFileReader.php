<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Data;

use Generator;
use SplFileObject;

class DelimitedFileReader
{
    /**
     * @return Generator<int, array<int, string>>
     */
    public static function readRows(string $path, string $delimiter = "\t", bool $skipHeader = true): Generator
    {
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $headerSkipped = false;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $first = trim((string) ($row[0] ?? ''));

            if ($first === '' || str_starts_with($first, '#')) {
                continue;
            }

            if ($skipHeader && ! $headerSkipped) {
                $headerSkipped = true;

                continue;
            }

            $headerSkipped = true;

            yield array_map(static fn (mixed $item): string => trim((string) $item), $row);
        }
    }
}
