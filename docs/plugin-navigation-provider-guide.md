# Implementing a navigation provider for Menu 1.5.0

> **Implementation guide for plugin authors.** Menu 1.5.0 development branch provides a read-only Geeklog Plugin Services integration. This is **not** a released stable API. Test with Geeklog 2.2.2 and your intended PHP runtime before deploying. The provider must implement its own authorization checks.

## Overview

Your plugin **owns** its navigation tree. Menu embeds that tree into an existing Menu menu, renders it in modern resolved-tree consumers and in a basic legacy HTML fallback, and can attach administrator-created Menu submenus under your stable node IDs. Menu does not import or copy your tree into its database.

The integration does not depend on Family Portfolio. Any enabled Geeklog plugin may provide a tree under its own plugin identifier.

## Required callbacks in your plugin

For a plugin whose Geeklog identifier is `portfolio`, add both functions in its loaded `functions.inc` (or ensure the second function is loaded before Menu queries it):

```php
/**
 * Geeklog 2.2.2 PLG_invokeService requires this callback.
 * This does not itself expose a remotely callable public portfolio API:
 * review the plugin's existing webservice access policy separately.
 */
function plugin_wsEnabled_portfolio()
{
    return true;
}

/**
 * Responds to PLG_invokeService('portfolio', 'getNavigationTree', ...).
 *
 * Do not use user identifiers supplied in $args for authorization.
 * All rules are resolved against the authenticated Geeklog session.
 */
function service_getNavigationTree_portfolio($args, &$output, &$svc_msg)
{
    global $_CONF, $_USER;

    $output = array();
    $svc_msg = array();

    $tree = isset($args['tree']) ? (string) $args['tree'] : '';
    if ($tree !== 'main' || !isset($_USER['uid'])
        || (int) $_USER['uid'] <= 1
        || !function_exists('SEC_hasRights')
        || !SEC_hasRights('portfolio.use')) {
        return PLG_RET_ERROR;
    }

    // Example routes only: adjust to your plugin's actual public routes.
    $base = rtrim($_CONF['site_url'], '/') . '/portfolio/';
    $nodes = array(
        array(
            'id' => 'portfolio:home',
            'label' => 'Accueil',
            'url' => $base,
            'children' => array()
        ),
        array(
            'id' => 'portfolio:family',
            'label' => 'Famille',
            'url' => $base . 'family/',
            'children' => array()
        )
    );

    // IMPORTANT: filter every nested node according to the current user's
    // active family memberships and private resource access rules.
    // Translate labels using the provider's active language files.
    $output = array(
        'contract_version' => 1,
        'provider' => 'portfolio',
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
- `id`: stable globally namespaced ID of the form `portfolio:family.members`, unique within the tree. Supported suffix characters are lowercase letters, numbers, periods, underscores and dashes; first character must be a lowercase letter.
- `label`: nonempty plain text (no HTML), maximum 255 bytes.
- `url`: absolute HTTPS/HTTP URL, root-relative path beginning with `/`, empty string or `#`. No `javascript:`, `data:`, protocol-relative URLs or arbitrary schemes.
- `children`: optional recursively nested array; default empty.

Limit: 8 levels of nested children and 300 total nodes per provider tree. On any invalid node, Menu rejects the **entire** provider tree. Preserve node IDs across releases even when translating, renaming, moving or reordering sections.

**Permission requirements:** the provider must omit all nodes unauthorized for the *current* user. Family membership, parental status, and the family-manager role do not automatically permit access to a person's private financial accounts. Every destination must independently enforce its own ACL. Do not put account balances or confidential account labels in the navigation response.

## Configuring Menu as administrator

The initial 1.5.0 implementation deliberately reuses existing **External URL** menu elements so sites do not need a database migration:

1. In Menu administration, open your named menu (such as `navigation`).
2. Add an **External URL** item with label `Family Portfolio` and destination `menu-provider:portfolio:main`. This special destination is a non-clickable marker that Menu replaces with the provider's current hierarchy.
3. To add a custom menu item beneath a provider node such as `portfolio:family`, add an **External URL** item with destination `menu-anchor:portfolio:family`. Add your normal Menu items as **children of that anchor item** using the existing hierarchy editor.
4. The anchor itself is never rendered. Its children are appended beneath the matching provider node at rendering time.
5. To insert a custom item at the top level, add an ordinary Menu item alongside the provider marker. These additions are stored by Menu and remain intact across provider upgrades.

Marker URLs are configuration syntax, **not browser links**. Valid examples:

```text
menu-provider:portfolio:main
menu-anchor:portfolio:family
menu-anchor:portfolio:family.members
```

The current first implementation supports appending custom entries beneath a provider node; drag-and-drop positioning *before* or *after* provider children and an enhanced visual provider-tree administration editor remain future improvements. If an anchor disappears, Menu keeps the administrator's saved nodes but omits them from the rendered tree until that node reappears.

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
- Use the included tests as a starting point for authenticated, anonymous, parent, child and cross-family scenarios.

See [design document](dynamic-plugin-navigation-providers.md) and [roadmap](../ROADMAP.md).
