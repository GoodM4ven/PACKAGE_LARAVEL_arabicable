<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta
        content="ie=edge"
        http-equiv="X-UA-Compatible"
    >
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >
    <title>Arabicable Quran Reader Demo</title>

    <link
        href="{{ asset('build/demo.css') }}"
        rel="stylesheet"
    >
    @livewireStyles
</head>

<body class="antialiased">
    <main>
        <livewire:arabicable::quran-reader />
    </main>

    <script
        src="{{ asset('vendor/arabicable/arabicable.js') }}"
        data-navigate-once
    ></script>
    <script
        src="{{ asset('build/demo.js') }}"
        data-navigate-once
    ></script>
    @livewireScripts

    @stack('injections')
</body>

</html>
