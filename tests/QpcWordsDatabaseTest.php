<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Support\Quran\QpcWordsDatabase;

it('detects whether sqlite qpc database includes words table', function (): void {
    $missingTableDatabasePath = tempnam(sys_get_temp_dir(), 'qpc-missing-');
    $validDatabasePath = tempnam(sys_get_temp_dir(), 'qpc-valid-');

    expect($missingTableDatabasePath)->toBeString()->not->toBe('')
        ->and($validDatabasePath)->toBeString()->not->toBe('');

    $missingDatabase = new SQLite3($missingTableDatabasePath);
    $missingDatabase->exec('CREATE TABLE not_words (id INTEGER PRIMARY KEY)');
    $missingDatabase->close();

    $validDatabase = new SQLite3($validDatabasePath);
    $validDatabase->exec('CREATE TABLE words (id INTEGER PRIMARY KEY, surah INTEGER, ayah INTEGER, text TEXT)');
    $validDatabase->close();

    expect(QpcWordsDatabase::hasWordsTable($missingTableDatabasePath))->toBeFalse()
        ->and(QpcWordsDatabase::hasWordsTable($validDatabasePath))->toBeTrue();

    @unlink($missingTableDatabasePath);
    @unlink($validDatabasePath);
});

it('resolves the first valid qpc database path', function (): void {
    $missingTableDatabasePath = tempnam(sys_get_temp_dir(), 'qpc-missing-');
    $validDatabasePath = tempnam(sys_get_temp_dir(), 'qpc-valid-');

    expect($missingTableDatabasePath)->toBeString()->not->toBe('')
        ->and($validDatabasePath)->toBeString()->not->toBe('');

    $missingDatabase = new SQLite3($missingTableDatabasePath);
    $missingDatabase->exec('CREATE TABLE placeholders (id INTEGER PRIMARY KEY)');
    $missingDatabase->close();

    $validDatabase = new SQLite3($validDatabasePath);
    $validDatabase->exec('CREATE TABLE words (id INTEGER PRIMARY KEY, surah INTEGER, ayah INTEGER, text TEXT)');
    $validDatabase->close();

    $resolvedPath = QpcWordsDatabase::resolveFirstValidPath([
        '/does/not/exist/qpc-v2.db',
        $missingTableDatabasePath,
        $validDatabasePath,
    ]);

    expect($resolvedPath)->toBe($validDatabasePath);

    @unlink($missingTableDatabasePath);
    @unlink($validDatabasePath);
});
