// JavaScript voor de feature van wesley1 (feestelijke menukaart). Geen inline JS in de pagina.
(function () {
    'use strict';

    // Bevestiging vragen voor een item van de kaart verwijderd wordt.
    // Het formulier draagt de vraag in data-wesley1-bevestig.
    var verwijderFormulieren = document.querySelectorAll('form[data-wesley1-bevestig]');
    Array.prototype.forEach.call(verwijderFormulieren, function (formulier) {
        formulier.addEventListener('submit', function (gebeurtenis) {
            if (!window.confirm(formulier.getAttribute('data-wesley1-bevestig'))) {
                gebeurtenis.preventDefault();
            }
        });
    });
})();
