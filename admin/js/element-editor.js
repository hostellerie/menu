/* Menu element editor field visibility. Compatible with Geeklog 2.1.1+. */
(function ($, window) {
    'use strict';

    var fieldIds = [
        '#urldiv',
        '#targetdiv',
        '#glcorediv',
        '#plugin',
        '#staticpage',
        '#glfunc',
        '#phpdiv',
        '#topic'
    ];

    function hideAll() {
        $.each(fieldIds, function (index, selector) {
            $(selector).hide();
        });
    }

    function syncFields() {
        var type = String($('#menutype').val() || '');

        hideAll();

        switch (type) {
            case '1': // submenu/container: optional destination URL
                $('#urldiv').show();
                break;

            case '2': // Geeklog Action
                $('#glfunc').show();
                break;

            case '3': // Geeklog Core menu
                $('#glcorediv').show();
                break;

            case '4': // plugin menu item
                $('#plugin').show();
                break;

            case '5': // static page
                $('#staticpage').show();
                break;

            case '6': // URL
                $('#urldiv, #targetdiv').show();
                break;

            case '7': // PHP function
                $('#phpdiv').show();
                break;

            case '8': // plugin-defined/other: no built-in destination field
                break;

            case '9': // topic
                $('#topic').show();
                break;
        }
    }

    function init() {
        var select = $('#menutype');
        if (!select.length) {
            return;
        }

        select.off('change.menuElementEditor')
            .on('change.menuElementEditor', syncFields);
        syncFields();
    }

    window.MENUElementEditor = {
        init: init,
        syncFields: syncFields
    };

    $(init);
}(jQuery, window));
