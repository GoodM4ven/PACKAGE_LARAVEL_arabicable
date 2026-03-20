<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Data;

use Illuminate\Support\Facades\DB;

class ChunkedUpsert
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int, string>  $update
     */
    public static function toTable(
        string $table,
        array $rows,
        array|string $uniqueBy,
        array $update,
        int $chunkSize = 1000,
    ): void {
        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $update);
        }
    }
}
