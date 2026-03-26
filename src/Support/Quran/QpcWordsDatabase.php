<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Quran;

final class QpcWordsDatabase
{
    /**
     * @param  array<int, string>  $paths
     */
    public static function resolveFirstValidPath(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (self::hasWordsTable($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function hasWordsTable(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        try {
            $database = new \SQLite3($path, SQLITE3_OPEN_READONLY);
            $statement = $database->prepare(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'words' LIMIT 1",
            );

            if (! $statement instanceof \SQLite3Stmt) {
                $database->close();

                return false;
            }

            $result = $statement->execute();

            if (! $result instanceof \SQLite3Result) {
                $statement->close();
                $database->close();

                return false;
            }

            $row = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            $statement->close();
            $database->close();

            return is_array($row) && ($row['name'] ?? null) === 'words';
        } catch (\Throwable) {
            return false;
        }
    }
}
