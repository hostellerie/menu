<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | element_editor_runtime.php                                                |
// |                                                                           |
// | Runtime wiring for Menu element create/edit forms.                        |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return true when the current request is a Menu element create/edit screen.
 *
 * @return bool
 */
function MENU_elementEditorIsAdminRequest()
{
    if (!function_exists('MENU_adminIsControllerRequest') || !MENU_adminIsControllerRequest()) {
        return false;
    }

    $mode = function_exists('MENU_adminCurrentMode') ? MENU_adminCurrentMode() : '';
    return $mode === 'new' || $mode === 'edit';
}

/**
 * Register the authoritative element editor behavior through Geeklog Scripts.
 *
 * The legacy controller still emits old JavaScript and an old server-side
 * select. This runtime owns the final field visibility and type list. It keeps
 * the stored type while editing and chooses Geeklog Action for new elements.
 *
 * @return void
 */
function MENU_registerElementEditorRuntime()
{
    global $_CONF, $_SCRIPTS;

    if (!MENU_elementEditorIsAdminRequest() || !isset($_SCRIPTS) || !is_object($_SCRIPTS)) {
        return;
    }

    if (method_exists($_SCRIPTS, 'setJavaScriptLibrary')) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
    }

    $endpoint = rtrim($_CONF['site_admin_url'], '/') . '/plugins/menu/type_options.php';
    $endpointJson = json_encode($endpoint);
    $modeJson = json_encode(MENU_adminCurrentMode());
    if ($endpointJson === false || $modeJson === false) {
        return;
    }

    $js = "jQuery(function($) {\n"
        . "    var select = $('#menutype');\n"
        . "    if (!select.length) { return; }\n"
        . "    var mode = " . $modeJson . ";\n"
        . "    var menuId = mode === 'edit' ? $('#menu').val() : $('#menunid').val();\n"
        . "    var mid = mode === 'edit' ? $('#id').val() : 0;\n"
        . "    var panels = '#urldiv,#targetdiv,#glfunc,#glcorediv,#plugin,#staticpage,#topic,#phpdiv';\n"
        . "    function syncFields() {\n"
        . "        var type = String(select.val() || '');\n"
        . "        $(panels).hide();\n"
        . "        switch (type) {\n"
        . "            case '1': $('#urldiv').show(); break;\n"
        . "            case '2': $('#glfunc').show(); break;\n"
        . "            case '3': $('#glcorediv').show(); break;\n"
        . "            case '4': $('#plugin').show(); break;\n"
        . "            case '5': $('#staticpage').show(); break;\n"
        . "            case '6': $('#urldiv,#targetdiv').show(); break;\n"
        . "            case '7': $('#phpdiv').show(); break;\n"
        . "            case '9': $('#topic').show(); break;\n"
        . "        }\n"
        . "    }\n"
        . "    select.off('change.menuElementRuntime').on('change.menuElementRuntime', syncFields);\n"
        . "    $(panels).hide();\n"
        . "    if (mode === 'new' && select.find('option[value=\"2\"]').length) {\n"
        . "        select.val('2');\n"
        . "    }\n"
        . "    syncFields();\n"
        . "    $.getJSON(" . $endpointJson . ", {menu: menuId, mid: mid})\n"
        . "        .done(function(data) {\n"
        . "            if (!data || !data.types) { syncFields(); return; }\n"
        . "            select.empty();\n"
        . "            $.each(data.types, function(index, item) {\n"
        . "                $('<option></option>').attr('value', item.id).text(item.label).appendTo(select);\n"
        . "            });\n"
        . "            var wanted = mode === 'edit' ? data.currentType : data.defaultType;\n"
        . "            if (wanted !== null && typeof wanted !== 'undefined') {\n"
        . "                select.val(String(wanted));\n"
        . "            }\n"
        . "            $('#menutype-hidden').empty();\n"
        . "            if (mode === 'edit' && data.locked) {\n"
        . "                select.prop('disabled', true);\n"
        . "                $('<input>').attr({type: 'hidden', name: 'menutype', value: wanted}).appendTo('#menutype-hidden');\n"
        . "            } else {\n"
        . "                select.prop('disabled', false);\n"
        . "            }\n"
        . "            $('#execute').prop('disabled', false);\n"
        . "            syncFields();\n"
        . "        })\n"
        . "        .fail(function() {\n"
        . "            if (mode === 'edit') { $('#execute').prop('disabled', true); }\n"
        . "            syncFields();\n"
        . "        });\n"
        . "});";

    if (method_exists($_SCRIPTS, 'setJavaScript')) {
        $_SCRIPTS->setJavaScript($js, true);
    }
}

MENU_registerElementEditorRuntime();
