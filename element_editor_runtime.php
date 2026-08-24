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
 * Build the authoritative editor state directly from the database.
 *
 * This deliberately does not depend on the in-memory $Menus cache. The admin
 * controller historically builds an incomplete type select while editing and
 * older cache state can therefore make a stored Geeklog Action look like a
 * Plugin. Reading the stored row here makes the editor deterministic.
 *
 * @return array
 */
function MENU_elementEditorServerState()
{
    global $_TABLES, $_PLUGINS, $LANG_MENU_TYPES;

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
        'defaultType' => 2,
        'locked' => false,
        'types' => array(),
    );

    if ($menuId <= 0 || !isset($_TABLES['menu'])) {
        return $state;
    }

    $menuType = (int) DB_getItem($_TABLES['menu'], 'menu_type', 'id=' . $menuId);
    if ($menuType <= 0) {
        return $state;
    }

    if ($mid > 0 && isset($_TABLES['menu_elements'])) {
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
    }

    $hasStaticPages = isset($_PLUGINS) && is_array($_PLUGINS)
        && in_array('staticpages', $_PLUGINS, true);
    $types = MENU_getAllowedElementTypes(
        is_array($LANG_MENU_TYPES) ? $LANG_MENU_TYPES : array(),
        $menuType,
        $hasStaticPages,
        $state['currentType']
    );

    $defaultType = MENU_defaultElementType($types);
    if ($defaultType !== null) {
        $state['defaultType'] = (int) $defaultType;
    }

    foreach ($types as $typeId => $label) {
        $state['types'][] = array(
            'id' => (int) $typeId,
            'label' => (string) $label,
        );
    }

    return $state;
}

/**
 * Register the authoritative element editor behavior through Geeklog Scripts.
 *
 * The server state is embedded in the page. No AJAX request is required to
 * determine the stored type or the allowed type list. JavaScript only renders
 * that state and switches the related detail panel.
 *
 * @return void
 */
function MENU_registerElementEditorRuntime()
{
    global $_SCRIPTS;

    if (!MENU_elementEditorIsAdminRequest() || !isset($_SCRIPTS) || !is_object($_SCRIPTS)) {
        return;
    }

    if (method_exists($_SCRIPTS, 'setJavaScriptLibrary')) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
    }

    $stateJson = json_encode(MENU_elementEditorServerState());
    if ($stateJson === false) {
        return;
    }

    $js = "jQuery(function($) {\n"
        . "    var state = " . $stateJson . ";\n"
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
        . "        if (type === '4') { field = $('#pluginname'); }\n"
        . "        if (type === '5') { field = $('#spname'); }\n"
        . "        if (type === '9') { field = $('#topicname'); }\n"
        . "        if (type === '5' && !field.length) {\n"
        . "            field = $('<select id=\"spname\" name=\"spname\"></select>').appendTo('#staticpage p');\n"
        . "        }\n"
        . "        if (!field || !field.length) { return; }\n"
        . "        var found = false;\n"
        . "        field.find('option').each(function() {\n"
        . "            if (String($(this).val()) === String(subtype)) { found = true; }\n"
        . "        });\n"
        . "        if (!found) {\n"
        . "            $('<option></option>').val(String(subtype)).text('[Unavailable] ' + String(subtype)).appendTo(field);\n"
        . "        }\n"
        . "        field.val(String(subtype));\n"
        . "    }\n"
        . "    select.empty();\n"
        . "    $.each(state.types || [], function(index, item) {\n"
        . "        $('<option></option>').attr('value', item.id).text(item.label).appendTo(select);\n"
        . "    });\n"
        . "    var wanted = state.mode === 'edit' ? state.currentType : state.defaultType;\n"
        . "    if (wanted !== null && typeof wanted !== 'undefined') { select.val(String(wanted)); }\n"
        . "    $('#menutype-hidden').empty();\n"
        . "    if (state.mode === 'edit' && state.locked) {\n"
        . "        select.prop('disabled', true);\n"
        . "        $('<input>').attr({type: 'hidden', name: 'menutype', value: wanted}).appendTo('#menutype-hidden');\n"
        . "    } else {\n"
        . "        select.prop('disabled', false);\n"
        . "    }\n"
        . "    if (state.mode === 'edit') {\n"
        . "        preserveUnavailableResource(String(state.currentType), state.currentSubtype);\n"
        . "    }\n"
        . "    select.off('change.menuElementRuntime').on('change.menuElementRuntime', syncFields);\n"
        . "    $('#execute').prop('disabled', false);\n"
        . "    syncFields();\n"
        . "});";

    if (method_exists($_SCRIPTS, 'setJavaScript')) {
        $_SCRIPTS->setJavaScript($js, true);
    }
}

MENU_registerElementEditorRuntime();
