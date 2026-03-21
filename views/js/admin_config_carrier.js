document.addEventListener('DOMContentLoaded', function () {
    function syncVindiCarrierPresetVisibility() {
        const selected = document.querySelector('[name="agyapay_vindi_carrier_mode"]:checked');
        const presetGroups = document.querySelectorAll('.form-group-vindi-carrier-preset');

        if (!presetGroups.length) {
            return;
        }

        const show = selected && String(selected.value) === '3';
        presetGroups.forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
    }

    document.querySelectorAll('[name="agyapay_vindi_carrier_mode"]').forEach(function (radio) {
        radio.addEventListener('change', syncVindiCarrierPresetVisibility);
    });

    syncVindiCarrierPresetVisibility();
});
