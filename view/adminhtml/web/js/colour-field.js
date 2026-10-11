define([], function () {
    'use strict';

    /** Keeps the colour picker and the text field of a Mega Menu colour setting in step. */
    return function (config, wrapper) {
        var text = wrapper.querySelector('.commercegears-mm-colour-text');
        var picker = wrapper.querySelector('.commercegears-mm-colour-picker');

        if (!text || !picker) {
            return;
        }
        picker.addEventListener('input', function () {
            text.value = picker.value;
            text.dispatchEvent(new Event('change', {bubbles: true}));
        });
        text.addEventListener('input', function () {
            if (/^#[0-9a-f]{6}$/i.test(text.value.trim())) {
                picker.value = text.value.trim().toLowerCase();
            }
        });
        // "Use system value" toggles the text field; follow it.
        new MutationObserver(function () {
            picker.disabled = text.disabled;
        }).observe(text, {attributes: true, attributeFilter: ['disabled']});
    };
});
