/**
 * Really Simple Color Picker in jQuery
 *
 * Copyright (c) 2008 Lakshan Perera (www.laktek.com)
 * Licensed under the MIT license.
 *
 * Menu 1.3.0 maintenance: keep the historical jQuery plugin API while
 * containing all implementation state inside this closure.
 */

(function ($) {
    'use strict';

    var selectorOwner = null;
    var selectorShowing = false;

    function toHex(color) {
        var match;
        var values;
        var pad;

        color = String(color || '');

        if (/^#?[0-9a-fA-F]{3}$/.test(color) || /^#?[0-9a-fA-F]{6}$/.test(color)) {
            return color.charAt(0) === '#' ? color : '#' + color;
        }

        match = color.match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i);
        if (!match) {
            return false;
        }

        values = [parseInt(match[1], 10), parseInt(match[2], 10), parseInt(match[3], 10)];
        if (values[0] > 255 || values[1] > 255 || values[2] > 255) {
            return false;
        }

        pad = function (value) {
            var hex = value.toString(16);
            return hex.length < 2 ? '0' + hex : hex;
        };

        return '#' + pad(values[0]) + pad(values[1]) + pad(values[2]);
    }

    function hideSelector() {
        var selector = $('div#color_selector');

        $(document).unbind('mousedown', checkMouse);
        selector.hide();
        selectorShowing = false;
    }

    function showSelector() {
        var selector = $('div#color_selector');
        var owner = $(selectorOwner);
        var offset = owner.offset();
        var hexColor;

        if (!selectorOwner || !offset) {
            return;
        }

        selector.css({
            top: offset.top + owner.outerHeight(),
            left: offset.left
        });

        hexColor = owner.prev('input').val();
        $('input#color_value').val(hexColor);
        selector.show();

        $(document).bind('mousedown', checkMouse);
        selectorShowing = true;
    }

    function toggleSelector(event) {
        if (event) {
            event.preventDefault();
        }

        selectorOwner = this;
        if (selectorShowing) {
            hideSelector();
        } else {
            showSelector();
        }
    }

    function changeColor(value) {
        var selectedValue = toHex(value);

        if (!selectedValue || !selectorOwner) {
            return;
        }

        $(selectorOwner).css('background-color', selectedValue);
        $(selectorOwner).prev('input').val(selectedValue).change();
        hideSelector();
    }

    function checkMouse(event) {
        var selector = 'div#color_selector';
        var selectorParent = $(event.target).parents(selector).length;

        if (event.target === $(selector)[0] || event.target === selectorOwner || selectorParent > 0) {
            return;
        }

        hideSelector();
    }

    function buildSelector() {
        var selector;
        var hexField;

        if ($('#color_selector').length > 0) {
            return;
        }

        selector = $("<div id='color_selector'></div>");

        $.each($.fn.colorPicker.defaultColors, function () {
            var swatch = $("<div class='color_swatch'>&nbsp;</div>");

            swatch.css('background-color', '#' + this);
            swatch.bind('click', function () {
                changeColor($(this).css('background-color'));
            });
            swatch.bind('mouseover', function () {
                $(this).css('border-color', '#598FEF');
                $('input#color_value').val(toHex($(this).css('background-color')));
            });
            swatch.bind('mouseout', function () {
                $(this).css('border-color', '#000');
                if (selectorOwner) {
                    $('input#color_value').val(toHex($(selectorOwner).css('background-color')));
                }
            });

            swatch.appendTo(selector);
        });

        hexField = $("<label for='color_value'>Hex</label><input type='text' size='8' id='color_value'/>");
        hexField.bind('keydown', function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                changeColor($(this).val());
            } else if (event.keyCode === 27) {
                event.preventDefault();
                hideSelector();
            }
        });

        $("<div id='color_custom'></div>").append(hexField).appendTo(selector);
        $('body').append(selector);
        selector.hide();
    }

    function buildPicker(element) {
        var control = $("<div class='color_picker'>&nbsp;</div>");

        control.css('background-color', $(element).val());
        control.bind('click', toggleSelector);
        $(element).after(control);

        $(element).bind('change', function () {
            var selectedValue = toHex($(element).val());
            if (selectedValue) {
                $(element).next('.color_picker').css('background-color', selectedValue);
            }
        });

        $(element).hide();
    }

    $.fn.colorPicker = function () {
        if (this.length > 0) {
            buildSelector();
        }

        return this.each(function () {
            buildPicker(this);
        });
    };

    $.fn.colorPicker.addColors = function (colorArray) {
        $.fn.colorPicker.defaultColors = $.fn.colorPicker.defaultColors.concat(colorArray);
    };

    $.fn.colorPicker.defaultColors = [
        '990033', 'ff3366', 'cc0033', 'ff0033', 'ff9999', 'cc3366', 'ffccff', 'cc6699',
        '993366', '660033', 'cc3399', 'ff99cc', 'ff66cc', 'ff99ff', 'ff6699', 'cc0066',
        'ff0066', 'ff3399', 'ff0099', 'ff33cc', 'ff00cc', 'ff66ff', 'ff33ff', 'ff00ff',
        'cc0099', '990066', 'cc66cc', 'cc33cc', 'cc99ff', 'cc66ff', 'cc33ff', '993399',
        'cc00cc', 'cc00ff', '9900cc', '990099', 'cc99cc', '996699', '663366', '660099',
        '9933cc', '660066', '9900ff', '9933ff', '9966cc', '330033', '663399', '6633cc',
        '6600cc', '9966ff', '330066', '6600ff', '6633ff', 'ccccff', '9999ff', '9999cc',
        '6666cc', '6666ff', '666699', '333366', '333399', '330099', '3300cc', '3300ff',
        '3333ff', '3333cc', '0066ff', '0033ff', '3366ff', '3366cc', '000066', '000033',
        '0000ff', '000099', '0033cc', '0000cc', '336699', '0066cc', '99ccff', '6699ff',
        '003366', '6699cc', '006699', '3399cc', '0099cc', '66ccff', '3399ff', '003399',
        '0099ff', '33ccff', '00ccff', '99ffff', '66ffff', '33ffff', '00ffff', '00cccc',
        '009999', '669999', '99cccc', 'ccffff', '33cccc', '66cccc', '339999', '336666',
        '006666', '003333', '00ffcc', '33ffcc', '33cc99', '00cc99', '66ffcc', '99ffcc',
        '00ff99', '339966', '006633', '336633', '669966', '66cc66', '99ff99', '66ff66',
        '339933', '99cc99', '66ff99', '33ff99', '33cc66', '00cc66', '66cc99', '009966',
        '009933', '33ff66', '00ff66', 'ccffcc', 'ccff99', '99ff66', '99ff33', '00ff33',
        '33ff33', '00cc33', '33cc33', '66ff33', '00ff00', '66cc33', '006600', '003300',
        '009900', '33ff00', '66ff00', '99ff00', '66cc00', '00cc00', '33cc00', '339900',
        '99cc66', '669933', '99cc33', '336600', '669900', '99cc00', 'ccff66', 'ccff33',
        'ccff00', '999900', 'cccc00', 'cccc33', '333300', '666600', '999933', 'cccc66',
        '666633', '999966', 'cccc99', 'ffffcc', 'ffff99', 'ffff66', 'ffff33', 'ffff00',
        'ffcc00', 'ffcc66', 'ffcc33', 'cc9933', '996600', 'cc9900', 'ff9900', 'cc6600',
        '993300', 'cc6633', '663300', 'ff9966', 'ff6633', 'ff9933', 'ff6600', 'cc3300',
        '996633', '330000', '663333', '996666', 'cc9999', '993333', 'cc6666', 'ffcccc',
        'ff3333', 'cc3333', 'ff6666', '660000', '990000', 'cc0000', 'ff0000', 'ff3300',
        'cc9966', 'ffcc99', 'ffffff', 'cccccc', '999999', '666666', '333333', '000000'
    ];
}(jQuery));
