<!DOCTYPE html>
<html lang="ar">

<head>
    <!-- Meta -->
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >
    <title>Arabicable Demo</title>

    <!-- Styles -->
    <link
        href="{{ asset('build/demo.css') }}"
        rel="stylesheet"
    >
    @livewireStyles
</head>

<body class="antialiased">
    <main>
        <livewire:arabicable::demo />
        {{-- @livewire('tailwind-merge::merger') --}}
    </main>

    <!-- Body Scripts -->
    <script
        src="{{ asset('vendor/arabicable/arabicable.js') }}"
        data-navigate-once
    ></script>
    <script
        src="{{ asset('build/demo.js') }}"
        data-navigate-once
    ></script>
    @livewireScripts

    <!-- Injections -->
    @stack('injections')
</body>

</html>
