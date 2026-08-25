/* Menu element editor behaviour. Compatible with Geeklog 2.1.1+. */
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
            case '1':
                $('#urldiv').show();
                break;
            case '2':
                $('#glfunc').show();
                break;
            case '3':
                $('#glcorediv').show();
                break;
            case '4':
                $('#plugin').show();
                break;
            case '5':
                $('#staticpage').show();
                break;
            case '6':
                $('#urldiv, #targetdiv').show();
                break;
            case '7':
                $('#phpdiv').show();
                break;
            case '8':
                break;
            case '9':
                $('#topic').show();
                break;
        }
    }

    function menuId() {
        var value = $('#menunid').val();
        if (typeof value === 'undefined') {
            value = $('input[name="menu"]').val();
        }
        return String(value || '');
    }

    function refreshOrder() {
        var parent = String($('#pid').val() || '0');
        var menu = menuId();
        if (!menu) {
            return;
        }
        $('#displayafter').load(
            'getorder.php?optionid=' + encodeURIComponent(parent)
            + '&menuid=' + encodeURIComponent(menu)
        );
    }

    function init() {
        var select = $('#menutype');
        if (!select.length) {
            return;
        }

        $('div#menu').show();
        select.off('change.menuElementEditor')
            .on('change.menuElementEditor', syncFields);
        $('#pid').off('change.menuElementEditor')
            .on('change.menuElementEditor', refreshOrder);
        syncFields();
    }

    window.MENUElementEditor = {
        init: init,
        syncFields: syncFields,
        refreshOrder: refreshOrder
    };

    $(init);
}(jQuery, window));
