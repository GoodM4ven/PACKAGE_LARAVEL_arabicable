<div
    class="h-screen overflow-hidden bg-slate-950 px-3 py-3 text-slate-100 sm:px-6 sm:py-5"
    dir="rtl"
>
    <div class="mx-auto h-full max-w-7xl">
        <section
            class="relative flex h-full flex-col overflow-hidden rounded-3xl border border-emerald-400/20 bg-slate-900/80 shadow-2xl shadow-emerald-900/30 backdrop-blur"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(20,184,166,0.16),transparent_50%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.12),transparent_55%)]">
            </div>

            <div class="relative flex items-center justify-between border-b border-slate-700/70 px-5 py-4 sm:px-7">
                <div>
                    <h1 class="text-lg font-semibold text-white sm:text-xl">المساعد العربي الذكي للبحث</h1>
                    <p class="mt-1 text-xs text-slate-300">معالجة عربية فورية بأسلوب محادثة مع بث تدريجي للنتائج.</p>
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
            </div>

            <div
                class="relative flex min-h-0 flex-1 flex-col"
                x-data="{
                    exampleText: @js($exampleQuery),
                    lastStreamMutationAt: Date.now(),
                    typingStep: 1,
                    showTyping: false,
                    scrollToBottom() {
                        if (!this.$refs.chatFeed) {
                            return;
                        }
                        this.$refs.chatFeed.scrollTop = this.$refs.chatFeed.scrollHeight;
                    },
                    pushExampleAndRun() {
                        if (this.$refs.queryInput) {
                            this.$refs.queryInput.value = this.exampleText;
                            this.$refs.queryInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                
                        this.lastStreamMutationAt = Date.now();
                        this.showTyping = false;
                
                        $wire.addExampleAndRun();
                    },
                    tickTyping() {
                        const processing = this.$el.classList.contains('is-processing');
                        const idle = Date.now() - this.lastStreamMutationAt > 1200;
                
                        this.showTyping = processing && idle;
                
                        if (this.showTyping) {
                            this.typingStep = (this.typingStep % 3) + 1;
                
                            return;
                        }
                
                        this.typingStep = 1;
                    },
                }"
                x-init="$nextTick(() => scrollToBottom());
                const observer = new MutationObserver(() => scrollToBottom());
                observer.observe($refs.chatFeed, { childList: true, subtree: true, characterData: true });
                const streamObserver = new MutationObserver(() => {
                    lastStreamMutationAt = Date.now();
                    showTyping = false;
                });
                if ($refs.streamTarget) {
                    streamObserver.observe($refs.streamTarget, { childList: true, subtree: true, characterData: true });
                }
                setInterval(() => tickTyping(), 160);"
                wire:loading.class="is-processing"
                wire:target="runProcessing,addExampleAndRun"
            >
                <section class="min-h-0 flex-1 overflow-hidden px-2 pb-2 pt-3 sm:px-6 sm:pb-5">
                    <div class="flex h-full min-h-0 flex-col gap-3 lg:flex-row">
                        <aside
                            class="w-full shrink-0 overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900/80 lg:w-80"
                        >
                            <div class="border-b border-slate-700/60 px-4 py-3">
                                <h2 class="text-sm font-semibold text-slate-100">محول التاريخ</h2>
                                <p class="mt-1 text-[11px] text-slate-300">لوحة عملية للتحويل بين الميلادي والهجري</p>
                            </div>

                            <div class="space-y-3 p-3">
                                <div class="rounded-xl border border-slate-700/70 bg-slate-800/70 p-3">
                                    <p class="text-[11px] font-semibold text-cyan-300">
                                        تحويل ميلادي ← هجري
                                        <span
                                            class="ms-1 font-mono text-[10px] text-slate-400">Arabic::gregorianToHijri()</span>
                                    </p>
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-cyan-400/70"
                                            type="number"
                                            wire:model.lazy="gregorianDay"
                                            min="1"
                                            max="31"
                                            placeholder="يوم"
                                        >
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-cyan-400/70"
                                            type="number"
                                            wire:model.lazy="gregorianMonth"
                                            min="1"
                                            max="12"
                                            placeholder="شهر"
                                        >
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-cyan-400/70"
                                            type="number"
                                            wire:model.lazy="gregorianYear"
                                            min="1"
                                            max="9999"
                                            placeholder="سنة"
                                        >
                                    </div>
                                    <button
                                        class="mt-2 w-full rounded-lg border border-cyan-400/40 bg-cyan-500/10 px-2 py-1.5 text-xs font-semibold text-cyan-200 transition hover:bg-cyan-500/20"
                                        type="button"
                                        wire:click="convertGregorianToHijri"
                                    >
                                        حوّل إلى هجري
                                    </button>
                                </div>

                                <div class="rounded-xl border border-slate-700/70 bg-slate-800/70 p-3">
                                    <p class="text-[11px] font-semibold text-emerald-300">
                                        تحويل هجري ← ميلادي
                                        <span
                                            class="ms-1 font-mono text-[10px] text-slate-400">Arabic::hijriToGregorian()</span>
                                    </p>
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-emerald-400/70"
                                            type="number"
                                            wire:model.lazy="hijriDay"
                                            min="1"
                                            max="30"
                                            placeholder="يوم"
                                        >
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-emerald-400/70"
                                            type="number"
                                            wire:model.lazy="hijriMonth"
                                            min="1"
                                            max="12"
                                            placeholder="شهر"
                                        >
                                        <input
                                            class="rounded-lg border border-slate-600 bg-slate-900 px-2 py-1.5 text-center text-xs text-slate-100 outline-none focus:border-emerald-400/70"
                                            type="number"
                                            wire:model.lazy="hijriYear"
                                            min="1"
                                            max="20000"
                                            placeholder="سنة"
                                        >
                                    </div>
                                    <button
                                        class="mt-2 w-full rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-2 py-1.5 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/20"
                                        type="button"
                                        wire:click="convertHijriToGregorian"
                                    >
                                        حوّل إلى ميلادي
                                    </button>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-[11px] text-slate-300">
                                    <p>الميلادي الحالي: <span
                                            class="font-mono text-slate-100">{{ $gregorianYear }}-{{ str_pad((string) $gregorianMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $gregorianDay, 2, '0', STR_PAD_LEFT) }}</span>
                                    </p>
                                    <p class="mt-1">الهجري الحالي: <span
                                            class="font-mono text-slate-100">{{ $hijriYear }}-{{ str_pad((string) $hijriMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $hijriDay, 2, '0', STR_PAD_LEFT) }}</span>
                                    </p>
                                    <p class="mt-2 text-cyan-300">{{ $dateConversionStatus }}</p>
                                    @error('gregorianYear')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                    @error('gregorianMonth')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                    @error('gregorianDay')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                    @error('hijriYear')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                    @error('hijriMonth')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                    @error('hijriDay')
                                        <p class="mt-1 text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </aside>

                        <div
                            class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900/70">
                            <div class="flex items-center justify-between border-b border-slate-700/60 px-4 py-3">
                                <h2 class="text-sm font-semibold text-slate-100">المحادثة</h2>
                                <span
                                    class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-[11px] text-slate-300"
                                >
                                    <span wire:stream.replace="assistant-status">{{ $streamingStatus }}</span>
                                </span>
                            </div>

                            <div
                                class="min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-4"
                                x-ref="chatFeed"
                            >
                                <div class="space-y-4">
                                    @forelse ($chatMessages as $message)
                                        <article
                                            class="{{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }} flex"
                                        >
                                            <div
                                                class="{{ $message['role'] === 'user' ? 'border border-emerald-300/30 bg-emerald-500/15 text-emerald-50' : 'border border-slate-700 bg-slate-800/85 text-slate-100' }} max-w-4xl rounded-2xl px-4 py-3 text-sm leading-7">
                                                <p
                                                    class="{{ $message['role'] === 'user' ? 'text-emerald-200' : 'text-cyan-300' }} mb-1 text-[11px] font-semibold">
                                                    {{ $message['role'] === 'user' ? 'مدخل البحث' : 'نتيجة المعالجة' }}
                                                </p>
                                                <p class="whitespace-pre-wrap">{{ $message['content'] }}</p>
                                            </div>
                                        </article>
                                    @empty
                                        <div
                                            class="mx-auto max-w-3xl rounded-2xl border border-slate-700 bg-slate-800/80 px-4 py-3 text-center text-sm text-slate-300">
                                            ابدأ بإضافة مثال أو اكتب النص مباشرة، ثم اضغط زر التشغيل لعرض النتائج
                                            تدريجيًا داخل المحادثة.
                                        </div>
                                    @endforelse

                                    <article class="flex justify-start">
                                        <div
                                            class="w-full max-w-4xl rounded-2xl border border-cyan-600/40 bg-slate-800/90 px-4 py-3 text-sm">
                                            <p class="mb-2 text-[11px] font-semibold text-cyan-300">البث المباشر</p>
                                            <p
                                                class="whitespace-pre-wrap leading-7 text-slate-100"
                                                x-ref="streamTarget"
                                                wire:stream="assistant-stream"
                                            >{{ $streamingReply }}</p>
                                        </div>
                                    </article>

                                    <article
                                        class="flex justify-start"
                                        x-cloak
                                        x-show="showTyping"
                                    >
                                        <div
                                            class="w-fit rounded-full border border-slate-600/70 bg-slate-800/95 px-3 py-1.5 text-xs text-slate-300">
                                            جارٍ المعالجة
                                            <span
                                                class="ms-1 inline-block min-w-[1.25rem] text-right font-mono text-cyan-300"
                                                dir="rtl"
                                                x-text="'…'.repeat(typingStep)"
                                            ></span>
                                        </div>
                                    </article>
                                </div>
                            </div>

                            <div
                                class="shrink-0 border-t border-slate-700/70 bg-slate-900/90 px-3 py-3 backdrop-blur sm:px-5">
                                <div class="mx-auto w-full max-w-4xl space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-xl border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/20"
                                            type="button"
                                            x-on:click="pushExampleAndRun()"
                                            wire:loading.attr="disabled"
                                            wire:target="runProcessing,clearQuery,addExampleAndRun"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M9.25 3a.75.75 0 0 1 .75.75V9.25H15.5a.75.75 0 0 1 0 1.5H10v5.5a.75.75 0 0 1-1.5 0v-5.5H3a.75.75 0 0 1 0-1.5h5.5V3.75A.75.75 0 0 1 9.25 3Z"
                                                />
                                            </svg>
                                            إضافة مثال
                                        </button>

                                        <div class="relative min-w-[280px] flex-1">
                                            <input
                                                class="w-full rounded-2xl border border-slate-600/70 bg-slate-800/80 py-3 pe-4 ps-11 text-sm text-slate-100 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-400/70 focus:ring-2 focus:ring-cyan-400/20"
                                                id="arabicable-query-input"
                                                type="text"
                                                x-ref="queryInput"
                                                wire:model.live.debounce.150ms="query"
                                                wire:keydown.enter.prevent="runProcessing"
                                                wire:loading.attr="disabled"
                                                wire:loading.attr="readonly"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                                placeholder="اكتب نصك هنا..."
                                            >
                                            <button
                                                class="absolute start-2 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-300 transition hover:border-rose-400 hover:text-rose-300"
                                                type="button"
                                                title="مسح النص"
                                                aria-label="مسح النص"
                                                wire:click="clearQuery"
                                                wire:loading.attr="disabled"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                            >
                                                <svg
                                                    class="h-3.5 w-3.5"
                                                    aria-hidden="true"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M6.28 5.22a.75.75 0 0 1 1.06 0L10 7.94l2.66-2.72a.75.75 0 0 1 1.08 1.04L11.06 9l2.68 2.74a.75.75 0 1 1-1.08 1.04L10 10.06l-2.66 2.72a.75.75 0 1 1-1.08-1.04L8.94 9 6.28 6.26a.75.75 0 0 1 0-1.04Z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <button
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-cyan-400/40 bg-cyan-500/15 text-cyan-200 transition hover:bg-cyan-500/25"
                                            type="button"
                                            title="تشغيل التحليل"
                                            aria-label="تشغيل التحليل"
                                            wire:click="runProcessing"
                                            wire:loading.attr="disabled"
                                            wire:target="runProcessing,clearQuery,addExampleAndRun"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                aria-hidden="true"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M4.5 3.75A1.75 1.75 0 0 1 7.2 2.3l8.4 6.25a1.75 1.75 0 0 1 0 2.8l-8.4 6.25A1.75 1.75 0 0 1 4.5 16.15V3.75Z"
                                                />
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        class="mx-auto flex w-full flex-wrap items-center justify-center gap-2 rounded-xl border border-slate-700/70 bg-slate-800/60 px-2 py-2">
                                        <label
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-900/70 px-2 py-1 text-[11px] text-slate-200"
                                            title="تنظيف النص من الرموز غير المفيدة. الواجهة: stripWeirdCharacters"
                                        >
                                            <input
                                                class="h-3.5 w-3.5 rounded border-slate-500 bg-slate-800 text-emerald-400 focus:ring-emerald-400"
                                                type="checkbox"
                                                wire:model.live="enableSanitization"
                                                wire:loading.attr="disabled"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                            >
                                            تنقية
                                        </label>

                                        <label
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-900/70 px-2 py-1 text-[11px] text-slate-200"
                                            title="حذف الكلمات الشائعة. الواجهة: removeCommons"
                                        >
                                            <input
                                                class="h-3.5 w-3.5 rounded border-slate-500 bg-slate-800 text-emerald-400 focus:ring-emerald-400"
                                                type="checkbox"
                                                wire:model.live="enableCommonsRemoval"
                                                wire:loading.attr="disabled"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                            >
                                            حذف الشائع
                                        </label>

                                        <label
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-900/70 px-2 py-1 text-[11px] text-slate-200"
                                            title="توسيع خطة البحث بمصطلحات إضافية مرتبطة بالكلمات. الواجهة: buildComprehensiveSearchPlan / expandWordVariants"
                                        >
                                            <input
                                                class="h-3.5 w-3.5 rounded border-slate-500 bg-slate-800 text-emerald-400 focus:ring-emerald-400"
                                                type="checkbox"
                                                wire:model.live="enableComprehensiveSearch"
                                                wire:loading.attr="disabled"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                            >
                                            موسّع
                                        </label>

                                        <label
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-900/70 px-2 py-1 text-[11px] text-slate-200"
                                            title="نوع الاشتقاقات. الواجهة: expandWordVariants(..., mode)"
                                        >
                                            <span>الاشتقاقات</span>
                                            <select
                                                class="rounded-md border border-slate-600 bg-slate-800 px-2 py-1 text-[11px] text-slate-100 outline-none transition focus:border-cyan-400/70 focus:ring-2 focus:ring-cyan-400/20"
                                                wire:model.live="variantMode"
                                                wire:loading.attr="disabled"
                                                wire:target="runProcessing,clearQuery,addExampleAndRun"
                                            >
                                                <option value="all">الكل</option>
                                                <option value="roots">جذور</option>
                                                <option value="stems">اشتقاقات</option>
                                                <option value="original_words">ألفاظ</option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</div>
