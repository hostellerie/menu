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
 * Build the stored editor state directly from the database.
 *
 * This state intentionally contains only values that are safe to resolve during
 * the early plugin bootstrap. Language-dependent type labels are loaded later
 * from admin/type_options.php, after Geeklog has completed initialization.
 *
 * @return array
 */
function MENU_elementEditorServerState()
{
    global $_TABLES;

    $mode = function_exists('MENU_adminCurrentMode') ? MENU_adminCurrentMode() : '';
    $menuId = $mode === 'edit'
        ? (isset($_REQUEST['menu']) ? (int) $_REQUEST['menu'] : 0)
        : (isset($_REQUEST['menuid']) ? (int) $_REQUEST['menuid'] : 0);
    $mid = $mode === 'edit' && isset($_REQUEST['mid']) ? (int) $_REQUEST['mid'] : 0;

    $state = array(
        'mode' => $mode,
        'menuId' => $menuId,
        'mid' => $mid,
        'currentType' => null,
        'currentSubtype' => '',
        'locked' => false,
    );

    if ($menuId <= 0 || $mid <= 0 || !isset($_TABLES['menu_elements'])) {
        return $state;
    }

    $result = DB_query(
        'SELECT element_type, element_subtype FROM ' . $_TABLES['menu_elements']
        . ' WHERE id=' . $mid . ' AND menu_id=' . $menuId
    );
    if (DB_numRows($result) > 0) {
        $row = DB_fetchArray($result);
        $state['currentType'] = (int) $row['element_type'];
        $state['currentSubtype'] = (string) $row['element_subtype'];
        $state['locked'] = ($state['currentType'] === 1);
    }

    return $state;
}

/**
 * Register the element editor behavior through Geeklog Scripts.
 *
 * The runtime never clears the server-rendered type select unless a complete,
 * authoritative replacement list has been returned by type_options.php. This
 * prevents an early bootstrap (before language arrays are available) from
 * producing an empty Type selector.
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

    $stateJson = json_encode(MENU_elementEditorServerState());
    $endpointJson = json_encode(rtrim($_CONF['site_admin_url'], '/') . '/plugins/menu/type_options.php');
    if ($stateJson === false || $endpointJson === false) {
        return;
    }

    $js = "jQuery(function($) {\n"
        . "    var state = " . $stateJson . ";\n"
        . "    var endpoint = " . $endpointJson . ";\n"
        . "    var select = $('#menutype');\n"
        . "    if (!select.length) { return; }\n"
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
        . "    function preserveUnavailableResource(type, subtype) {\n"
        . "        if (!subtype) { return; }\n"
        . "        var field = null;\n"
        . "        var resource = 'resource';\n"
        . "        if (type === '4') { field = $('#pluginname'); resource = 'plugin'; }\n"
        . "        if (type === '5') { field = $('#spname'); resource = 'static page'; }\n"
        . "        if (type === '9') { field = $('#topicname'); resource = 'topic'; }\n"
        . "        if (type === '5' && (!field || !field.length)) {\n"
        . "            field = $('<select id=\"spname\" name=\"spname\"></select>').appendTo('#staticpage p');\n"
        . "        }\n"
        . "        if (!field || !field.length) { return; }\n"
        . "        var found = false;\n"
        . "        field.find('option').each(function() {\n"
        . "            if (String($(this).val()) === String(subtype)) { found = true; }\n"
        . "        });\n"
        . "        if (!found) {\n"
        . "            $('<option></option>').val(String(subtype)).text('[Unavailable ' + resource + '] ' + String(subtype)).appendTo(field);\n"
        . "        }\n"
        . "        field.val(String(subtype));\n"
        . "    }\n"
        . "    function applyTypeResponse(data) {\n"
        . "        if (!data || !$.isArray(data.types) || data.types.length === 0) { return false; }\n"
        . "        select.empty();\n"
        . "        $.each(data.types, function(index, item) {\n"
        . "            $('<option></option>').attr('value', item.id).text(item.label).appendTo(select);\n"
        . "        });\n"
        . "        var wanted = state.mode === 'edit' ? data.currentType : data.defaultType;\n"
        . "        if (wanted !== null && typeof wanted !== 'undefined') { select.val(String(wanted)); }\n"
        . "        $('#menutype-hidden').empty();\n"
        . "        if (state.mode === 'edit' && data.locked) {\n"
        . "            select.prop('disabled', true);\n"
        . "            $('<input>').attr({type: 'hidden', name: 'menutype', value: wanted}).appendTo('#menutype-hidden');\n"
        . "        } else {\n"
        . "            select.prop('disabled', false);\n"
        . "        }\n"
        . "        if (state.mode === 'edit') {\n"
        . "            preserveUnavailableResource(String(state.currentType), state.currentSubtype);\n"
        . "        }\n"
        . "        $('#execute').prop('disabled', false);\n"
        . "        syncFields();\n"
        . "        return true;\n"
        . "    }\n"
        . "    select.off('change.menuElementRuntime').on('change.menuElementRuntime', syncFields);\n"
        . "    if (state.mode === 'new' && select.find('option[value=\"2\"]').length) { select.val('2'); }\n"
        . "    syncFields();\n"
        . "    $.getJSON(endpoint, {menu: state.menuId, mid: state.mid})\n"
        . "        .done(function(data) {\n"
        . "            if (!applyTypeResponse(data) && state.mode === 'edit') { $('#execute').prop('disabled', true); }\n"
        . "        })\n"
        . "        .fail(function() {\n"
        . "            if (state.mode === 'edit') { $('#execute').prop('disabled', true); }\n"
        . "        });\n"
        . "});";

    if (method_exists($_SCRIPTS, 'setJavaScript')) {
        $_SCRIPTS->setJavaScript($js, true);
    }
}

MENU_registerElementEditorRuntime();
