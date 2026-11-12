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

## Configuring Menu as administrator

**New in the 1.5.0 development branch:** the Menu administration now includes a **Plugin navigation editor** link in the menu builder. It presents each active provider tree as a read-only hierarchical outline and lets administrators add their own links through a validated form.

1. Open Menu administration and choose a menu such as `navigation`.
2. Add one **External URL** element with the destination `menu-provider:exampleplugin:main`, using the plugin identifier and the tree name supported by your provider. This places its entire top-level navigation into the selected Menu menu.
3. Return to that Menu menu and select **Plugin navigation editor**.
4. Select the provider node to extend. Choose **Inside**, **Before**, or **After**, then enter a custom link label and a root-relative or HTTP(S) URL.
5. Submit. Menu creates (or reuses) a hidden placement anchor and saves the custom link as a normal Menu-owned item. Edit/remove that link later through the regular element editor, including its group-based visibility settings.

The provider's nodes remain read-only. Only the administrator's own entries are stored in Menu. Labels and routes supplied by the provider are re-read on every request, using the current visitor's permissions. A missing or disabled provider does not delete any saved customizations.

**No new database table is required.** For scripted or advanced setups, these existing External URL marker forms remain supported:

```text
menu-provider:exampleplugin:main
menu-anchor:exampleplugin:resources
menu-before:exampleplugin:resources
menu-after:exampleplugin:resources
```

`menu-provider` expands the provider's top-level tree at that location. An `anchor` places its ordinary Menu-owned children **inside** the specified provider node, while `before` and `after` place those children as siblings immediately adjacent to the target node. Placement anchors are never navigable browser links or provider-owned records. The marker item's own parent menu and group permissions still apply.

Provider node IDs, such as `exampleplugin:resources`, must remain stable across plugin upgrades. If a target ID disappears, its local custom entries remain stored but are hidden until that ID returns; the plugin's access rules must not be bypassed by the marker. A future version may add more visual diagnostics for orphaned anchors and drag-and-drop reordering.

**Limitations:** the editor currently adds URL links; custom Static Pages, Geeklog actions and submenus can be added by using the ordinary Menu element editor beneath a placement anchor. The legacy theme renderer uses a basic nested-list HTML fallback for provider menus. Styling and browser testing are required before a stable 1.5.0 release.

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
