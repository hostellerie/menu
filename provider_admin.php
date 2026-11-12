<?php
/**
 * Graphical administration of navigation supplied by enabled plugins.
 * Provider nodes remain read-only; only ordinary Menu-owned records are saved.
 */
if (!defined('VERSION')) {
    die('Direct access is not permitted.');
}
require_once __DIR__ . '/provider_navigation.php';

/** Get the currently configured provider markers from the selected Menu menu. */
function MENU_providerAdminSources($menuId)
{
    global $Menus;
    $sources = array();
    if (!isset($Menus[$menuId]['elements'])) {
        return $sources;
    }
    foreach ($Menus[$menuId]['elements'] as $element) {
        if ((int) $element->active !== 1 || (int) $element->access <= 0 || (int) $element->type !== 6) {
            continue;
        }
        $marker = MENU_providerMarker($element->url);
        if ($marker === null || $marker['kind'] !== 'provider') {
            continue;
        }
        $nodes = MENU_providerGetTree($marker['plugin'], $marker['key']);
        if (empty($nodes)) {
            continue;
        }
        $sources[] = array(
            'marker_id' => (int) $element->id,
            'plugin' => $marker['plugin'],
            'tree' => $marker['key'],
            'nodes' => $nodes,
            'group_id' => (int) $element->group_id
        );
    }
    return $sources;
}

/** Search an already-filtered provider tree for a stable node ID. */
function MENU_providerAdminNodeExists($nodes, $id)
{
    foreach ($nodes as $node) {
        if (isset($node['provider_node_id']) && $node['provider_node_id'] === $id) {
            return true;
        }
        if (!empty($node['children']) && MENU_providerAdminNodeExists($node['children'], $id)) {
            return true;
        }
    }
    return false;
}

function MENU_providerAdminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function MENU_providerAdminNodeOptions($nodes, $depth)
{
    $html = '';
    foreach ($nodes as $node) {
        $label = str_repeat('— ', $depth) . $node['label'];
        $html .= '<option value="' . MENU_providerAdminEscape($node['provider_node_id']) . '">'
            . MENU_providerAdminEscape($label) . '</option>';
        if (!empty($node['children'])) {
            $html .= MENU_providerAdminNodeOptions($node['children'], $depth + 1);
        }
    }
    return $html;
}

/** Read-only visual tree; links/labels must never be trusted as authorization. */
function MENU_providerAdminRenderNodes($nodes)
{
    $html = '<ul class="menu-provider-tree">';
    foreach ($nodes as $node) {
        $html .= '<li><span class="menu-provider-node" title="'
            . MENU_providerAdminEscape($node['provider_node_id']) . '">'
            . MENU_providerAdminEscape($node['label'])
            . ' <small>(' . MENU_providerAdminEscape($node['provider_node_id']) . ')</small></span>';
        if (!empty($node['children'])) {
            $html .= MENU_providerAdminRenderNodes($node['children']);
        }
        $html .= '</li>';
    }
    return $html . '</ul>';
}

/**
 * The target is always drawn from the active provider's authorized tree.
 * A missing/hidden node cannot be used as a target, even by forged POST.
 */
function MENU_providerAdminValidate($source, $target, $placement, $label, $url)
{
    if (!in_array($placement, array('child', 'before', 'after'), true)) {
        return 'Invalid placement.';
    }
    if (!is_string($target) || !MENU_providerAdminNodeExists($source['nodes'], $target)) {
        return 'The target navigation node is unavailable.';
    }
    if (!is_string($label) || trim(strip_tags($label)) === '' || strlen($label) > 255) {
        return 'Invalid menu label.';
    }
    if (!MENU_providerSafeUrl($url) || trim((string) $url) === '' || trim((string) $url) === '#') {
        return 'Use an HTTP(S) URL or a root-relative path.';
    }
    return '';
}

/**
 * Discover available plugin trees without knowing any plugin-specific name.
 *
 * In v1 'main' is the standard entry point for one-click installation.
 * Plugins exposing alternate names can still use the advanced marker editor.
 * Tree discovery happens in the current request and never persists secrets.
 */
function MENU_providerAdminAvailable($menuId)
{
    global $_PLUGINS, $Menus;
    $available = array();
    if (!isset($Menus[$menuId]) || !is_array($_PLUGINS)) {
        return $available;
    }
    $installed = array();
    foreach ($Menus[$menuId]['elements'] as $element) {
        if ((int) $element->type !== 6) {
            continue;
        }
        $marker = MENU_providerMarker($element->url);
        if ($marker !== null && $marker['kind'] === 'provider') {
            $installed[$marker['plugin'] . ':' . $marker['key']] = true;
        }
    }
    foreach ($_PLUGINS as $plugin) {
        if (!is_string($plugin) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $plugin)
            || $plugin === 'menu') {
            continue;
        }
        $tree = 'main';
        $nodes = MENU_providerGetTree($plugin, $tree);
        if (empty($nodes)) {
            continue;
        }
        $available[] = array(
            'plugin' => $plugin,
            'tree' => $tree,
            'count' => count($nodes),
            'installed' => isset($installed[$plugin . ':' . $tree])
        );
    }
    return $available;
}

/**
 * Install the full provider tree as a single Menu-owned marker.
 * This action is idempotent, scoped to one existing Menu and never edits the
 * underlying plugin's navigation or previously created Menu elements.
 */
function MENU_providerAdminInstall($post)
{
    global $Menus, $_TABLES;
    $menuId = isset($post['menu']) ? (int) $post['menu'] : 0;
    $plugin = isset($post['plugin']) ? (string) $post['plugin'] : '';
    $tree = isset($post['tree']) ? (string) $post['tree'] : '';
    if ($menuId <= 0 || !isset($Menus[$menuId]) || !isset($_TABLES['menu_elements'])) {
        return 'Invalid Menu destination.';
    }
    if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $plugin)
        || !preg_match('/^[a-z][a-z0-9_.-]{0,127}$/D', $tree)) {
        return 'Invalid navigation provider.';
    }
    $selected = false;
    foreach (MENU_providerAdminAvailable($menuId) as $candidate) {
        if ($candidate['plugin'] === $plugin && $candidate['tree'] === $tree) {
            if ($candidate['installed']) {
                return ''; // Already installed; never create duplicates.
            }
            $selected = true;
            break;
        }
    }
    if (!$selected) {
        return 'The requested plugin navigation is unavailable.';
    }
    $marker = 'menu-provider:' . $plugin . ':' . $tree;
    $escaped = MENU_dbEscape($marker);
    // Recheck persisted state to avoid duplicate entries after stale page load.
    $query = DB_query("SELECT id FROM {$_TABLES['menu_elements']} WHERE menu_id="
        . $menuId . " AND element_type=6 AND element_url='" . $escaped . "' ORDER BY id ASC");
    if (DB_fetchArray($query)) {
        return '';
    }

    $groupId = isset($Menus[$menuId]['group_id']) ? (int) $Menus[$menuId]['group_id'] : 0;
    if ($groupId <= 0) {
        return 'Cannot determine Menu visibility group.';
    }
    $order = MENU_providerAdminNextOrder($menuId, 0);
    DB_save(
        $_TABLES['menu_elements'],
        'pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id',
        '0,' . $menuId . ",'" . MENU_dbEscape($plugin . ' navigation') . "',6,'"
        . $escaped . "'," . $order . ",1,'" . $escaped . "',''," . $groupId
    );
    if ((int) DB_insertId() <= 0) {
        return 'Unable to add the plugin navigation.';
    }
    MENU_invalidateRuntimeCache(true);
    return '';
}

/** Page displayed inside the existing CSRF-protected Menu administration. */
function MENU_providerAdminPage($menuId)
{
    global $_CONF, $LANG_MENU_PROVIDER;
    $sources = MENU_providerAdminSources($menuId);
    $escape = 'MENU_providerAdminEscape';
    $title = $escape($LANG_MENU_PROVIDER['title']);
    $html = '<section class="menu-provider-editor"><h2>' . $title . '</h2>'
        . '<p>' . $escape($LANG_MENU_PROVIDER['intro']) . '</p>'
        . '<p><a href="' . $escape($_CONF['site_admin_url']) . '/plugins/menu/index.php?mode=menu&amp;menu='
        . (int) $menuId . '">' . $escape($LANG_MENU_PROVIDER['back']) . '</a></p>';

    $available = MENU_providerAdminAvailable($menuId);
    $html .= '<h3>' . $escape($LANG_MENU_PROVIDER['discover']) . '</h3>'
        . '<p>' . $escape($LANG_MENU_PROVIDER['discover_help']) . '</p>';
    $found = false;
    foreach ($available as $candidate) {
        $found = true;
        $html .= '<form method="post" action="' . $escape($_CONF['site_admin_url'])
            . '/plugins/menu/index.php" style="display:flex;align-items:center;gap:1rem;margin:.5rem 0">'
            . MENU_adminTokenInput()
            . '<input type="hidden" name="mode" value="providerinstall">'
            . '<input type="hidden" name="menu" value="' . (int) $menuId . '">'
            . '<input type="hidden" name="plugin" value="' . $escape($candidate['plugin']) . '">'
            . '<input type="hidden" name="tree" value="' . $escape($candidate['tree']) . '">'
            . '<strong>' . $escape($candidate['plugin']) . '</strong>'
            . '<span>' . (int) $candidate['count'] . ' ' . $escape($LANG_MENU_PROVIDER['sections']) . '</span>'
            . '<button type="submit"' . ($candidate['installed'] ? ' disabled="disabled"' : '') . '>'
            . $escape($candidate['installed'] ? $LANG_MENU_PROVIDER['installed'] : $LANG_MENU_PROVIDER['install'])
            . '</button></form>';
    }
    if (!$found) {
        $html .= '<p>' . $escape($LANG_MENU_PROVIDER['none_available']) . '</p>';
    }
    if (empty($sources)) {
        return $html . '<p>' . $escape($LANG_MENU_PROVIDER['none']) . '</p></section>';
    }
    $html .= '<style>.menu-provider-editor .menu-provider-card{border:1px solid #bbb;border-radius:7px;padding:1rem;margin:1rem 0}'
        . '.menu-provider-tree{list-style:none;margin:.5rem 0;padding-left:1rem;border-left:2px solid #ccc}'
        . '.menu-provider-tree li{padding:.3rem 0}.menu-provider-node{display:inline-block;padding:.25rem .5rem;background:#eee;color:#222;border-radius:4px}'
        . '.menu-provider-node small{font-size:.8em;color:#444}'
        . '.menu-provider-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-top:1rem}'
        . '.menu-provider-form label{display:flex;flex-direction:column;gap:.25rem}'
        . '.menu-provider-form input,.menu-provider-form select{max-width:100%;padding:.35rem}'
        . '.menu-provider-form .menu-provider-submit{align-self:end}</style>';

    foreach ($sources as $source) {
        $html .= '<div class="menu-provider-card"><h3>' . $escape($source['plugin']) . ' / '
            . $escape($source['tree']) . '</h3>'
            . '<p>' . $escape($LANG_MENU_PROVIDER['read_only']) . '</p>'
            . MENU_providerAdminRenderNodes($source['nodes']);
        $html .= '<form class="menu-provider-form" method="post" action="'
            . $escape($_CONF['site_admin_url']) . '/plugins/menu/index.php">'
            . MENU_adminTokenInput()
            . '<input type="hidden" name="mode" value="provideradd">'
            . '<input type="hidden" name="menu" value="' . (int) $menuId . '">'
            . '<input type="hidden" name="marker_id" value="' . (int) $source['marker_id'] . '">'
            . '<label>' . $escape($LANG_MENU_PROVIDER['target']) . '<select name="node_id" required>'
            . MENU_providerAdminNodeOptions($source['nodes'], 0) . '</select></label>'
            . '<label>' . $escape($LANG_MENU_PROVIDER['placement']) . '<select name="placement">'
            . '<option value="child">' . $escape($LANG_MENU_PROVIDER['child']) . '</option>'
            . '<option value="before">' . $escape($LANG_MENU_PROVIDER['before']) . '</option>'
            . '<option value="after">' . $escape($LANG_MENU_PROVIDER['after']) . '</option>'
            . '</select></label>'
            . '<label>' . $escape($LANG_MENU_PROVIDER['label']) . '<input type="text" name="custom_label" maxlength="255" required></label>'
            . '<label>' . $escape($LANG_MENU_PROVIDER['url']) . '<input type="text" name="custom_url" placeholder="/example/" maxlength="2048" required></label>'
            . '<button class="menu-provider-submit" type="submit">' . $escape($LANG_MENU_PROVIDER['add']) . '</button>'
            . '</form></div>';
    }
    return $html . '</section>';
}

/**
 * Persist an ordinary Menu-owned link under a placement marker.
 * This function is invoked ONLY after MENU_adminEnforceCsrf has checked
 * menu.admin, POST method and SEC token. Revalidate all IDs and source data.
 */
function MENU_providerAdminSave($post)
{
    global $_TABLES;

    $menuId = isset($post['menu']) ? (int) $post['menu'] : 0;
    $markerId = isset($post['marker_id']) ? (int) $post['marker_id'] : 0;
    $target = isset($post['node_id']) ? (string) $post['node_id'] : '';
    $placement = isset($post['placement']) ? (string) $post['placement'] : '';
    $label = isset($post['custom_label']) ? (string) $post['custom_label'] : '';
    $url = isset($post['custom_url']) ? (string) $post['custom_url'] : '';

    $source = null;
    foreach (MENU_providerAdminSources($menuId) as $candidate) {
        if ($candidate['marker_id'] === $markerId) {
            $source = $candidate;
            break;
        }
    }
    if ($source === null) {
        return 'The provider source is not available for this menu.';
    }
    $error = MENU_providerAdminValidate($source, $target, $placement, $label, $url);
    if ($error !== '') {
        return $error;
    }

    $key = substr($target, strlen($source['plugin']) + 1);
    $kind = $placement === 'child' ? 'anchor' : $placement;
    $markerUrl = 'menu-' . $kind . ':' . $source['plugin'] . ':' . $key;
    $escapedMarker = MENU_dbEscape($markerUrl);
    $existing = DB_query(
        "SELECT id FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $menuId
        . " AND element_type=6 AND element_url='" . $escapedMarker . "' ORDER BY id ASC"
    );
    $row = DB_fetchArray($existing);
    $anchorId = is_array($row) && isset($row['id']) ? (int) $row['id'] : 0;

    if ($anchorId <= 0) {
        $anchorOrder = MENU_providerAdminNextOrder($menuId, 0);
        DB_save(
            $_TABLES['menu_elements'],
            'pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id',
            '0,' . $menuId . ",'" . MENU_dbEscape('Provider: ' . $target . ' (' . $placement . ')') . "',6,'"
            . $escapedMarker . "'," . $anchorOrder . ",1,'" . $escapedMarker . "','',"
            . (int) $source['group_id']
        );
        $anchorId = (int) DB_insertId();
        if ($anchorId <= 0) {
            return 'Unable to create the menu placement.';
        }
    }
    $order = MENU_providerAdminNextOrder($menuId, $anchorId);
    DB_save(
        $_TABLES['menu_elements'],
        'pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id',
        $anchorId . ',' . $menuId . ",'" . MENU_dbEscape(trim(strip_tags($label))) . "',6,'"
        . MENU_dbEscape(trim($url)) . "'," . $order . ",1,'"
        . MENU_dbEscape(trim($url)) . "',''," . (int) $source['group_id']
    );
    if ((int) DB_insertId() <= 0) {
        return 'Unable to save the custom menu entry.';
    }
    MENU_invalidateRuntimeCache(true);
    return '';
}

function MENU_providerAdminNextOrder($menuId, $parentId)
{
    global $_TABLES;
    $result = DB_query(
        "SELECT MAX(element_order) AS max_order FROM {$_TABLES['menu_elements']}"
        . ' WHERE menu_id=' . (int) $menuId . ' AND pid=' . (int) $parentId
    );
    $row = DB_fetchArray($result);
    return (is_array($row) ? (int) $row['max_order'] : 0) + 10;
}
