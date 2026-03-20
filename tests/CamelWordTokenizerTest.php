<?php

declare(strict_types=1);

it('tokenizes by whitespace and separates punctuation', function (): void {
    $tokens = camel_simple_word_tokenize('Hello,    world!!!');

    expect($tokens)->toBe(['Hello', ',', 'world', '!', '!', '!']);
});

it('tokenizes arabic text and keeps words with separated punctuation', function (): void {
    $tokens = camel_simple_word_tokenize('هَلْ ذَهَبْتَ إِلَى المَكْتَبَةِ؟');

    expect($tokens)->toBe(['هَلْ', 'ذَهَبْتَ', 'إِلَى', 'المَكْتَبَةِ', '؟']);
});

it('splits digit runs when split_digits is enabled', function (): void {
    $tokens = camel_simple_word_tokenize('world123!!!', true);

    expect($tokens)->toBe(['world', '123', '!', '!', '!']);
});

it('keeps digit runs in the same token when split_digits is disabled', function (): void {
    $tokens = camel_simple_word_tokenize('world123!!!', false);

    expect($tokens)->toBe(['world123', '!', '!', '!']);
});

it('handles emoji as individual symbol tokens', function (): void {
    $tokens = camel_simple_word_tokenize('hello🙂world!');

    expect($tokens)->toBe(['hello', '🙂', 'world', '!']);
});
