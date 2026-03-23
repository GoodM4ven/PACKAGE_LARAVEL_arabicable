<div
    class="h-screen overflow-hidden bg-slate-950 px-3 py-3 text-slate-100 sm:px-6 sm:py-5"
    dir="rtl"
>
    <div class="mx-auto h-full max-w-7xl">
        <section
            class="relative flex h-full flex-col overflow-hidden rounded-3xl border border-cyan-400/20 bg-slate-900/85 shadow-2xl shadow-cyan-950/30 backdrop-blur"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(6,182,212,0.14),transparent_50%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.10),transparent_55%)]">
            </div>

            <header class="relative flex items-center justify-between border-b border-slate-700/70 px-5 py-4 sm:px-7">
                <div>
                    <h1 class="text-lg font-semibold text-white sm:text-xl">قارئ القرآن</h1>
                    <p class="mt-1 text-xs text-slate-300">عرض صفحات المصحف مع بحث ذكي واختيار الآية لعرض التفسير
                        والإعراب.</p>
                </div>

                <nav class="flex items-center gap-2 text-xs">
                    <a
                        class="rounded-lg border border-slate-600/70 bg-slate-800/70 px-3 py-1.5 text-slate-100 transition hover:border-cyan-400/60 hover:text-cyan-200"
                        href="{{ route('demo') }}"
                        wire:navigate
                    >
                        ورشة البحث
                    </a>
                    <a
                        class="rounded-lg border border-slate-600/70 bg-slate-800/70 px-3 py-1.5 text-slate-100 transition hover:border-cyan-400/60 hover:text-cyan-200"
                        href="{{ route('quran-reader') }}"
                        wire:navigate
                    >
                        قارئ القرآن
                    </a>
                </nav>
            </header>

            <div class="relative min-h-0 flex-1 p-3 sm:p-5">
                @if (!$ready)
                    <div
                        class="mx-auto max-w-3xl rounded-2xl border border-amber-400/40 bg-amber-500/10 p-5 text-sm text-amber-100">
                        بنية قارئ القرآن غير مكتملة. أعد تشغيل الترحيلات لتجهيز الجداول الجديدة الخاصة بالصفحات والبحث.
                    </div>
                @else
                    <div class="grid h-full min-h-0 gap-3 lg:grid-cols-[320px_minmax(0,1fr)]">
                        <aside
                            class="min-h-0 overflow-y-auto rounded-2xl border border-slate-700/70 bg-slate-900/75 p-3"
                        >
                            <h2 class="text-sm font-semibold text-slate-100">البحث والتنقّل</h2>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs text-slate-300">ابحث بصيغة الكتابة المعتادة</label>
                                    <input
                                        class="w-full rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition placeholder:text-slate-400 focus:border-cyan-400/70 focus:ring-2 focus:ring-cyan-400/20"
                                        type="text"
                                        wire:model.live.debounce.350ms="query"
                                        placeholder="مثال: الكتاب"
                                    >

                                    @if ($searchQuery !== '')
                                        <p class="mt-1 text-[11px] text-slate-400">صيغة البحث: <span
                                                class="text-cyan-300"
                                            >{{ $searchQuery }}</span></p>
                                    @endif
                                </div>

                                @if ($searchQuery !== '' && count($searchMatches) > 1)
                                    <div>
                                        <label class="mb-1 block text-xs text-slate-300">نتائج متعددة — اختر آية</label>
                                        <select
                                            class="w-full rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition focus:border-cyan-400/70 focus:ring-2 focus:ring-cyan-400/20"
                                            wire:model.live="searchSelectionAyahIndex"
                                        >
                                            <option value="0">اختر نتيجة...</option>
                                            @foreach ($searchMatches as $match)
                                                <option value="{{ $match['ayah_index'] }}">
                                                    {{ $match['surah_title'] }} • آية {{ $match['ayah_number'] }}
                                                    @if ($match['mushaf_page'] !== null)
                                                        • صفحة {{ $match['mushaf_page'] }}
                                                    @endif
                                                    @if (($match['search_snippet'] ?? '') !== '')
                                                        • {{ $match['search_snippet'] }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif ($searchQuery !== '' && count($searchMatches) === 1)
                                    <div
                                        class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100">
                                        تم العثور على نتيجة واحدة والانتقال إليها تلقائيًا.
                                    </div>
                                @elseif ($searchQuery !== '' && count($searchMatches) === 0)
                                    <div
                                        class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-100">
                                        لا توجد نتائج مطابقة حاليًا.
                                    </div>
                                @endif

                                <div class="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                                    <p class="text-xs font-semibold text-slate-200">الصفحة الحالية</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <button
                                            class="rounded-lg border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-200 transition hover:border-cyan-400/60"
                                            type="button"
                                            wire:click="nextPage"
                                        >
                                            التالي
                                        </button>
                                        <input
                                            class="w-20 rounded-lg border border-slate-600 bg-slate-800 px-2 py-1 text-center text-xs text-slate-100 outline-none focus:border-cyan-400/70"
                                            type="number"
                                            min="1"
                                            max="{{ max(1, $maxPage) }}"
                                            wire:model.live.debounce.350ms="pageNumber"
                                        >
                                        <button
                                            class="rounded-lg border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs text-slate-200 transition hover:border-cyan-400/60"
                                            type="button"
                                            wire:click="previousPage"
                                        >
                                            السابق
                                        </button>
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-400">صفحة {{ $pageNumber }} من
                                        {{ max(1, $maxPage) }}</p>
                                </div>

                                <div class="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                                    <p class="text-xs font-semibold text-slate-200">محتوى الشرح</p>
                                    <div class="mt-2 space-y-2 text-xs">
                                        <label class="inline-flex items-center gap-2 text-slate-300">
                                            <input
                                                class="h-4 w-4 rounded border-slate-500 bg-slate-800 text-cyan-400"
                                                type="checkbox"
                                                wire:model.live="showTafsir"
                                            >
                                            عرض التفسير
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-slate-300">
                                            <input
                                                class="h-4 w-4 rounded border-slate-500 bg-slate-800 text-cyan-400"
                                                type="checkbox"
                                                wire:model.live="showIrab"
                                            >
                                            عرض الإعراب
                                        </label>
                                    </div>

                                    <label class="mt-3 block text-xs text-slate-300">مصدر محدد (اختياري)</label>
                                    <select
                                        class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-2 py-1.5 text-xs text-slate-100 outline-none transition focus:border-cyan-400/70"
                                        wire:model.live="sourceKey"
                                    >
                                        <option value="">كل المصادر</option>
                                        @foreach ($sourceOptions as $source)
                                            <option value="{{ $source['source_key'] }}">
                                                {{ $source['source_label'] }}
                                                ({{ $source['content_kind'] === 'irab' ? 'إعراب' : 'تفسير' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button
                                    class="w-full rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-rose-400/60 hover:text-rose-200"
                                    type="button"
                                    wire:click="clearFilters"
                                >
                                    إعادة ضبط المرشّحات
                                </button>
                            </div>
                        </aside>

                        <section
                            class="min-h-0 overflow-y-auto rounded-2xl border border-slate-700/70 bg-slate-900/75 p-3 sm:p-4"
                        >
                            <div class="rounded-2xl border border-cyan-400/20 bg-slate-950/60 px-4 py-3">
                                <div class="mb-3 flex items-center justify-between text-xs text-slate-300">
                                    <span>صفحة المصحف {{ $pageNumber }}</span>
                                    <span>انقر على أي آية لإظهار الشروح</span>
                                </div>

                                @if ($qpcPageFontFamily !== null && $qpcPageFontUrl !== null)
                                    <style>
                                        @font-face {
                                            font-family: '{{ $qpcPageFontFamily }}';
                                            src: url('{{ $qpcPageFontUrl }}');
                                            font-display: block;
                                        }
                                    </style>
                                @endif

                                @if ($surahHeaderFontFamily !== null && $surahHeaderFontUrl !== null)
                                    <style>
                                        @font-face {
                                            font-family: '{{ $surahHeaderFontFamily }}';

                                            src: url('{{ $surahHeaderFontUrl }}') format('{{ $surahHeaderFontFormat ?? 'woff2' }}')@if ($surahHeaderFontDataUri !== null)
                                                ,
                                                url('{{ $surahHeaderFontDataUri }}') format('{{ $surahHeaderFontFormat ?? 'woff2' }}')
                                            @endif
                                            ;
                                            font-display: block;
                                        }
                                    </style>
                                @endif

                                <div
                                    class="{{ !$useCenteredAyahLayout ? 'mx-auto w-[32rem] max-w-full space-y-7' : 'mx-auto max-w-[920px] space-y-7' }}">
                                    @foreach ($mushafLines as $line)
                                        @php
                                            $isRectangularAyahLine =
                                                $line['line_type'] === 'ayah' && !$useCenteredAyahLayout;
                                            $lineFontStyle =
                                                $qpcPageFontFamily !== null
                                                    ? "font-family: '{$qpcPageFontFamily}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;"
                                                    : null;
                                            $metaLineStyle =
                                                $line['line_type'] === 'basmallah'
                                                    ? "font-family: 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;"
                                                    : null;
                                        @endphp
                                        <div
                                            class="{{ $isRectangularAyahLine ? 'text-right' : ($line['is_centered'] ? 'text-center' : '') }}"
                                            wire:key="mushaf-line-{{ $pageNumber }}-{{ $line['line_number'] }}-{{ $line['line_type'] }}"
                                        >
                                            @if ($line['line_type'] === 'ayah' && $line['words'] !== [])
                                                <div
                                                    class="{{ $isRectangularAyahLine ? 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect font-quran text-[1.95rem] leading-[1.54] text-slate-50 sm:text-[2.08rem]' : 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered font-quran text-[1.72rem] leading-[2.12] text-slate-50 sm:text-[2.02rem]' }}"
                                                    @if ($lineFontStyle !== null) style="{{ $lineFontStyle }}" @endif
                                                >
                                                    @foreach ($line['words'] as $word)
                                                        @php
                                                            $wordAyahIndex = (int) ($word['ayah_index'] ?? 0);
                                                        @endphp
                                                        <button
                                                            class="{{ $wordAyahIndex > 0 && $wordAyahIndex === $activeAyahIndex ? 'quran-segment-active bg-cyan-400/20 text-cyan-100' : 'text-slate-50 hover:bg-cyan-400/12' }} quran-segment quran-word-button rounded-sm px-0 align-baseline transition"
                                                            data-ayah-index="{{ $wordAyahIndex }}"
                                                            type="button"
                                                            wire:key="mushaf-word-{{ $pageNumber }}-{{ $line['line_number'] }}-{{ $word['word_index'] ?? $loop->index }}"
                                                            @if ($wordAyahIndex > 0) wire:click="selectAyah({{ $wordAyahIndex }})"
                                                            @else
                                                                disabled @endif
                                                        >
                                                            {{ $word['text'] }}
                                                        </button>
                                                        @if (($word['ends_ayah'] ?? false) && !($word['is_glyph'] ?? false))
                                                            <span
                                                                class="quran-ayah-marker mr-0.5 text-[0.92rem] text-cyan-200"
                                                            >۝{{ $word['ayah_number'] }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @elseif ($line['line_type'] === 'surah_name')
                                                <div
                                                    class="quran-surah-header-line text-center text-3xl text-emerald-100 sm:text-4xl"
                                                    @if ($surahHeaderFontFamily !== null) style="font-family: '{{ $surahHeaderFontFamily }}', 'SurahNameV4', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;" @endif
                                                >
                                                    {{ $line['text'] }}
                                                </div>
                                            @else
                                                <div
                                                    class="font-quran text-2xl leading-[2.1] text-emerald-100 sm:text-3xl"
                                                    @if ($metaLineStyle !== null) style="{{ $metaLineStyle }}" @endif
                                                >
                                                    {{ $line['text'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-700/70 bg-slate-800/80 p-4">
                                @if ($selectedVerse)
                                    <header class="mb-3 flex items-center justify-between text-xs text-slate-300">
                                        <span>{{ $selectedSurahTitle }} • آية
                                            {{ $selectedVerse->ayah_number }}</span>
                                        <span class="font-mono text-slate-400">#{{ $selectedVerse->ayah_index }}</span>
                                    </header>

                                    <p class="font-quran text-2xl leading-[2.2] text-slate-50 sm:text-3xl">
                                        {{ $selectedVerse->text_uthmani }}
                                    </p>

                                    <div
                                        class="mt-3 rounded-xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-[12px] text-cyan-200">
                                        <span class="text-slate-400">صيغة البحث المطابقة:</span>
                                        <span>{{ $selectedVerse->text_searchable_typed }}</span>
                                    </div>

                                    @if ($selectedExplanations !== [])
                                        <div class="mt-3 space-y-2">
                                            @foreach ($selectedExplanations as $item)
                                                <details
                                                    class="rounded-xl border border-slate-700/80 bg-slate-900/70 p-3"
                                                >
                                                    <summary
                                                        class="cursor-pointer text-xs font-semibold text-emerald-200"
                                                    >
                                                        {{ $item['content_kind'] === 'irab' ? 'إعراب' : 'تفسير' }}:
                                                        {{ $item['source_label'] }}
                                                    </summary>
                                                    <p
                                                        class="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate-100">
                                                        {{ $item['content_text'] }}
                                                    </p>
                                                </details>
                                            @endforeach
                                        </div>
                                    @else
                                        <div
                                            class="mt-3 rounded-xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs text-slate-300">
                                            لا توجد مادة تفسيرية/إعرابية متاحة لهذه الآية وفق المرشحات الحالية.
                                        </div>
                                    @endif
                                @else
                                    <div class="text-sm text-slate-300">اختر آية من الصفحة أو عبر البحث لعرض الشروح.
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
