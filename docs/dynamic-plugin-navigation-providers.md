# Dynamic Plugin Navigation Providers — Menu 1.5.0 design

> **Status:** Initial provider-service resolution and persistent Menu-owned subtree anchors implemented in **develop-1.5.0**. A graphical read-only provider-tree editor and inside/before/after placement are implemented. Full drag-and-drop editing, orphan-anchor diagnostics and release acceptance remain pending. Existing Menu 1.4.0 resolved-tree contract v1 is unchanged. See [plugin author guide](plugin-navigation-provider-guide.md).

## Objective and ownership

A plugin such as a generic example plugin is the authoritative owner of its entire application navigation hierarchy. Menu displays that hierarchy and lets an administrator insert **extra menu entries at the root, before or after provider nodes, or underneath provider nodes**, without editing the provider's tree. An update, disable or failure of the provider must **never delete the administrator's configuration**.

| Concern | Owner |
| --- | --- |
| Application node IDs, hierarchy, route URLs, translated labels and eligibility | Provider plugin |
| Permission checks on each protected destination | Provider plugin and Geeklog |
| Menu placement, presentation, theme integration, administrator-defined links and inserts | Menu plugin |
| Final permission-aware tree composition and cache | Menu plugin, with provider-denial respected |
| User login/session/group context | Geeklog Core |

## Contract proposal (not an existing API)

The initial implementation uses native Geeklog Plugin Services via `PLG_invokeService($plugin, 'getNavigationTree', ...)` and the callback `service_getNavigationTree_<plugin>($args, &$output, &$svc_msg)`. The provider also needs `plugin_wsEnabled_<plugin>()` under Geeklog's service dispatch rules. This callback can affect other exposed services, so audit its security implications. See the implementation guide for a working example; additional capability discovery remains future work.

The input should carry only an authorized logical tree identifier and optional configuration scope. **Do not trust a caller-supplied user ID**; resolve current user, language, active site and permissions from the authenticated Geeklog request. The provider returns an array of **already-filtered** nodes.

Illustrative output (schema to be finalized):

```json
{
  "contract_version": 1,
  "provider": "exampleplugin",
  "tree": "main",
  "nodes": [
    {
      "id": "exampleplugin:home",
      "label": "Home",
      "url": "/exampleplugin/",
      "order": 10,
      "children": []
    },
    {
      "id": "exampleplugin:resources",
      "label": "Resources",
      "url": "/exampleplugin/resources/",
      "order": 30,
      "children": [
        {
          "id": "exampleplugin:resources.items",
          "label": "Items",
          "url": "/exampleplugin/resources/items/",
          "order": 10,
          "children": []
        }
      ]
    }
  ]
}
```

All paths above are examples and must be checked against implemented Example Plugin routes.

### Required validation

- Namespaced stable IDs per provider, no duplicates or recursive/cyclic nesting.
- Maximum recursion depth, node count, label lengths and payload size.
- Valid safe internal/external URLs and explicit restrictions on script schemes; validate `target`, `rel`, `aria_label`, and any future icon/class metadata.
- Bilingual/multilingual labels resolved by the provider for the active request language.
- No raw HTML from plugins in node labels; presentation-neutral data only.
- No group identifiers, private role facts, secret data or access-control details in public tree output.
- Re-validate provider/plugin availability; unavailable or unauthorized providers must disappear from rendered trees without revealing child nodes.
- Provider runtime errors and unsupported versions fail closed while leaving stored administrator entries intact.

## Hybrid composition

The administrator selects a provider, one named provider tree, and a location in an existing Menu-managed navigation. Menu resolves the provider's live nodes at request time. The plugin returns its current hierarchy; Menu applies **stored additive overlays**, not write-back or copies of provider nodes.

The current implementation stores no new overlay table: an existing Menu External URL item with `menu-provider:exampleplugin:main` embeds the tree. `menu-anchor:exampleplugin:resources` appends Menu-owned children beneath the node, while `menu-before:exampleplugin:resources` and `menu-after:exampleplugin:resources` insert Menu-owned entries immediately adjacent to that node. A graphical administration screen exposes these choices. Future enhanced overlay storage could add:

- Menu ID and provider ID/tree ID.
- A durable anchor key such as `exampleplugin:resources`.
- Placement: `before`, `after`, `first_child`, `last_child` (precise order rules to be designed).
- A reference to the **ordinary Menu-owned** custom element/subtree.
- Active/inactive local preference and a stable priority/tiebreaker.

Administrators may add an external link, a Static Page, an article or other ordinary supported element at any depth. Provider-owned nodes are visible as **read-only** in the editor. Only locally owned entries are editable/deletable in Menu.

**Conflict policy:** avoid duplicate IDs, enforce deterministic order and forbid loops. A provider can update text, URLs and ordering without breaking references anchored by stable IDs. If an anchor disappears, the associated custom elements are **preserved** as orphaned configuration, flagged in administration and temporarily withheld or shown at a documented safe fallback location; never silently attach them to a different route. Reappearance of the same anchor can restore placement after revalidation. A disabled provider cannot cause deletion of the overlay or its descendants.

## Compatibility with existing Menu releases

- `MENU_getResolvedTree($name)` stays the single permission-aware, presentation-neutral read model for themes. Its **v1 contract remains compatible**; add only truly additive fields unless introducing an explicitly versioned v2.
- Existing Menu menus, manually configured plugin links, HTML rendering and autotags must continue to work unchanged.
- Dynamic providers do not require writing their node sets to Menu database tables. Schema additions, if necessary, store only local overlay references and are upgrade-safe.
- On conventional sites without provider support, the feature is invisible; there must be no hard dependency on Example Plugin.
- On sites without Menu, Example Plugin can continue to use its native Geeklog flat-menu callback and its own local navigation.
- Test Geeklog/PHP compatibility based on the **declared** support range. If a contract cannot support PHP 5.6, change policy explicitly and coordinate release metadata instead of quietly dropping support.

## Security and caching

The effective tree must use **current user, tenant/resources access, active site, language, menu state and provider state**. Parent/child itemship and resources-manager roles do not imply visibility over private application pages. A provider tree filtered for one user cannot be cached and served to another. Use request-local caching or a carefully validated key and explicit invalidation on permissions, provider changes and administrator edits; fail closed on uncertain caches.

Menu is a navigation renderer, **not an authorization boundary**. Each protected destination performs its own access check even if the menu is hidden. Avoid enumeration of inaccessible nodes via debug pages, exports or API responses.

## Administration acceptance scenarios

1. Example Plugin provides Home, Resources, Documentation and News with nested items.
2. An administrator inserts a custom Static Page under the provider's Resources node and a custom external link at the root.
3. Example Plugin renames/reorders Resources while preserving its node ID; the custom page remains attached correctly.
4. A provider removes an anchor; the local item is preserved, clearly flagged, not inadvertently attached elsewhere.
5. Disabling a provider removes its live nodes, but leaves the saved overlay ready for reactivation.
6. Users without permission do not receive restricted nodes or infer private data through parent visibility or counts.
7. Two different families and two separate Geeklog sites do not leak their menu trees through caches.
8. Legacy Menu 1.4.x navigation and themes, including Eclipse, still render correctly.
9. Repeated resolution produces deterministic trees without duplicate nodes, cycles or unexpected mutations.
10. Installing Example Plugin without Menu remains a supported fallback.

## Implementation sequence for 1.5.0

1. Validate Geeklog Plugin Services interoperability and versioned contract; write sanitized provider fixtures.
2. Implement non-persistent resolution and a strict structural validator.
3. Add overlay storage and migration with backward compatibility and restore safety.
4. Extend Menu editor with read-only provider nodes and editable local anchors.
5. Reuse `MENU_getResolvedTree()`, legacy rendering and theme adapters for the composed tree.
6. Test permission isolation, cache invalidation, provider availability, local custom-item persistence, and upgrade with the generated ZIP.

**Current state:** provider resolution, integration tests, graphical provider-tree viewing and inside/before/after custom-link placement are implemented in development. Full drag-and-drop UX, orphan-anchor diagnostics, broader compatibility/security acceptance and release validation remain planned. See [ROADMAP](../ROADMAP.md).
