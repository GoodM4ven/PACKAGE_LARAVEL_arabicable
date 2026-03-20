(function registerArabicable(globalScope) {
    const asciiDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    const arabicIndicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    const asciiToArabicIndic = Object.fromEntries(
        asciiDigits.map((digit, index) => [digit, arabicIndicDigits[index]]),
    );

    const arabicIndicToAscii = Object.fromEntries(
        arabicIndicDigits.map((digit, index) => [digit, asciiDigits[index]]),
    );

    const replaceDigits = (value, map) =>
        String(value ?? '')
            .split('')
            .map((character) => map[character] ?? character)
            .join('');

    const toArabicIndic = (value) => replaceDigits(value, asciiToArabicIndic);
    const toAscii = (value) => replaceDigits(value, arabicIndicToAscii);

    const normalizeForBackendSearch = (value, mode = 'arabic') => {
        const text = String(value ?? '');

        if (mode === 'both') {
            const ascii = toAscii(text);
            const arabicIndic = toArabicIndic(ascii);

            if (ascii === arabicIndic) {
                return ascii;
            }

            return `${ascii} ${arabicIndic}`.trim();
        }

        if (mode === 'indian') {
            return toArabicIndic(toAscii(text));
        }

        return toAscii(text);
    };

    const normalizeInputElement = (element, options = {}) => {
        if (!element || typeof element.value === 'undefined') {
            return;
        }

        const mode = options.mode ?? 'arabic';
        const target = options.target ?? null;
        const emitInput = options.emitInput ?? true;

        const normalized = normalizeForBackendSearch(element.value, mode);

        if (target && typeof target.value !== 'undefined') {
            target.value = normalized;

            if (emitInput) {
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }

            return;
        }

        element.value = normalized;

        if (emitInput) {
            element.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    const attachInputNormalizer = (selector, options = {}) => {
        const elements = Array.from(document.querySelectorAll(selector));

        elements.forEach((element) => {
            const targetSelector = options.targetSelector ?? null;
            const target = targetSelector ? document.querySelector(targetSelector) : null;

            element.addEventListener('input', () => {
                normalizeInputElement(element, {
                    mode: options.mode ?? 'arabic',
                    target,
                    emitInput: options.emitInput ?? false,
                });
            });
        });
    };

    const ArabicableNumbers = {
        toArabicIndic,
        toAscii,
        normalizeForBackendSearch,
        normalizeInputElement,
        attachInputNormalizer,
    };

    const createRunAnywhereApi = () => {
        let bridge = null;

        const hasBridge = () => bridge !== null;

        const setBridge = (nextBridge) => {
            if (!nextBridge || typeof nextBridge !== 'object') {
                throw new Error('ArabicableRunAnywhere bridge must be an object.');
            }

            if (
                typeof nextBridge.speechToText !== 'function' ||
                typeof nextBridge.textToSpeech !== 'function'
            ) {
                throw new Error(
                    'ArabicableRunAnywhere bridge must expose speechToText(...) and textToSpeech(...).',
                );
            }

            bridge = nextBridge;

            return bridge;
        };

        const clearBridge = () => {
            bridge = null;
        };

        const requireBridge = () => {
            if (!bridge) {
                throw new Error(
                    'ArabicableRunAnywhere bridge is not registered. Call ArabicableRunAnywhere.setBridge(...) first.',
                );
            }

            return bridge;
        };

        const callBridge = async (method, ...args) => {
            const activeBridge = requireBridge();
            const callable = activeBridge[method];

            if (typeof callable !== 'function') {
                throw new Error(`ArabicableRunAnywhere bridge missing method: ${method}`);
            }

            return callable(...args);
        };

        const extractTextValue = (value) => {
            if (typeof value === 'string') {
                return value;
            }

            if (value && typeof value === 'object') {
                if (typeof value.text === 'string') {
                    return value.text;
                }

                if (typeof value.transcript === 'string') {
                    return value.transcript;
                }
            }

            return '';
        };

        const decodeAudioBlob = async (blob, targetSampleRate = 16000) => {
            if (!(blob instanceof Blob)) {
                throw new Error('decodeAudioBlob expects a Blob input.');
            }

            const AudioContextCtor = globalScope.AudioContext || globalScope.webkitAudioContext;

            if (!AudioContextCtor) {
                throw new Error('Web Audio API is not available in this browser.');
            }

            const audioContext = new AudioContextCtor();

            try {
                const sourceBuffer = await blob.arrayBuffer();
                const decoded = await audioContext.decodeAudioData(sourceBuffer.slice(0));

                const duration = decoded.duration;
                const frameCount = Math.max(1, Math.floor(duration * targetSampleRate));
                const offlineContext = new OfflineAudioContext(1, frameCount, targetSampleRate);

                const source = offlineContext.createBufferSource();
                source.buffer = decoded;
                source.connect(offlineContext.destination);
                source.start(0);

                const rendered = await offlineContext.startRendering();
                const samples = rendered.getChannelData(0);

                return {
                    audio: new Float32Array(samples),
                    sampleRate: targetSampleRate,
                };
            } finally {
                if (typeof audioContext.close === 'function') {
                    await audioContext.close();
                }
            }
        };

        const playFloat32Audio = async (audio, sampleRate = 24000) => {
            if (!(audio instanceof Float32Array)) {
                throw new Error('playFloat32Audio expects Float32Array input.');
            }

            const AudioContextCtor = globalScope.AudioContext || globalScope.webkitAudioContext;

            if (!AudioContextCtor) {
                throw new Error('Web Audio API is not available in this browser.');
            }

            const audioContext = new AudioContextCtor();
            const buffer = audioContext.createBuffer(1, audio.length, sampleRate);
            buffer.copyToChannel(audio, 0);

            const source = audioContext.createBufferSource();
            source.buffer = buffer;
            source.connect(audioContext.destination);
            source.start(0);

            return new Promise((resolve) => {
                source.onended = () => {
                    if (typeof audioContext.close === 'function') {
                        audioContext.close().finally(resolve);

                        return;
                    }

                    resolve();
                };
            });
        };

        const speechToText = async (audioInput, options = {}) => {
            const result = await callBridge('speechToText', audioInput, options);
            const text = extractTextValue(result);

            return {
                raw: result,
                text,
            };
        };

        const textToSpeech = async (textInput, options = {}) => {
            if (typeof textInput !== 'string' || textInput.trim() === '') {
                throw new Error('textToSpeech expects a non-empty text input.');
            }

            const result = await callBridge('textToSpeech', textInput, options);

            if (result instanceof Float32Array) {
                return {
                    raw: result,
                    audio: result,
                    sampleRate: options.sampleRate ?? 24000,
                };
            }

            if (result instanceof Blob) {
                return {
                    raw: result,
                    blob: result,
                };
            }

            if (result && typeof result === 'object') {
                return {
                    raw: result,
                    audio: result.audio instanceof Float32Array ? result.audio : undefined,
                    blob: result.blob instanceof Blob ? result.blob : undefined,
                    url: typeof result.url === 'string' ? result.url : undefined,
                    sampleRate:
                        typeof result.sampleRate === 'number'
                            ? result.sampleRate
                            : (options.sampleRate ?? 24000),
                };
            }

            return {
                raw: result,
            };
        };

        const voiceToText = async (audioInput, options = {}) => speechToText(audioInput, options);
        const textToVoice = async (textInput, options = {}) => textToSpeech(textInput, options);

        const voiceToVoice = async (audioInput, options = {}) => {
            const stt = await speechToText(audioInput, options);

            if (stt.text.trim() === '') {
                return {
                    mode: 'speech-to-text-to-speech',
                    transcript: '',
                    warning: 'No transcript text was produced by speechToText.',
                };
            }

            const tts = await textToSpeech(stt.text, options);

            return {
                mode: 'speech-to-text-to-speech',
                transcript: stt.text,
                ...tts,
            };
        };

        const transformArabic = async (
            { text = '', audio = null, target = 'text' } = {},
            options = {},
        ) => {
            if (target === 'text') {
                if (typeof text === 'string' && text.trim() !== '') {
                    return {
                        mode: 'text',
                        text,
                    };
                }

                if (audio) {
                    const stt = await speechToText(audio, options);

                    return {
                        mode: 'speech-to-text',
                        text: stt.text,
                        raw: stt.raw,
                    };
                }

                throw new Error('transformArabic(target=text) expects text or audio input.');
            }

            if (target === 'voice') {
                if (typeof text === 'string' && text.trim() !== '') {
                    const tts = await textToSpeech(text, options);

                    return {
                        mode: 'text-to-speech',
                        ...tts,
                    };
                }

                if (audio) {
                    return voiceToVoice(audio, options);
                }

                throw new Error('transformArabic(target=voice) expects text or audio input.');
            }

            throw new Error(`Unsupported transform target: ${target}`);
        };

        const playAudio = async (result) => {
            if (result?.audio instanceof Float32Array) {
                await playFloat32Audio(result.audio, result.sampleRate ?? 24000);

                return;
            }

            if (result?.blob instanceof Blob) {
                const url = URL.createObjectURL(result.blob);
                const audio = new Audio(url);
                await audio.play();

                return;
            }

            if (typeof result?.url === 'string') {
                const audio = new Audio(result.url);
                await audio.play();

                return;
            }

            throw new Error(
                'playAudio requires one of: audio(Float32Array), blob(Blob), or url(string).',
            );
        };

        return {
            hasBridge,
            setBridge,
            clearBridge,
            decodeAudioBlob,
            playFloat32Audio,
            speechToText,
            textToSpeech,
            voiceToText,
            textToVoice,
            voiceToVoice,
            transformArabic,
            playAudio,
        };
    };

    const ArabicableRunAnywhere = createRunAnywhereApi();

    globalScope.ArabicableNumbers = ArabicableNumbers;
    globalScope.ArabicableRunAnywhere = ArabicableRunAnywhere;

    if (globalScope.Alpine && typeof globalScope.Alpine.magic === 'function') {
        globalScope.Alpine.magic('arabicableNumbers', () => ArabicableNumbers);
        globalScope.Alpine.magic('arabicableRunAnywhere', () => ArabicableRunAnywhere);
    }
})(window);
