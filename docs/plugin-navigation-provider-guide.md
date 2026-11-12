# Implementing a navigation provider for Menu 1.5.0

> **Implementation guide for plugin authors.** Menu 1.5.0 development branch provides a read-only Geeklog Plugin Services integration. This is **not** a released stable API. Test with Geeklog 2.2.2 and your intended PHP runtime before deploying. The provider must implement its own authorization checks.

## Overview

Your plugin **owns** its navigation tree. Menu embeds that tree into an existing Menu menu, renders it in modern resolved-tree consumers and in a basic legacy HTML fallback, and can attach administrator-created Menu submenus under your stable node IDs. Menu does not import or copy your tree into its database.

The integration does not depend on Example Plugin. Any enabled Geeklog plugin may provide a tree under its own plugin identifier.

## Required callbacks in your plugin

For a plugin whose Geeklog identifier is `exampleplugin`, add both functions in its loaded `functions.inc` (or ensure the second function is loaded before Menu queries it):

```php
/**
 * Geeklog 2.2.2 PLG_invokeService requires this callback.
 * This does not itself expose a remotely callable public exampleplugin API:
 * review the plugin's existing webservice access policy separately.
 */
function plugin_wsEnabled_exampleplugin()
{
    return true;
}

/**
 * Responds to PLG_invokeService('exampleplugin', 'getNavigationTree', ...).
 *
 * Do not use user identifiers supplied in $args for authorization.
 * All rules are resolved against the authenticated Geeklog session.
 */
function service_getNavigationTree_exampleplugin($args, &$output, &$svc_msg)
{
    global $_CONF, $_USER;

    $output = array();
    $svc_msg = array();

    $tree = isset($args['tree']) ? (string) $args['tree'] : '';
    if ($tree !== 'main' || !isset($_USER['uid'])
        || (int) $_USER['uid'] <= 1
        || !function_exists('SEC_hasRights')
        || !SEC_hasRights('exampleplugin.view')) {
        return PLG_RET_ERROR;
    }

    // Example routes only: adjust to your plugin's actual public routes.
    $base = rtrim($_CONF['site_url'], '/') . '/exampleplugin/';
    $nodes = array(
        array(
            'id' => 'exampleplugin:home',
            'label' => 'Home',
            'url' => $base,
            'children' => array()
        ),
        array(
            'id' => 'exampleplugin:resources',
            'label' => 'Resources',
            'url' => $base . 'resources/',
            'children' => array()
        )
    );

    // IMPORTANT: filter every nested node according to the current user's
    // active resources itemships and private resource access rules.
    // Translate labels using the provider's active language files.
    $output = array(
        'contract_version' => 1,
        'provider' => 'exampleplugin',
        'tree' => 'main',
        'nodes' => $nodes
    );
    return PLG_RET_OK;
}
```

**Important security implication:** In Geeklog, `plugin_wsEnabled_*` enables Plugin Services for that plugin. Before returning `true`, audit **all existing service callbacks** in your plugin and protect or disable any that should not be available through the CMS web-service routing. A dedicated service not exposed to unauthenticated clients is preferable if the plugin has sensitive existing webservices. Never rely on Menu rendering as an authorization barrier.

### Output contract

Return an `array` with `contract_version` exactly `1`, `provider` exactly matching the plugin identifier, `tree` exactly matching the requested tree key, and `nodes` an array of nodes.

Every node must have:
- `id`: stable globally namespaced ID of the form `exampleplugin:resources.items`, unique within the tree. Supported suffix characters are lowercase letters, numbers, periods, underscores and dashes; first character must be a lowercase letter.
- `label`: nonempty plain text (no HTML), maximum 255 bytes.
- `url`: absolute HTTPS/HTTP URL, root-relative path beginning with `/`, empty string or `#`. No `javascript:`, `data:`, protocol-relative URLs or arbitrary schemes.
- `children`: optional recursively nested array; default empty.

Limit: 8 levels of nested children and 300 total nodes per provider tree. On any invalid node, Menu rejects the **entire** provider tree. Preserve node IDs across releases even when translating, renaming, moving or reordering sections.

**Permission requirements:** the provider must omit all nodes unauthorized for the *current* user. Provider nodes must be filtered for the current user. Roles or membership in a plugin-specific group must not grant access to other users' private records. Every destination must independently enforce its own ACL. Do not put account balances or confidential account labels in the navigation response.

## One-click installation in Menu administration

**Recommended workflow for Menu 1.5.0:** plugin authors expose the native Geeklog `getNavigationTree` service described above, with a `main` tree. No Menu SQL writes, dependencies, or installation hooks are required from the provider.

1. An administrator opens Menu administration and selects a menu such as `navigation`.
2. The administrator opens **Plugin navigation editor**.
3. Menu discovers active, compatible plugins that return a valid `main` navigation tree for the current request. Each is shown with an **Add to this menu** button.
4. One click inserts the plugin's **entire top-level hierarchy and all submenus**, using one Menu-owned provider marker. The button then reads **Already added**.
5. Later provider upgrades update the displayed tree dynamically without recreating or duplicating menu items. Local Menu additions are preserved.

**This is the default path.** The provider needs no knowledge of Menu's database or administrative UI. The integration is optional: plugins can still function without Menu. Other named trees can be configured through the advanced marker workflow until automatic discovery beyond `main` is implemented.

### Optional custom entries and advanced administration

After the one-click installation, the same graphical editor displays the provider tree as a read-only hierarchy. The administrator may select a node and add a custom link **Inside**, **Before**, or **After** it. Menu validates the destination, creates or reuses a placement anchor, and stores the new link independently of the provider. Its label and access settings can subsequently be edited using the ordinary Menu editor.

Administrators can still use existing External URL markers manually for advanced setups:

```text
menu-provider:exampleplugin:main
menu-anchor:exampleplugin:resources
menu-before:exampleplugin:resources
menu-after:exampleplugin:resources
```

`menu-provider` inserts the entire provider tree. `anchor` adds Menu-owned children inside a provider node; `before` and `after` place them immediately alongside it. The provider's node IDs must remain stable. If a node disappears or becomes unavailable, saved additions remain in storage without leaking the restricted node. The markers are internal configuration, not browser URLs.

The current graphical editor adds ordinary URL links. For Static Pages and other Menu element types, add elements to a placement anchor via the standard editor. Richer drag-and-drop positioning and diagnostics for orphaned anchors remain future improvements.

### Theme integration

For a theme that consumes the resolved-tree API:

```php
$tree = MENU_getResolvedTree('navigation');
```

Provider nodes are returned with `id = 0` (preserving the legacy v1 integer ID field) and an additional `provider_node_id` string plus `provider` identifier. Themes should use `provider_node_id` for stable keys on provider nodes. Nested `children` include both provider-defined and administrator-added entries.

For existing themes using `MENU_getMenu('navigation')`, provider menus use a basic, escaped nested-list HTML renderer. Existing menus without provider markers keep their legacy rendering unchanged. Test your CSS/JS styling on your deployed theme; advanced legacy CSS parity is not yet guaranteed.

## Failure, upgrade and maintenance

- Disabled/unavailable providers return no nodes, and saved Menu anchors/custom links are retained.
- Invalid or unauthorized trees are suppressed (fail closed).
- Return URLs must not expose financial or security secrets.
- Menus are resolved in the authenticated request context; do not cache across users/families without a complete permission-aware cache key.
- This integration does not grant Menu rights to change provider nodes or perform actions inside the provider.
- No installation-time writes to Menu tables are necessary and Menu can operate without your plugin.
- Use the included tests as a starting point for authenticated, anonymous, role-scoped and cross-tenant scenarios.

See [design document](dynamic-plugin-navigation-providers.md) and [roadmap](../ROADMAP.md).
