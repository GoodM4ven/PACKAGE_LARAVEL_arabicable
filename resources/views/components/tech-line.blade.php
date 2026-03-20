@props(['caption'])

<div class="relative">
    <!-- Left Half -->
    <div class="absolute left-0 flex h-full w-1/2 flex-col justify-center">
        <!-- Dashed Faded Line -->
        <div class="absolute -z-10 w-full border-2 border-dashed border-gray-400"></div>
        <div class="bg-linear-to-r absolute z-10 h-5 w-full from-transparent via-white to-white"></div>

        <!-- Tag -->
        <div class="relative z-20 ms-10">
            <p class="inline-flex w-fit border bg-white px-3 py-2 text-xl italic">{{ $caption }}</p>
        </div>
    </div>

    <div class="relative z-30">
        {{ $slot }}
    </div>
</div>
