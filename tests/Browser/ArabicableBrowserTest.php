<?php

declare(strict_types=1);

it('exercises the workbench home page demo', function (): void {
    visit('/')
        ->assertNoJavaScriptErrors();
})->group('browser');
