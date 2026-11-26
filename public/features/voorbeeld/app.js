// Enkel JavaScript voor de voorbeeld-feature: een teller onder het tekstvak.
(function () {
    'use strict';

    var textarea = document.getElementById('voorbeeld-body');
    var counter = document.getElementById('voorbeeld-count');

    if (!textarea || !counter) {
        return;
    }

    textarea.addEventListener('input', function () {
        counter.textContent = textarea.value.length;
    });
})();
