const registerAlpineMagic = () => {
    if (window.__arabicableAlpineMagicRegistered) {
        return;
    }

    const alpine = window.Alpine;

    if (!alpine || typeof alpine.magic !== 'function') {
        return;
    }

    if (window.ArabicableNumbers) {
        alpine.magic('arabicableNumbers', () => window.ArabicableNumbers);
    }

    if (window.ArabicableRunAnywhere) {
        alpine.magic('arabicableRunAnywhere', () => window.ArabicableRunAnywhere);
    }

    window.__arabicableAlpineMagicRegistered = true;
};

document.addEventListener('alpine:init', registerAlpineMagic);
document.addEventListener('livewire:init', registerAlpineMagic);
registerAlpineMagic();

document.addEventListener('DOMContentLoaded', () => {
    if (document.documentElement.classList.contains('js-ready')) {
        return;
    }

    document.documentElement.classList.add('js-ready');
});

const toggleAyahHover = (ayahIndex, isHovered) => {
    if (!ayahIndex) {
        return;
    }

    const escapedAyahIndex =
        window.CSS && typeof window.CSS.escape === 'function'
            ? window.CSS.escape(String(ayahIndex))
            : String(ayahIndex).replace(/"/g, '\\"');

    const selector = `.quran-segment[data-ayah-index="${escapedAyahIndex}"]`;

    document.querySelectorAll(selector).forEach((element) => {
        if (element.classList.contains('quran-segment-active')) {
            return;
        }

        element.classList.toggle('quran-segment-hover', isHovered);
    });
};

document.addEventListener('mouseover', (event) => {
    const segment = event.target instanceof Element ? event.target.closest('.quran-segment') : null;

    if (!(segment instanceof HTMLElement)) {
        return;
    }

    toggleAyahHover(segment.dataset.ayahIndex ?? '', true);
});

document.addEventListener('mouseout', (event) => {
    const segment = event.target instanceof Element ? event.target.closest('.quran-segment') : null;

    if (!(segment instanceof HTMLElement)) {
        return;
    }

    const relatedSegment =
        event.relatedTarget instanceof Element
            ? event.relatedTarget.closest('.quran-segment')
            : null;

    if (
        relatedSegment instanceof HTMLElement &&
        relatedSegment.dataset.ayahIndex === segment.dataset.ayahIndex
    ) {
        return;
    }

    toggleAyahHover(segment.dataset.ayahIndex ?? '', false);
});
