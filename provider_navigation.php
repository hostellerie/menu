<?php
/**
 * Menu 1.5.0: request-scoped navigation supplied by other Geeklog plugins.
 *
 * Storage-free integration: an ordinary URL element with
 * menu-provider:plugin:tree embeds a plugin tree; an ordinary URL element with
 * menu-anchor:plugin:node-id attaches its Menu-owned children to the named
 * provider node. These elements are never exposed as navigable URLs.
 */
if (!defined('VERSION')) {
    die('Direct access is not permitted.');
}

/** Parse a reserved non-executable Menu navigation marker. */
function MENU_providerMarker($value)
{
    $value = trim((string) $value);
    if (!preg_match('/^menu-(provider|anchor):([a-z][a-z0-9_]{0,63}):([a-z][a-z0-9_.-]{0,127})$/D', $value, $m)) {
        return null;
    }
    return array('kind' => $m[1], 'plugin' => $m[2], 'key' => $m[3]);
}

/** Check a destination, preserving only safe path and web URL forms. */
function MENU_providerSafeUrl($url)
{
    if (!is_string($url) || strlen($url) > 2048 || preg_match('/[\x00-\x1f\x7f]/', $url)) {
        return false;
    }
    $url = trim(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    if ($url === '' || $url === '#') {
        return true;
    }
    if (strpos($url, '//') === 0 || strpos($url, '\\') === 0) {
        return false;
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        return (bool) preg_match('~^https?://[^\s/]+(?:/[^\s]*)?$~iD', $url);
    }
    return $url[0] === '/' && strpos($url, '\\') === false;
}

/** Normalize a provider node recursively; reject the entire tree on failure. */
function MENU_providerNormalizeNodes($nodes, $plugin, &$seen, $depth, &$budget)
{
    if (!is_array($nodes) || $depth > 8) {
        return false;
    }
    $result = array();
    foreach ($nodes as $node) {
        --$budget;
        if ($budget < 0 || !is_array($node)
            || !isset($node['id'], $node['label'], $node['url'])
            || !is_string($node['id']) || !is_string($node['label'])
            || !preg_match('/^' . preg_quote($plugin, '/') . ':[a-z][a-z0-9_.-]{0,127}$/D', $node['id'])
            || isset($seen[$node['id']]) || strlen($node['label']) > 255
            || !MENU_providerSafeUrl($node['url'])) {
            return false;
        }
        $seen[$node['id']] = true;
        $label = trim(strip_tags($node['label']));
        if ($label === '') {
            return false;
        }
        $children = MENU_providerNormalizeNodes(
            isset($node['children']) ? $node['children'] : array(),
            $plugin, $seen, $depth + 1, $budget
        );
        if ($children === false) {
            return false;
        }
        $url = $node['url'];
        $result[] = array(
            'id' => 0, 'parent_id' => 0, 'provider_node_id' => $node['id'],
            'provider' => $plugin, 'label' => $label, 'type' => 10,
            'subtype' => $node['id'], 'url' => $url,
            'target' => '', 'rel' => '',
            'aria_label' => $label, 'active' => true,
            'selected' => function_exists('MENU_resolvedNodeSelected') ? MENU_resolvedNodeSelected($url) : false,
            'resolved' => true, 'children' => $children
        );
    }
    return $result;
}

/**
 * Invoke the provider's native Geeklog service in the current request context.
 * Providers MUST return only authorized nodes for the current user.
 */
function MENU_providerGetTree($plugin, $tree)
{
    global $_PLUGINS;
    if (!is_array($_PLUGINS) || !in_array($plugin, $_PLUGINS, true)
        || !function_exists('PLG_invokeService')) {
        return array();
    }
    $output = array();
    $message = array();
    $args = array('tree' => $tree, 'contract_version' => 1);
    $status = PLG_invokeService($plugin, 'getNavigationTree', $args, $output, $message);
    if (!defined('PLG_RET_OK') || $status !== PLG_RET_OK
        || !is_array($output)
        || !isset($output['contract_version'], $output['provider'], $output['tree'], $output['nodes'])
        || (int) $output['contract_version'] !== 1 || $output['provider'] !== $plugin
        || $output['tree'] !== $tree) {
        return array();
    }
    $seen = array();
    $budget = 300;
    $nodes = MENU_providerNormalizeNodes($output['nodes'], $plugin, $seen, 0, $budget);
    return $nodes === false ? array() : $nodes;
}

/**
 * Move ordinary Menu-owned children under stable provider node identifiers.
 * The anchor wrapper is never rendered. Missing anchors remain persisted in
 * Menu and are hidden until the provider returns the same stable ID again.
 */
function MENU_providerCompose($nodes)
{
    $overlays = array();
    MENU_providerCollectOverlays($nodes, $overlays);
    return MENU_providerApplyOverlays($nodes, $overlays);
}
function MENU_providerCollectOverlays($nodes, &$overlays)
{
    foreach ($nodes as $node) {
        if (isset($node['menu_anchor'])) {
            $key = $node['menu_anchor'];
            if (!isset($overlays[$key])) {
                $overlays[$key] = array();
            }
            foreach ($node['children'] as $child) {
                $overlays[$key][] = $child;
            }
        } elseif (!empty($node['children'])) {
            MENU_providerCollectOverlays($node['children'], $overlays);
        }
    }
}
function MENU_providerApplyOverlays($nodes, &$overlays)
{
    $result = array();
    foreach ($nodes as $node) {
        if (isset($node['menu_anchor'])) {
            continue;
        }
        if (!empty($node['children'])) {
            $node['children'] = MENU_providerApplyOverlays($node['children'], $overlays);
        }
        if (isset($node['provider_node_id']) && isset($overlays[$node['provider_node_id']])) {
            $node['children'] = array_merge($node['children'], $overlays[$node['provider_node_id']]);
            unset($overlays[$node['provider_node_id']]);
        }
        $result[] = $node;
    }
    return $result;
}

/** Safe HTML fallback for legacy Menu themes when provider markers are used. */
function MENU_providerRenderHtml($nodes, $depth = 0)
{
    if ($depth > 16 || empty($nodes)) {
        return '';
    }
    $html = '<ul>';
    foreach ($nodes as $node) {
        $label = htmlspecialchars($node['label'], ENT_QUOTES, 'UTF-8');
        $href = isset($node['url']) && MENU_providerSafeUrl($node['url']) ? $node['url'] : '';
        $html .= '<li>';
        if ($href !== '' && $href !== '#') {
            $html .= '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
        } else {
            $html .= '<span>' . $label . '</span>';
        }
        $html .= MENU_providerRenderHtml(isset($node['children']) ? $node['children'] : array(), $depth + 1);
        $html .= '</li>';
    }
    return $html . '</ul>';
}
